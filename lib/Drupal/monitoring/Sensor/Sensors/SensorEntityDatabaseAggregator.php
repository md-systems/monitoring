<?php
/**
 * @file
 * Contains \Drupal\monitoring\Sensor\Sensors\SensorSimpleDatabaseAggregator.
 */

namespace Drupal\monitoring\Sensor\Sensors;


use Drupal\Component\Utility\String;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal;

/**
 * Entity database aggregator.
 *
 * It utilises the entity query aggregate functionality.
 *
 * The table specified in the sensor info must be the base table of the entity.
 */
class SensorEntityDatabaseAggregator extends SensorDatabaseAggregatorBase {

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
    $entity_type = $this->getEntityTypeFromTable($this->getEntityType());
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
   * Returns the entity type for a given base table.
   *
   * @param string $base_table
   *   The name of base table.
   *
   * @return string
   *   The entity type that is stored in the given base table.
   */
  protected function getEntityTypeFromTable($base_table) {
    foreach (\Drupal::entityManager()->getDefinitions() as $entity_type => $entity_info) {
      if ($entity_info->getBaseTable() == $base_table) {
        return $entity_type;
      }
    }
    return NULL;
  }
}
