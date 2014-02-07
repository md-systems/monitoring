<?php
/**
 * @file
 * Contains \Drupal\monitoring\Entity\SensorResultEntity.
 */

namespace Drupal\monitoring\Entity;

/**
 * The monitoring_sensor_result entity class.
 */
class SensorResultEntity extends \Entity {

  public $sensor_name;
  public $sensor_status;
  public $sensor_value;
  public $sensor_message;
  public $timestamp;
  public $execution_time;
}
