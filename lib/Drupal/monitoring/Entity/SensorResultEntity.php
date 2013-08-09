<?php
/**
 * @file
 * Contains \Drupal\monitoring\Entity\SensorResultEntity.
 */

namespace Drupal\monitoring\Entity;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * The monitoring_sensor_result entity class.
 *
 * @EntityType(
 *   id = "monitoring_sensor_result",
 *   label = @Translation("Monitoring sensor result"),
 *   module = "monitoring",
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\DatabaseStorageController",
 *   },
 *   base_table = "monitoring_sensor_result",
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "record_id",
 *     "label" = "sensor_message",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class SensorResultEntity extends Entity {

  public $record_id;

  /**
   * The sensor name.
   *
   * @var string
   */
  public $sensor_name;

  /**
   * The sensor status.
   *
   * @var int
   */
  public $sensor_status;

  /**
   * The sensor status.
   *
   * @var string
   */
  public $sensor_value;

  /**
   * The sensor message(s).
   *
   * @var string
   */
  public $sensor_message;

  /**
   * The sensor timestamp in UNIX time format.
   *
   * @var int
   */
  public $timestamp;

  /**
   * The sensor execution time in ms.
   *
   * @var float
   */
  public $execution_time;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->record_id;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::preSave($storage_controller);
    $this->timestamp = REQUEST_TIME;
  }

}
