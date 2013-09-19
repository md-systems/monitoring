<?php
/**
 * @file
 * Contains Drupal\monitoring\Sensor\Sensors\SensorQueue
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\SensorThresholds;

/**
 * Monitors number of items for a given queue.
 */
class SensorQueue extends SensorThresholds {

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    /** @var \DrupalQueueInterface $queue */
    $queue = \DrupalQueue::get($this->info->getSetting('queue'));
    $result->setSensorValue($queue->numberOfItems());
    $result->addSensorStatusMessage($this->info->getSetting('queue'));
  }
}
