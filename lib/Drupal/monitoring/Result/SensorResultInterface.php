<?php
/**
 * @file
 * Contains \Drupal\monitoring\Result\SensorResultInterface.
 */

namespace Drupal\monitoring\Result;

use Drupal\monitoring\Sensor\SensorInfo;

/**
 * Container for sensor result.
 *
 * @todo more
 */
interface SensorResultInterface {

  const STATUS_OK = 'OK';
  const STATUS_INFO = 'INFO';
  const STATUS_WARNING = 'WARNING';
  const STATUS_CRITICAL = 'CRITICAL';
  const STATUS_UNKNOWN = 'UNKNOWN';

  /**
   * Gets sensor status.
   *
   * @return string
   *   Sensor status.
   */
  function getStatus();
  /**
   * Gets a human readable label for the sensor status.
   *
   * @return string
   *   Sensor status label.
   */
  function getStatusLabel();

  /**
   * Sets sensor status.
   *
   * @param string $status
   *   One of SensorResultInterface::STATUS_* constants.
   */
  function setStatus($status);

  /**
   * Gets sensor status message.
   *
   * Must not be called on an uncompiled result.
   *
   * @return string
   *   Sensor status message.
   */
  function getMessage();

  /**
   * Sets the final result message.
   *
   * If this is set, then the compilation will not extend the message in any
   * way and the sensor completely responsible for making sure that all
   * relevant information like the sensor value is part of the message.
   *
   * @param string $message
   *   Message to be set.
   * @param array $variables
   *   Dynamic values to be replaced for placeholders in the message.
   *
   * @see self::addStatusMessage()
   */
  function setMessage($message, array $variables = array());

  /**
   * Adds sensor status message.
   *
   * Multiple status messages can be added to a single result and will be added
   * to the final status message.
   *
   * @param string $message
   *   Message to be set.
   * @param array $variables
   *   Dynamic values to be replaced for placeholders in the message.
   *
   * @see self::setMessage()
   */
  function addStatusMessage($message, array $variables = array());

  /**
   * Compiles added status messages sets the status.
   *
   * If the status is STATUS_UNKNOWN, this will attempt to set the status
   * based on expected value and threshold configurations. See
   * \Drupal\monitoring\Sensor\SensorInterface::runSensor() for details.
   *
   * @throws \Drupal\monitoring\Sensor\SensorCompilationException
   *   Thrown if an error occurs during the sensor result compilation.
   */
  function compile();

  /**
   * Gets the sensor metric value.
   *
   * @return mixed
   *   Whatever value the sensor is supposed to return.
   */
  function getValue();

  /**
   * Sets sensor value.
   *
   * @param mixed $value
   */
  function setValue($value);

  /**
   * Gets the sensor expected value.
   *
   * @return mixed
   *   Whatever value the sensor is supposed to return.
   */
  function getExpectedValue();

  /**
   * Sets sensor expected value.
   *
   * Set to NULL if you want to prevent the default sensor result assessment.
   * Use 0/FALSE values instead.
   *
   * In case an interval is expected, do not set the expected value, thresholds
   * are used instead.
   *
   * The expected value is not considered when thresholds are configured.
   *
   * @param mixed $value
   */
  function setExpectedValue($value);

  /**
   * Get sensor execution time.
   *
   * @return double
   */
  function getExecutionTime();

  /**
   * Sets sensor execution time.
   *
   * @param double $time
   *   Sensor execution time.
   */
  function setExecutionTime($time);

  /**
   * Casts/processes the sensor value into numeric representation.
   *
   * @return number
   *   Numeric sensor value.
   */
  function toNumber();

  /**
   * Determines if data for given result object are cached.
   *
   * @return boolean
   *   Cached flag.
   */
  function isCached();

  /**
   * The result data timestamp.
   *
   * @return int
   *   Unix timestamp.
   */
  function getTimestamp();

  /**
   * Gets sensor result data as array.
   *
   * @return array
   *   Sensor result data as array.
   */
  function toArray();

  /**
   * Gets sensor name.
   *
   * @return string
   */
  public function getSensorName();

  /**
   * Gets sensor info.
   *
   * @return SensorInfo
   */
  public function getSensorInfo();

  /**
   * Checks if sensor is in UNKNOWN state.
   *
   * @return boolean
   */
  public function isUnknown();

  /**
   * Checks if sensor is in WARNING state.
   *
   * @return boolean
   */
  public function isWarning();

  /**
   * Checks if sensor is in CRITICAL state.
   *
   * @return boolean
   */
  public function isCritical();

  /**
   * Checks if sensor is in OK state.
   *
   * @return boolean
   */
  public function isOk();

  /**
   * Set the verbose output.
   *
   * @param string $verbose_output
   *   The verbose output as a string.
   */
  public function setVerboseOutput($verbose_output);

  /**
   * Returns the verbose output.
   *
   * Verbose output is not persisted and is only available if the sensor result
   * is not cached.
   *
   * @return string
   *   The verbose output as a string.
   */
  public function getVerboseOutput();

}
