<?php

/**
 * @file
 * Contains \Drupal\monitoring\Sensor\Sensors\SensorValueComparisonBase
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\Component\Utility\String;
use Drupal\monitoring\Sensor\SensorConfigurable;
use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Provides abstract functionality for a value comparison sensor.
 *
 * Uses "value" offset to store the expected value against which the actual
 * value will be compared to. You can prepopulate this offset with initial
 * value that will be used as the expected one on the sensor enable.
 */
abstract class SensorValueComparisonBase extends SensorConfigurable {

  /**
   * Gets the value description that will be shown in the settings form.
   *
   * @return string
   *   Value description.
   */
  abstract protected function getValueDescription();

  /**
   * Gets the actual value.
   *
   * @return mixed
   *   The actual value.
   */
  abstract protected function getActualValue();

  /**
   * Gets the expected value.
   *
   * @return mixed
   *   The expected value.
   */
  protected function getExpectedValue() {
    return $this->info->getSetting('value');
  }

  /**
   * Adds expected value setting field into the sensor settings form.
   */
  public function settingsForm($form, &$form_state) {
    $form = parent::settingsForm($form, $form_state);

    if (is_array($this->getActualValue())) {
      return $form;
    }

    $form['value'] = array(
      '#title' => $this->getValueDescription(),
      '#default_value' => $this->getActualValue(),
    );

    if ($this->info->isNumeric()) {
      $form['value']['#type'] = 'numeric';
    }
    elseif ($this->info->getValueType() == 'bool') {
      $form['value']['#type'] = 'checkbox';
    }
    else {
      $form['value']['#type'] = 'textfield';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $result->setValue($this->getActualValue());
    $result->setExpectedValue($this->getExpectedValue());

    $result->addStatusMessage(String::format('Expected value @expected actual @actual',
      array('@expected' => $result->getFormattedValue($this->getExpectedValue()), '@actual' => $result->getFormattedValue($this->getActualValue()))));
  }
}
