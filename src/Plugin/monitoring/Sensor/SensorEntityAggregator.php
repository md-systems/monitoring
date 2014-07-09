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
 * @Sensor(
 *   id = "entity_aggregator",
 *   label = @Translation("Entity Aggregator"),
 *   description = @Translation("Utilises the entity query aggregate functionality."),
 *   addable = TRUE
 * )
 *
 * It utilises the entity query aggregate functionality.
 *
 * The table specified in the sensor info must be the base table of the entity.
 */
class SensorEntityAggregator extends SensorDatabaseAggregatorBase {

  use DependencyTrait;

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
    $field = '';
    $field_value = '';
    $settings = $this->info->getSettings();

    if (isset($this->info->settings['entity_type'])) {
      $form['old_entity_type'] = array(
        '#type' => 'textfield',
        '#default_value' => \Drupal::entityManager()->getDefinition($this->getEntityType())->getClass(),
	'#maxlength' => 255,
        '#title' => t('Entity Type'),
        '#attributes' => array('readonly' => 'readonly'),
      );
      $field = $settings['conditions'][0]['field'];
      $field_value = $settings['conditions'][0]['value'];
    }
    else {
      $form['entity_type'] = array(
        '#type' => 'select',
        '#options' => \Drupal::entityManager()->getEntityTypeLabels(),
        '#title' => t('Entity Type'),
        '#required' => TRUE,
      );
    }

    $form['conditions'][0]['field'] = array(
      '#type' => 'textfield',
      '#title' => t('Condition\'s Field'),
      '#maxlength' => 255,
      '#default_value' => $field,
    );
    $form['conditions'][0]['value'] = array(
      '#type' => 'textfield',
      '#title' => t('Condition\'s Value'),
      '#maxlength' => 255,
      '#default_value' => $field_value,
    );
    return $form;
  }
}
