<?php
/**
 * @file
 * Contains \Drupal\monitoring\Sensor\Sensors\SensorUserFailedLogins.
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Monitors user failed login from dblog messages.
 *
 * Helps to identify bots or brute force attacks.
 */
class SensorUserFailedLogins extends SensorSimpleDatabaseAggregator {

  /**
   * {@inheritdoc}
   */
  public function getAggregateQuery() {
    $query = parent::getAggregateQuery();
    $query->addField('watchdog', 'variables');
    $query->groupBy('watchdog.variables');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $records_count = 0;
    foreach ($this->getAggregateQuery()->execute() as $row) {
      $records_count += $row->records_count;
      $variables = unserialize($row->variables);
      $result->addStatusMessage('@user: @count', array('@user' => $variables['%user'], '@count' => $row->records_count));
    }

    $result->setValue($records_count);
  }

}
