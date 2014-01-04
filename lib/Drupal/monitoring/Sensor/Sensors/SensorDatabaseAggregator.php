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
  protected $queryResult;

  /**
   * The fetched object from the query result.
   *
   * @var object
   */
  protected $fetchedObject;

  /**
   * The arguments of the executed query.
   *
   * @var array
   */
  protected $queryArguments;


  /**
   * During instantiation the query is build and executed.
   *
   * @param SensorInfo $info
   */
  public function __construct(SensorInfo $info) {
    parent::__construct($info);
  }

  /**
   * Executes the query and returns the result.
   *
   * @return \DatabaseStatementInterface
   *   The query result.
   */
  protected function getQueryResult() {
    if ($this->queryResult === NULL) {
      $query = $this->buildQuery();
      $this->queryArguments = $query->getArguments();
      $this->queryResult = $query->execute();
    }
    return $this->queryResult;
  }

  /**
   * Returns query arguments of the last executed query.
   *
   * SensorDatabaseAggregator::getQueryResult() must be called first.
   *
   * @return array
   *   The query arguments as an array.
   */
  protected function getQueryArguments() {
    return $this->queryArguments;
  }

  /**
   * {@inheritdoc}
   */
  public function sensorVerbose() {
    return "Query:\n{$this->getQueryResult()->getQueryString()}\n\nArguments:\n" . var_export($this->getQueryArguments(), TRUE);
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
      $this->fetchedObject = $this->getQueryResult()->fetchObject();
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
    $this->buildQueryAggregate($query);

    if ($conditions = $this->info->getSetting('conditions')) {
      foreach ($conditions as $condition) {
        $query->condition($condition['field'], $condition['value'], isset($condition['operator']) ? $condition['operator'] : NULL);
      }
    }

    if ($time_period = $this->info->getSetting('time_period')) {
      $query->where(db_escape_field($time_period['field']) . ' > :timestamp_value',
        array(
          ':timestamp_value' => REQUEST_TIME - $time_period['value'],
        ));
    }

    return $query;
  }

  /**
   * Adds aggregate expressions to the query.
   *
   * Defaults to COUNT(*), override this method to use a different aggregation.
   *
   * @param \SelectQueryInterface $query
   *  The select query that should be aggregated.
   */
  protected function buildQueryAggregate(\SelectQueryInterface $query) {
    $query->addExpression('COUNT(*)', 'records_count');
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
