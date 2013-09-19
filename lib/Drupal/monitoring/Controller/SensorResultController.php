<?php

/**
 * @file
 * Monitoring Sensor Call controller class.
 */

namespace Drupal\monitoring\Controller;

/**
 * Controller class for monitoring_sensor_result.
 */
class SensorResultController extends \EntityAPIController {

  /**
   * {@inheritdoc}
   */
  public function create(array $values = array()) {
    $entity = parent::create($values);

    if (empty($entity->timestamp)) {
      $entity->timestamp = REQUEST_TIME;
    }

    return $entity;
  }

}
