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
   * The sensor label might not be self-explaining enough or unique without
   * the category, the category should always be present when the label is
   * displayed.
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
   * Returns all settings.
   *
   * @return array
   *   Settings as an array.
   */
  public function getSettings() {
    return $this->sensorInfo['settings'];
  }

  /**
   * Gets time interval value.
   *
   * @return int
   *   Number of seconds of the time interval.
   *   NULL in case the sensor does not define the time interval.
   */
  public function getTimeIntervalValue() {
    return $this->getSetting('time_interval_value', NULL);
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
   * Compiles sensor info values to an associative array.
   *
   * @return array
   *   Sensor info associative array.
   */
  public function toArray() {
    $info_array = array(
      'sensor' => $this->getName(),
      'label' => $this->getLabel(),
      'category' => $this->getCategory(),
      'description' => $this->getDescription(),
      'value_type' => $this->getValueType(),
      'units_label' => $this->getUnitsLabel(),
      'caching_time' => $this->getCachingTime(),
      'time_interval' => $this->getTimeIntervalValue(),
      'enabled' => $this->isEnabled(),
    );

    if ($this->isDefiningThresholds()) {
      $info_array['thresholds'] = array(
        'type' => $this->getThresholdsType(),
        'intervals' => $this->getThresholdsIntervals(),
      );
    }

    return $info_array;
  }

}
