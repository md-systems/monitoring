<?php

/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\Sensor\SensorStateValue
 */

namespace Drupal\monitoring\Plugin\monitoring\Sensor;

use Drupal;
use Drupal\monitoring\Sensor\Sensors\SensorValueComparisonBase;

/**
 * Generic sensor that checks for the variable value.
 *
 * @Sensor(
 *   id = "state_value",
 *   label = @Translation("State Value"),
 *   description = @Translation("Monitors and checks for the variable value."),
 *   addable = FALSE
 * )
 *
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
