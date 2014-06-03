<?php
/**
 * @file
 * Contains \Drupal\monitoring\Sensor\Sensors\SensorDblog404.
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Monitors 404 page errors from dblog.
 *
 * Displays URL with highest occurrence as message.
 */
class SensorDblog404 extends SensorSimpleDatabaseAggregator {

  /**
   * {@inheritdoc}
   */
  public function getAggregateQuery() {
    $query = parent::getAggregateQuery();
    $query->addField('watchdog', 'message');
    // The message is the requested 404 URL.
    $query->groupBy('message');
    $query->orderBy('records_count', 'DESC');
    $query->range(0, 1);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    parent::runSensor($result);
    if (!empty($this->fetchedObject) && !empty($this->fetchedObject->message)) {
      $result->addStatusMessage($this->fetchedObject->message);
    }
  }
}
