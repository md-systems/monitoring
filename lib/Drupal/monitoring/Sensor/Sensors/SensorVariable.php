<?php

/**
 * @file
 * Generic variable sensor.
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Sensor\Sensor;
use Drupal\monitoring\Sensor\SensorConfigurable;
use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Generic sensor that checks for the variable value.
 */
class SensorVariable extends SensorConfigurable {

  protected $variable;

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, &$form_state) {
    $form = parent::settingsForm($form, $form_state);

    if (is_array($this->info->getSetting('variable_value'))) {
      return $form;
    }

    $form['variable_value'] = array(
      '#type' => 'textfield',
      '#title' => t('Expected value of variable %variable', array('%variable' => $this->info->getSetting('variable_name'))),
      '#default_value' => $this->info->getSetting('variable_value'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $result->setValue(variable_get($this->info->getSetting('variable_name'), $this->info->getSetting('variable_default_value')));
    $result->setExpectedValue($this->info->getSetting('variable_value'));
  }
}
