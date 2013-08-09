<?php
/**
 * @file
 * Contains \Drupal\monitoring\Sensor\Sensors\SensorQueue.
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\SensorThresholds;
use Drupal;

/**
 * Monitors number of items for a given core queue.
 *
 * Every instance represents a single queue.
 * Once all queue items are processed, the value should be 0.
 *
 * @see \DrupalQueue
 */
class SensorQueue extends SensorThresholds {

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $result->setValue(\Drupal::queue($this->info->getSetting('queue'))->numberOfItems());
  }
}
