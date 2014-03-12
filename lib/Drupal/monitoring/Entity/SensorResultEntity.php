<?php
/**
 * @file
 * Contains \Drupal\monitoring\Entity\SensorResultEntity.
 */

namespace Drupal\monitoring\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;

/**
 * The monitoring_sensor_result entity class.
 *
 * @ContentEntityType(
 *   id = "monitoring_sensor_result",
 *   label = @Translation("Monitoring sensor result"),
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
class SensorResultEntity extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('record_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::preSave($storage_controller);
    $this->timestamp = REQUEST_TIME;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['record_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Record ID'))
      ->setDescription(t('The record ID.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The record UUID.'))
      ->setReadOnly(TRUE);

    $fields['sensor_name'] = FieldDefinition::create('string')
      ->setLabel(t('Sensor name'))
      ->setDescription(t('The machine name of the sensor.'));

    $fields['sensor_status'] = FieldDefinition::create('string')
      ->setLabel(t('Sensor status'))
      ->setDescription(t('The sensor status at the moment of the sensor run.'));

    $fields['sensor_value'] = FieldDefinition::create('string')
      ->setLabel(t('Sensor value'))
      ->setDescription(t('The sensor value at the moment of the sensor run.'));

    $fields['sensor_message'] = FieldDefinition::create('string')
      ->setLabel(t('Sensor message'))
      ->setDescription(t('The sensor message reported by the sensor.'));

    // @todo Convert to a "created" field in https://drupal.org/node/2145103.
    $fields['timestamp'] = FieldDefinition::create('integer')
      ->setLabel(t('Timestamp'))
      ->setDescription(t('The time that the sensor was executed.'));

    $fields['execution_time'] = FieldDefinition::create('string')
      ->setLabel(t('Execution time'))
      ->setDescription(t('The time needed for the sensor to execute in ms.'));

    return $fields;
  }

}
