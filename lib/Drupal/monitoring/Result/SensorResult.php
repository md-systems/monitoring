<?php
/**
 * @file
 * Contains Drupal\monitoring\Result\SensorResult
 */

namespace Drupal\monitoring\Result;

use Drupal\monitoring\Sensor\SensorInfo;
use Drupal\monitoring\Sensor\Thresholds;

/**
 * Provides generic container for the sensor result.
 */
class SensorResult implements SensorResultInterface {

  protected $sensorInfo;
  protected $isCached = FALSE;
  protected $data = array();

  protected $statusMessages = array();
  protected $sensorMessage = array();

  /**
   * Instantiates a sensor result object.
   *
   * @param SensorInfo $sensor_info
   *   Sensor info object.
   * @param array $cached_data
   *   Result data obtained from a cache.
   */
  function __construct(SensorInfo $sensor_info, array $cached_data = array()) {
    $this->sensorInfo = $sensor_info;
    if ($cached_data) {
      $this->data = $cached_data;
      $this->isCached = TRUE;
    }

    // Merge in defaults in case there is nothing cached for given sensor yet.
    $this->data += array(
      'sensor_status' => SensorResultInterface::STATUS_UNKNOWN,
      'sensor_message' => NULL,
      'sensor_expected_value' => NULL,
      'sensor_value' => NULL,
      'execution_time' => 0,
      'timestamp' => REQUEST_TIME,
    );
  }

  /**
   * Sets result data.
   *
   * @param string $key
   *   Data key.
   * @param mixed $value
   *   Data to set.
   */
  protected function setResultData($key, $value) {
    $this->data[$key] = $value;
    $this->isCached = FALSE;
  }

