<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\Sensor\SensorUserFailedLogins.
 */

namespace Drupal\monitoring\Plugin\monitoring\Sensor;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Monitors user failed login from dblog messages.
 *
 * @Sensor(
 *   id = "monitoring_user_failed_logins",
 *   label = @Translation("User Failed Logins"),
 *   description = @Translation("Monitors user failed login from dblog messages.")
 * )
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
