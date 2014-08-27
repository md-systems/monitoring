<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\Sensor\SensorEntityAggregator.
 */

namespace Drupal\monitoring\Plugin\monitoring\Sensor;


use Drupal\Component\Utility\String;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal;
use Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregatorBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\monitoring\Entity\SensorInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity database aggregator.
 *
 * It utilises the entity query aggregate functionality.
 *
 * @Sensor(
 *   id = "entity_aggregator",
 *   label = @Translation("Entity Aggregator"),
 *   description = @Translation("Utilises the entity query aggregate functionality."),
 *   addable = TRUE
 * )
 */
class SensorEntityAggregator extends SensorDatabaseAggregatorBase {

  use DependencySerializationTrait;
  use DependencyTrait;

  /**
   * Local variable to store the field that is used as aggregate.
   *
   * @var string
   *   Field name.
   */
  protected $aggregateField;

  /**
   * Local variable to store \Drupal::entityManger().
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Local variable to store \Drupal::entityQueryAggregate.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryAggregate;

  /**
   * Builds the entity aggregate query.
   *
   * @return Drupal\Core\Entity\Query\QueryInterface
   *   The entity query object.
   */
  protected function getEntityQueryAggregate() {
    $entity_info = $this->entityManager->getDefinition($this->getEntityType(), TRUE);

    $query = $this->entityQueryAggregate->getAggregate($this->getEntityType());
    $this->aggregateField = $entity_info->getKey('id');
    $query->aggregate($this->aggregateField, 'COUNT');

    foreach ($this->getConditions() as $condition) {
      $query->condition($condition['field'], $condition['value'], isset($condition['operator']) ? $condition['operator'] : NULL);
    }

    if ($time_interval_field = $this->getTimeIntervalField()) {
      $query->condition($time_interval_field, REQUEST_TIME - $this->getTimeIntervalValue(), '>');
    }

    return $query;
  }

  /**
   * Constructs an SensorEntityAggregator object.
   */
  public function __construct (SensorInfo $info, $plugin_id, $plugin_definition, EntityManagerInterface $entityManager, QueryFactory $entity_query) {
    parent::__construct($info, $plugin_id, $plugin_definition);
    $this->entityManager = $entityManager;
    $this->entityQueryAggregate = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, SensorInfo $info, $plugin_id, $plugin_definition) {
    return new static(
      $info,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('entity.query')
    );
  }

  protected function getEntityType() {
    return $this->info->getSetting('entity_type');
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $query_result = $this->getEntityQueryAggregate()->execute();
    $entity_type = $this->getEntityType();
    $entity_info = $this->entityManager->getDefinition($entity_type);

    if (isset($query_result[0][$entity_info->getKey('id') . '_count'])) {
      $records_count = $query_result[0][$entity_info->getKey('id') . '_count'];
    }
    else {
      $records_count = 0;
    }

    $result->setValue($records_count);
  }

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    return String::format('Aggregate field @field', array('@field' => $this->aggregateField));
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $entity_type_id = $this->getEntityType();
    if (!$entity_type_id) {
      throw new \Exception(String::format('Sensor @id is missing the required entity_type setting.', array('@id' => $this->id())));
    }
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    $this->addDependency('module', $entity_type->getProvider());
    return $this->dependencies;
  }

  /**
   * Adds UI for variables entity_type and conditions.
   */
  public function settingsForm($form, &$form_state) {
    $form = parent::settingsForm($form, $form_state);
    $conditions = array(array('field' => '', 'value' => ''));
    $settings = $this->info->getSettings();

    if (isset($this->info->settings['entity_type'])) {
      $form['old_entity_type'] = array(
        '#type' => 'textfield',
        '#default_value' => $this->entityManager->getDefinition($this->getEntityType())->getClass(),
	'#maxlength' => 255,
        '#title' => t('Entity Type'),
        '#attributes' => array('readonly' => 'readonly'),
      );
      if (isset($settings['conditions'])) {
        $conditions = $settings['conditions'];
      }
    }
    else {
      $form['entity_type'] = array(
        '#type' => 'select',
        '#options' => $this->entityManager->getEntityTypeLabels(),
        '#title' => t('Entity Type'),
        '#required' => TRUE,
      );
    }

    /*    if (isset($form_state['values']['settings']['conditions']['table'])) {
      $conditions = $form_state['values']['settings']['conditions']['table'];
    }

    $form['conditions'] = array(
      '#type' => 'fieldset',
      '#title' => t('Conditions'),
      '#prefix' => '<div id="add-conditions">',
      '#suffix' => '</div>',
    );

    $form['conditions']['conditions_add_button'] = array(
      '#type' => 'submit',
      '#value' => t('Add Condition'),
      '#ajax' => array(
        'wrapper' => 'add-conditions',
        'callback' => array($this, 'addConditions'),
        'method' => 'replace',
      ),
      '#submit' => array(
        array($this, 'addConditionSubmit'),
      ),
    );

    $form['conditions']['table'] = array(
      '#type' => 'table',
      '#header' => array(
        'no' => t('Condition No.'),
        'field' => t('Field'),
        'value' => t('Value'),
      ),
      '#prefix' => '<div id="add-conditions">',
      '#suffix' => '</div>',
      '#empty' => t(
        'Add Conditions to this sensor.'
      ),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'sensors-table-weight',
        ),
      ),
    );
    */
    foreach ($conditions as $no => $condition) {
      $form['conditions'][$no] = array(
        'field' => array(
          '#type' => 'textfield',
	  '#title' => t('Condition\'s Field'),
          '#default_value' => $condition['field'],
          '#required' => TRUE,
        ),
        'value' => array(
          '#type' => 'textfield',
	  '#title' => t('Condition\'s Value'),
          '#default_value' => $condition['value'],
          '#required' => TRUE,
        )
      );
    }
    return $form;
  }

  /**
   * Returns the rebuild form;
   */
  public function addConditions(array $form, &$form_state) {
    return $form['settings']['conditions'];
  }

  /**
   * Adds new condition field and value to the form.
   */
  public function addConditionSubmit(array $form, &$form_state) {
    $form_state['rebuild'] = TRUE;
    $form_state['values']['settings']['conditions']['table'] += array(array('field' => '', 'value' => ''));
  }

}
