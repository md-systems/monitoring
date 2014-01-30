<?php
/**
 * @file
 * Contains Drupal\monitoring\Result\SensorResult
 */

namespace Drupal\monitoring\Result;

use Drupal\monitoring\Sensor\SensorCompilationException;
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
   * The verbose output of the sensor execution.
   *
   * @var string
   */
  protected $verboseOutput;

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
  public function getSensorStatusLabel() {
    $labels = array(
      self::STATUS_CRITICAL => t('Critical'),
      self::STATUS_WARNING => t('Warning'),
      self::STATUS_INFO => t('Info'),
      self::STATUS_OK => t('OK'),
      self::STATUS_UNKNOWN => t('Unknown'),
    );
    return $labels[$this->getResultData('sensor_status')];
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
    $threshold_message = NULL;
    if ($this->getSensorStatus() == SensorResultInterface::STATUS_UNKNOWN) {
      if ($this->getSensorInfo()->isDefiningThresholds()) {
        $threshold_message = $this->assessThresholds();
      }
      elseif ($this->getSensorExpectedValue() !== NULL) {
        $this->assessComparison();
      }
    }

    if ($this->getSensorInfo()->getValueType() == 'bool') {
      $msg_expected = $this->getSensorExpectedValue() ? 'TRUE' : 'FALSE';
    }
    else {
      $msg_expected = $this->getSensorExpectedValue();
    }

    // Set the default message variables.
    $default_variables = array(
      '@sensor' => $this->getSensorName(),
      '!formatted_value' => $this->getFormattedValue(),
      '@time' => $this->getTimestamp(),
      '!expected' => $msg_expected,
      '!time_interval' => format_interval($this->getSensorInfo()->getTimeIntervalValue()),
    );

    if (!empty($this->sensorMessage)) {
      $message = format_string($this->sensorMessage['message'], $this->sensorMessage['variables']);
    }
    else {
      $messages = array();

      // Set the sensor message.
      if ($this->getSensorValue() !== NULL) {

        // If the sensor defines time interval we append the info to the
        // message.
        if ($this->getSensorInfo()->getTimeIntervalValue()) {
          $messages[] = format_string('!formatted_value in !time_interval', $default_variables);
        }
        else {
          $messages[] = $default_variables['!formatted_value'];
        }
      }
      // Avoid an empty sensor message.
      elseif (empty($this->statusMessages)) {
        $messages[] = 'No value';
      }

      // Set the expected value message if the sensor did not match.
      if ($this->isCritical() && $this->getSensorExpectedValue() !== NULL) {
        $messages[] = format_string('expected !expected', $default_variables);
      }
      // Set the threshold message if there is any.
      if ($threshold_message !== NULL) {
        $messages[] = $threshold_message;
      }

      foreach ($this->statusMessages as $msg) {
        $messages[] = format_string($msg['message'], array_merge($default_variables, $msg['variables']));
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
   *
   * @return string
   *   The message associated with the threshold.
   */
  protected function assessThresholds() {
    $thresholds = new Thresholds($this->sensorInfo->getThresholdsType(), $this->sensorInfo->getThresholdsIntervals());
    $matched_threshold = $thresholds->getMatchedThreshold($this->getSensorValue());

    // Set sensor status based on matched threshold.
    $this->setSensorStatus($matched_threshold);
    return $thresholds->getStatusMessage();
  }

  /**
   * Formats the value to be human readable.
   *
   * @return string
   *   Formatted value.
   *
   * @throws \Drupal\monitoring\Sensor\SensorCompilationException
   */
  protected function getFormattedValue() {
    // If the value type is defined we have the formatter that will format the
    // value to be ready for display.
    if ($value_type = $this->getSensorInfo()->getValueType()) {
      $value_types = monitoring_value_types();
      if (!isset($value_types[$value_type])) {
        throw new SensorCompilationException(format_string('Invalid value type @type', array('@type' => $value_type)));
      }
      elseif (isset($value_types[$value_type]['formatter_callback']) && !function_exists($value_types[$value_type]['formatter_callback'])) {
        throw new SensorCompilationException(format_string('Formatter callback @callback for @type does not exist',
          array('@callback' => $value_types[$value_type]['formatter_callback'], '@type' => $value_type)));
      }
      else {
        $callback = $value_types[$value_type]['formatter_callback'];
        return $callback($this);
      }
    }

    // If there is no value formatter we try to provide something human readable
    // by concatenating the value and label.

    if ($label = $this->getSensorInfo()->getValueLabel()) {
      // @todo This assumption will no longer work when non-english messages
      // supported.
      $label = drupal_strtolower($label);
      return format_string('!value !label', array('!value' => $this->getSensorValue(), '!label' => $label));
    }

    return format_string('Value !value', array('!value' => $this->getSensorValue()));
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
    return round($this->getResultData('execution_time'), 2);
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
   * Returns sensor result data as array.
   *
   * @return array
   *   An array with data having following keys:
   *   - sensor_name
   *   - value
   *   - expected_value
   *   - numeric_value
   *   - status
   *   - message
   *   - execution_time
   *   - timestamp
   */
  public function toArray() {
    return array(
      'sensor_name' => $this->getSensorName(),
      'value' => $this->getSensorValue(),
      'expected_value' => $this->getSensorExpectedValue(),
      'numeric_value' => $this->toNumber(),
      'status' => $this->getSensorStatus(),
      'message' => $this->getSensorMessage(),
      'execution_time' => $this->getSensorExecutionTime(),
      'timestamp' => $this->getTimestamp(),
    );
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
  function getSensorName() {
    return $this->sensorInfo->getName();
  }

  /**
   * {@inheritdoc}
   */
  function getSensorInfo() {
    return $this->sensorInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function setVerboseOutput($verbose_output) {
    $this->verboseOutput = $verbose_output;
  }

  /**
   * {@inheritdoc}
   */
  public function getVerboseOutput() {
    return $this->verboseOutput;
  }


}
