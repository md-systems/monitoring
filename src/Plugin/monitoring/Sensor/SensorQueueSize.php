<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\Sensor\SensorQueueSize.
 */

namespace Drupal\monitoring\Plugin\monitoring\Sensor;

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\SensorThresholds;
use Drupal;

/**
 * Monitors number of items for a given core queue.
 *
 * @Sensor(
 *   id = "queue_size",
 *   label = @Translation("Queue Size"),
 *   description = @Translation("Monitors number of items for a given core queue.")
 * )
 *
 * Every instance represents a single queue.
 * Once all queue items are processed, the value should be 0.
 *
 * @see \DrupalQueue
 */
class SensorQueueSize extends SensorThresholds {

  /**
   * Adds UI to select Queue for the sensor.
   */
  public function settingsForm($form, &$form_state) {
    $form = parent::settingsForm($form, $form_state);
    $queues = array_keys(Drupal::moduleHandler()->invokeAll('queue_info'));
    $form['queue'] = array(
      '#type' => 'select',
      '#options' => $queues,
      '#default_value' => $this->info->getSetting('queue'),
      '#required' => TRUE,
      '#title' => t('Queues'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $result->setValue(\Drupal::queue($this->info->getSetting('queue'))->numberOfItems());
  }
}
