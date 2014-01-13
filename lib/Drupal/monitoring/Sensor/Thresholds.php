<?php
/**
 * @file
 * Contains Drupal\monitoring\Sensor\Thresholds.
 */

namespace Drupal\monitoring\Sensor;

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Helper class to determine status based on thresholds.
 */
class Thresholds {

  /**
   * Thresholds definitions.
   *
   * @var array
   */
  protected $definitions = array();

  protected $message;
  protected $thresholdType;

  /**
   * @param string $threshold_type
   *   Threshold type - one of exceeds, falls, inner_interval, outer_interval
   * @param array $threshold_definitions
   *   Threshold definitions array where keys are statuses.
   *   - status
   *     - int value|interval defined by array of two ints
   */
  function __construct($threshold_type, array $threshold_definitions) {
    $this->thresholdType = $threshold_type;
    $this->definitions = $threshold_definitions;
  }

  /**
   * Gets status based on given value.
   *
   * Note that if the threshold value is NULL no assessment will be carried out
   * therefore the OK value will be returned.
   *
   * @param int $value
   *
   * @return string
   *   Status string.
   */
  public function getMatchedThreshold($value) {
    foreach ($this->definitions as $threshold => $threshold_value) {
      if ($this->thresholdType == 'exceeds' && $this->exceeds($value, $threshold_value)) {
        $this->message = format_string('exceeds @expected', array('@expected' => $threshold_value));
        return $threshold;
      }
      if ($this->thresholdType == 'falls' && $this->falls($value, $threshold_value)) {
        $this->message = format_string('falls below @expected', array('@expected' => $threshold_value));
        return $threshold;
      }
      if ($this->thresholdType == 'inner_interval' && $this->isInsideTheInterval($value, $threshold_value)) {
        $this->message = format_string('violating the interval @from - @to', array('@from' => $threshold_value[0], '@to' => $threshold_value[1]));
        return $threshold;
      }
      if ($this->thresholdType == 'outer_interval' && $this->isOutsideTheInterval($value, $threshold_value)) {
        $this->message = format_string('outside the allowed interval @from - @to', array('@from' => $threshold_value[0], '@to' => $threshold_value[1]));
        return $threshold;
      }
    }

    return SensorResultInterface::STATUS_OK;
  }

  /**
   * Gets status message based on the status and threshold type.
   *
   * @return string
   *   Status message
   */
  public function getStatusMessage() {
    return $this->message;
  }

  /**
   * Checks if provided value exceeds the given threshold.
   *
   * @param int $value
   * @param int $threshold
   *
   * @return bool
   */
  protected function exceeds($value, $threshold) {
    if ($threshold === NULL) {
      return FALSE;
    }
    return $value > $threshold;
  }

  /**
   * Checks if provided value falls below the given threshold.
   *
   * @param int $value
   * @param int $threshold
   *
   * @return bool
   */
  protected function falls($value, $threshold) {
    if ($threshold === NULL) {
      return FALSE;
    }
    return $value < $threshold;
  }

  /**
   * Checks if provided value is inside the interval.
   *
   * @param $value
   * @param array $interval
   *   Array of two values defining the interval.
   *
   * @return bool
   */
  protected function isInsideTheInterval($value, $interval) {
    if ($interval[0] === NULL || $interval[1] === NULL) {
      return FALSE;
    }
    return ($value > $interval[0] && $value < $interval[1]);
  }

  /**
   * Checks if provided value is outside the interval.
   *
   * @param $value
   * @param array $interval
   *   Array of two values defining the inner interval.
   *
   * @return bool
   */
  protected function isOutsideTheInterval($value, $interval) {
    if ($interval[0] === NULL || $interval[1] === NULL) {
      return FALSE;
    }
    return ($value < $interval[0] || $value > $interval[1]);
  }

}
