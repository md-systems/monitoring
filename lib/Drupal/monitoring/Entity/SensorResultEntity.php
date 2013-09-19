<?php
/**
 * @file
 * Entity class for the sensor result.
 */

namespace Drupal\monitoring\Entity;

/**
 * Entity representation of the sensor result.
 */
class SensorResultEntity extends \Entity {

  public $sensor_name;
  public $sensor_status;
  public $sensor_value;
  public $sensor_message;
  public $timestamp;
  public $execution_time;
}
