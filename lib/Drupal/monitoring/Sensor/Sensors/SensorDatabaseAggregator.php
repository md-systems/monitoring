<?php
/**
 * @file
 * Contains Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator
 */

namespace Drupal\monitoring\Sensor\Sensors;


use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\SensorExtendedInfoInterface;
use Drupal\monitoring\Sensor\SensorInfo;
use Drupal\monitoring\Sensor\SensorThresholds;

/**
 * Base for database aggregator sensors.
 *
 * Provides basic query build logic and generic verbosity.
 *
 * Settings:
 * - conditions:
 *  - field
 *  - value
 *  - operator
 * - time_period:
 *  - field - timestamp field name
 *  - value - number of seconds defining the period
 */
class SensorDatabaseAggregator extends SensorThresholds implements SensorExtendedInfoInterface {

  /**
   * The result of the db query execution.
   *
   * @var \DatabaseStatementInterface
   */
  protected $executedQuery;

  protected $fetchedObject;
  protected $queryArguments;


  /**
   * During instantiation the query is build and executed.
   *
   * @param SensorInfo $info
   */
  public function __construct(SensorInfo $info) {
    parent::__construct($info);
    $query = $this->buildQuery();
    $this->alterQuery($query);
    $this->queryArguments = $query->getArguments();
    $this->executedQuery = $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function sensorVerbose() {
    return "Query:\n{$this->executedQuery->getQueryString()}\n\nArguments:\n" . var_export($this->queryArguments, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    return $this->sensorVerbose();
  }

  /**
   * Helper function to get fetched object from the executed query.
   *
   * Multiple calls to executedQuery->fetchObjects() resulted in second call
   * returning false value.
   *
   * @return object
   */
  public function fetchObject() {
    if ($this->fetchedObject === NULL) {
      $this->fetchedObject = $this->executedQuery->fetchObject();
    }
    return $this->fetchedObject;
  }

  /**
   * Builds the database query.
   *
   * @return \SelectQuery
   */
  protected function buildQuery() {
    $table = $this->info->getSetting('table');
    $query = db_select($table);
    $query->addExpression('COUNT(*)', 'records_count');

    if ($conditions = $this->info->getSetting('conditions')) {
      foreach ($conditions as $condition) {
        $query->condition($condition['field'], $condition['value'], isset($condition['operator']) ? $condition['operator'] : NULL);
      }
    }

    if ($time_period = $this->info->getSetting('time_period')) {
      $query->where(check_plain($time_period['field']) . ' > :timestamp_value',
        array(
          ':timestamp_value' => REQUEST_TIME - $time_period['value'],
        ));
    }

    return $query;
  }

  /**
   * Extending sensors can alter the query via overriding this method.
   *
   * @param \SelectQuery $query
   */
  public function alterQuery(\SelectQuery $query) {

  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $query_result = $this->fetchObject();
    if (!empty($query_result)) {
      $records_count = $query_result->records_count;
    }
    else {
      $records_count = 0;
    }

    $result->setSensorValue($records_count);
  }
}
