<?php

/**
 * @file
 * Contains \Drupal\monitoring\Sensor\Sensors\SensorStateValue
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal;

/**
 * Generic sensor that checks for the variable value.
 */
class SensorStateValue extends SensorValueComparisonBase {

  /**
   * {@inheritdoc}
   */
  protected function getValueDescription() {
    return t('Expected value of state %state', array('%state' => $this->info->getSetting('state')));
  }

  /**
   * {@inheritdoc}
   */
  protected function getActualValue() {
    $state = $this->getState();
    return $state->get($this->info->getSetting('state'));
  }

  /**
   * Gets config.
   *
   * @return \Drupal\Core\KeyValueStore\State
   */
  protected function getState() {
    return $this->getService('state');
  }
}
