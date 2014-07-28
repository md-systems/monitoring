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
use Drupal\Core\Entity\DependencyTrait;

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

  use DependencyTrait;

  public $conditions;

  /**
   * Local variable to store the field that is used as aggregate.
   *
   * @var string
   *   Field name.
   */
  protected $aggregateField;

  /**
   * Builds the entity aggregate query.
   *
   * @return Drupal\Core\Entity\Query\QueryInterface
   *   The entity query object.
   */
  protected function getEntityQueryAggregate() {
    $entity_info = \Drupal::entityManager()->getDefinition($this->getEntityType(), TRUE);

    $query = \Drupal::entityQueryAggregate($this->getEntityType());
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

  protected function getEntityType() {
    return $this->info->getSetting('entity_type');
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $query_result = $this->getEntityQueryAggregate()->execute();
    $entity_type = $this->getEntityType();
    $entity_info = \Drupal::entityManager()->getDefinition($entity_type);

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
    $entity_type = \Drupal::entityManager()->getDefinition($entity_type_id);
    $this->addDependency('module', $entity_type->getProvider());
    return $this->dependencies;
  }

  /**
   * Adds UI for variables entity_type and conditions.
   */
  public function settingsForm($form, &$form_state) {
    $form = parent::settingsForm($form, $form_state);
    $this->conditions = array();
    if (isset($form_state['values']['conditions'])) {
      $conditions = $form_state['values']['conditions'];
    }
    $settings = $this->info->getSettings();

    if (isset($this->info->settings['entity_type'])) {
      $form['old_entity_type'] = array(
        '#type' => 'textfield',
        '#default_value' => \Drupal::entityManager()->getDefinition($this->getEntityType())->getClass(),
	'#maxlength' => 255,
        '#title' => t('Entity Type'),
        '#attributes' => array('readonly' => 'readonly'),
      );
      if (isset($settings['conditions'])) {
        $this->conditions = $settings['conditions'];
      }
    }
    else {
      $form['entity_type'] = array(
        '#type' => 'select',
        '#options' => \Drupal::entityManager()->getEntityTypeLabels(),
        '#title' => t('Entity Type'),
        '#required' => TRUE,
      );
    }

    $this->conditions += array(array('field' => '', 'value' => ''));

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

    foreach ($this->conditions as $no => $condition) {
      $form['conditions']['table'][$no] = array(
        'no' => array(
          '#markup' => count($this->conditions),
        ),
        'field' => array(
          '#type' => 'textfield',
          '#default_value' => $condition['field'],
          '#required' => TRUE,
        ),
        'value' => array(
          '#type' => 'textfield',
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
  public function addConditions(array $form, array &$form_state) {
    return $form['settings']['conditions'];
  }

  /**
   * Adds new condition field and value to the form.
   */
  public function addConditionSubmit(array $form, array &$form_state) {
    $form_state['rebuild'] = TRUE;
    $this->conditions += array(array('field' => '', 'value' => ''));
  }

}
