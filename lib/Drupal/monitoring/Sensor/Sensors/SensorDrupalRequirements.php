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

    $severity = $this->getHighestSeverity($this->requirements);

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
      $result->addSensorStatusMessage('Requirements check OK');
    }
  }

  /**
   * Extracts the highest severity from the requirements array.
   *
   * Replacement for drupal_requirements_severity(), which ignores
   * the INFO severity, which results in those messages not being displayed.
   *
   * @param $requirements
   *   An array of requirements, in the same format as is returned by
   *   hook_requirements().
   *
   * @return
   *   The highest severity in the array.
   *
   *
   */
  protected function getHighestSeverity(&$requirements) {
    $severity = REQUIREMENT_INFO;
    foreach ($requirements as $requirement) {
      if (isset($requirement['severity'])) {
        $severity = max($severity, $requirement['severity']);
      }
    }
    return $severity;
  }
}
