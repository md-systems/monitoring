<?php
/**
 * @file
 * Contains Drupal\monitoring\Sensor\SensorInfo class.
 */

namespace Drupal\monitoring\Sensor;

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Helper class to deal with sensor info.
 */
class SensorInfo {

  protected $sensorName;
  protected $sensorInfo;

  /**
   * Instantiates sensor info object.
   *
   * @param string $sensor_name
   *   Sensor name.
   * @param array $sensor_info
   *   Sensor info.
   */
  function __construct($sensor_name, array $sensor_info) {
    $this->sensorName = $sensor_name;
    $this->sensorInfo = $sensor_info;
  }

  /**
   * Gets sensor name.
   *
   * @return string
   *   Sensor name.
   */
  public function getName() {
    return $this->sensorName;
  }

  /**
   * Gets sensor label.
   *
   * @return string
   *   Sensor label.
   */
  public function getLabel() {
    return $this->sensorInfo['label'];
  }

  /**
   * Gets sensor description.
   *
   * @return string
   *   Sensor description.
   */
  public function getDescription() {
    return $this->sensorInfo['description'];
  }

  /**
   * Gets sensor class.
   *
   * @return string
   *   Sensor class
   */
  public function getSensorClass() {
    return $this->sensorInfo['sensor class'];
  }

  /**
   * Gets sensor result class.
   *
   * @return string
   *   Result class.
   */
  public function getResultClass() {
    return $this->sensorInfo['result class'];
  }

  /**
   * Gets sensor categories.
   *
   * @return string
   *   Categories.
   */
  public function getCategory() {
    return $this->getSetting('category');
  }

  /**
   * Gets sensor units_label.
   *
   * @return string
   *   Sensor units_label.
   */
  public function getUnitsLabel() {
    return $this->getSetting('units_label');
  }

  /**
   * Gets Sensor value type.
   *
   * @return string
   *   Sensor type [numeric, state].
   */
  public function getValueType() {
    return $this->sensorInfo['type'];
  }

  /**
   * Gets sensor caching time.
   *
   * @return int
   *   Caching time in seconds.
   */
  public function getCachingTime() {
    return $this->getSetting('caching time');
  }

  /**
   * Gets threshold type.
   *
   * @return string|null
   *   Threshold type.
   */
  public function getThresholdsType() {
    $thresholds = $this->getSetting('thresholds');
    if (!empty($thresholds['type'])) {
      return $thresholds['type'];
    }

    // We assume the default threshold type.
    return 'exceeds';
  }

  /**
   * Gets thresholds.
   *
   * @return array
   *   Thresholds definition.
   */
  public function getThresholdsIntervals() {
    $thresholds = $this->getSetting('thresholds');
    if (!empty($thresholds['intervals'])) {
      return $thresholds['intervals'];
    }

    return array(
      SensorResultInterface::STATUS_CRITICAL => NULL,
      SensorResultInterface::STATUS_WARNING => NULL,
    );
  }

  /**
   * Gets setting.
   *
   * @param string $key
   *   Setting key.
   * @param mixed $default
   *   Default value if the setting does not exist.
   *
   * @return mixed
   *   Setting value.
   */
  public function getSetting($key, $default = NULL) {
    return isset($this->sensorInfo['settings'][$key]) ? $this->sensorInfo['settings'][$key] : $default;
  }

  /**
   * Checks if sensor is enabled.
   *
   * @return bool
   */
  public function isEnabled() {
    return (boolean) $this->getSetting('enabled');
  }

  /**
   * Checks if sensor is configurable.
   *
   * @return bool
   */
  public function isConfigurable() {
    return in_array('Drupal\monitoring\Sensor\SensorConfigurableInterface', class_implements($this->getSensorClass()));
  }

  /**
   * Checks if sensor provides extended info.
   *
   * @return bool
   */
  public function isExtendedInfo() {
    return in_array('Drupal\monitoring\Sensor\SensorExtendedInfoInterface', class_implements($this->getSensorClass()));
  }

  /**
   * Checks if sensor defines thresholds.
   *
   * @return bool
   */
  public function isDefiningThresholds() {
    return in_array('Drupal\monitoring\Sensor\SensorThresholdsInterface', class_implements($this->getSensorClass()));
  }

  /**
   * Checks if sensor results should be logged.
   *
   * @param string $logging_mode
   *   Global logging mode setting value.
   * @param string $old_status
   *   The old sensor status.
   * @param string $new_status
   *   Thew new sensor status.
   *
   * @return bool
   */
  public function logResults($logging_mode, $old_status = NULL, $new_status = NULL) {
    $log_activity = $this->getSetting('log_calls', FALSE);

    // We log if requested or on status change.
    if ($logging_mode == 'on_request') {
      return $log_activity || ($old_status != $new_status);
    }

    // We are logging all.
    if ($logging_mode == 'all') {
      return TRUE;
    }

    return FALSE;
  }
}
