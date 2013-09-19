<?php

/**
 * @file
 * Contains Drupal\monitoring\Sensor\Sensors\SensorDrupalRequirements
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\Sensor;

/**
 * Monitors a specific module hook_requirements.
 */
class SensorDrupalRequirements extends Sensor {

  protected $requirements;

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $module = $this->info->getSetting('module');
    module_load_include('install', $module);
    $function = $module . '_requirements';

    if (!function_exists($function)) {
      $result->setSensorStatus(SensorResultInterface::STATUS_CRITICAL);
    }

    $this->requirements = $function('runtime');

    foreach ($this->info->getSetting('exclude keys', array()) as $exclude_key) {
      if (isset($this->requirements[$exclude_key])) {
        unset($this->requirements[$exclude_key]);
      }
    }

    $severity = drupal_requirements_severity($this->requirements);

    if ($severity == REQUIREMENT_ERROR) {
      $result->setSensorStatus(SensorResultInterface::STATUS_CRITICAL);
    }
    elseif ($severity == REQUIREMENT_WARNING) {
      $result->setSensorStatus(SensorResultInterface::STATUS_WARNING);
    }
    else {
      $result->setSensorStatus(SensorResultInterface::STATUS_OK);
    }

    if (!empty($this->requirements)) {
      foreach ($this->requirements as $requirement) {
        // Skip if we do not have the highest requirements severity.
        if (!isset($requirement['severity']) || $requirement['severity'] != $severity) {
          continue;
        }

        if (!empty($requirement['title'])) {
          $result->addSensorStatusMessage($requirement['title']);
        }

        if (!empty($requirement['description'])) {
          $result->addSensorStatusMessage($requirement['description']);
        }

        if (!empty($requirement['value'])) {
          $result->addSensorStatusMessage($requirement['value']);
        }
      }
    }
    // In case no requirements returned, it is assumed that all is okay.
    else {
      $result->addSensorStatusMessage('Requirements check for module @module OK', array('@module' => $module));
    }
  }
}