  /**
   * Gets result data.
   *
   * @param string $key
   *   Data key.
   *
   * @return mixed
   *   Stored data.
   */
  protected function getResultData($key) {
    return $this->data[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function getSensorStatus() {
    return $this->getResultData('sensor_status');
  }

  /**
   * {@inheritdoc}
   */
  public function getSensorMessage() {
    return $this->getResultData('sensor_message');
  }

  /**
   * {@inheritdoc}
   */
  public function setSensorMessage($message, array $variables = array()) {
    $this->sensorMessage = array(
      'message' => $message,
      'variables' => $variables,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function addSensorStatusMessage($message, array $variables = array()) {
    $this->statusMessages[] = array(
      'message' => $message,
      'variables' => $variables,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function compile() {

    // If the status is UNKNOWN we do the value assessment.
    if ($this->getSensorStatus() == SensorResultInterface::STATUS_UNKNOWN) {
      if ($this->getSensorInfo()->isDefiningThresholds()) {
        $this->assessThresholds();
      }
      elseif ($this->getSensorExpectedValue() !== NULL) {
        $this->assessComparison();
      }
    }

    // In case the sensor value is not set provide it explicitly as the status
    // code.
    if ($this->getSensorValue() === NULL) {
      $this->setSensorValue($this->getSensorStatus());
    }

    if (is_bool($this->getSensorValue())) {
      $msg_value = $this->getSensorValue() ? 'TRUE' : 'FALSE';
    }
    else {
      $msg_value = $this->getSensorValue();
    }

    if (is_bool($this->getSensorExpectedValue())) {
      $msg_expected = $this->getSensorExpectedValue() ? 'TRUE' : 'FALSE';
    }
    else {
      $msg_expected = $this->getSensorExpectedValue();
    }

    // Set the default message variables.
    $default_variables = array(
      '@sensor' => $this->getSensorName(),
      '@value' => $msg_value,
      '@time' => $this->getTimestamp(),
      '@expected' => $msg_expected,
    );

    if (!empty($this->sensorMessage)) {
      $message = format_string($this->sensorMessage['message'], $this->sensorMessage['variables']);
    }
    else {
      $messages = array();
      foreach ($this->statusMessages as $msg) {
        $messages[] = format_string($msg['message'], array_merge($default_variables, $msg['variables']));
      }

      // Set the expected value message.
      if ($this->getSensorExpectedValue() !== NULL) {
        array_unshift($messages, format_string('expected @expected', $default_variables));
      }
      // Set the sensor value message
      if ($this->getSensorValue() !== NULL) {
        array_unshift($messages, format_string('Value @value', $default_variables));
      }

      $message = implode(', ', $messages);
    }

    $this->setResultData('sensor_message', $message);

  }

  /**
   * Performs comparison of expected and actual sensor values.
   */
  protected function assessComparison() {
    if ($this->getSensorValue() != $this->getSensorExpectedValue()) {
      $this->setSensorStatus(SensorResultInterface::STATUS_CRITICAL);
    }
    else {
      $this->setSensorStatus(SensorResultInterface::STATUS_OK);
    }
  }

  /**
   * Helper method to deal with thresholds.
   */
  protected function assessThresholds() {
    $thresholds = new Thresholds($this->sensorInfo->getThresholdsType(), $this->sensorInfo->getThresholdsIntervals());
    $matched_threshold = $thresholds->getMatchedThreshold($this->getSensorValue());

    // Set sensor status based on matched threshold.
    $this->setSensorStatus($matched_threshold);
    if ($message = $thresholds->getStatusMessage()) {
      $this->addSensorStatusMessage($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSensorValue() {
    return $this->getResultData('sensor_value');
  }

  /**
   * {@inheritdoc}
   */
  public function getSensorExpectedValue() {
    return $this->getResultData('sensor_expected_value');
  }

  /**
   * {@inheritdoc}
   */
  public function getSensorExecutionTime() {
    return $this->getResultData('execution_time');
  }

  /**
   * {@inheritdoc}
   */
  public function setSensorStatus($sensor_status) {
    $this->setResultData('sensor_status', $sensor_status);
  }

  /**
   * {@inheritdoc}
   */
  public function setSensorValue($sensor_value) {
    $this->setResultData('sensor_value', $sensor_value);
  }

  /**
   * {@inheritdoc}
   */
  public function setSensorExpectedValue($sensor_value) {
    $this->setResultData('sensor_expected_value', $sensor_value);
  }

  /**
   * {@inheritdoc}
   */
  public function setSensorExecutionTime($execution_time) {
    $this->setResultData('execution_time', $execution_time);
  }

  /**
   * {@inheritdoc}
   */
  public function toNumber() {

    $sensor_value = $this->getSensorValue();

    if (is_numeric($sensor_value)) {
      return $sensor_value;
    }

    // Casting to int should be good enough as boolean will get casted to 0/1
    // and string as well.
    return (int) $sensor_value;
  }

  /**
   * Helper method to check the warning state.
   *
   * @return bool
   *   Check result.
   */
  public function isWarning() {
    return $this->getSensorStatus() == SensorResultInterface::STATUS_WARNING;
  }

  /**
   * Helper method to check the critical state.
   *
   * @return bool
   *   Check result.
   */
  public function isCritical() {
    return $this->getSensorStatus() == SensorResultInterface::STATUS_CRITICAL;
  }

  /**
   * Helper method to check the unknown state.
   *
   * @return bool
   *   Check result.
   */
  public function isUnknown() {
    return $this->getSensorStatus() == SensorResultInterface::STATUS_UNKNOWN;
  }

  /**
   * Helper method to check the OK state.
   *
   * @return bool
   *   Check result.
   */
  public function isOk() {
    return $this->getSensorStatus() == SensorResultInterface::STATUS_OK;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityValues() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function isCached() {
    return $this->isCached;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestamp() {
    return $this->getResultData('timestamp');
  }

  /**
   * {@inheritdoc}
   */
  public function verbose($as_array = FALSE) {
    if ($as_array) {
      return array(
        'status' => $this->getSensorStatus(),
        'message' => $this->getSensorMessage(),
        'value' => $this->getSensorValue(),
        'execution time' => $this->getSensorExecutionTime(),
      );
    }
    return
      'status: ' . $this->getSensorStatus() . "\n" .
      'message: ' . $this->getSensorMessage() . "\n" .
      'value: ' . $this->getSensorValue() . "\n" .
      'execution time: ' . $this->getSensorExecutionTime() . "\n";
  }

  /**
   * {@inheritdoc}
   */
  function getSensorName() {
    return $this->sensorInfo->getName();
  }

  /**
   * {@inheritdoc}
   */
  function getSensorInfo() {
    return $this->sensorInfo;
  }


}
