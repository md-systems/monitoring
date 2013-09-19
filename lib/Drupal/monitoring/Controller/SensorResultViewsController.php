<?php
/**
 * @file
 * Contains Drupal\monitoring\Controller\SensorResultViewsController
 */

namespace Drupal\monitoring\Controller;

/**
 * Handles views for Translation Server entity.
 */
class SensorResultViewsController extends \EntityDefaultViewsController {

  /**
   * {@inheritdoc}
   */
  public function views_data() {
    $data = parent::views_data();
    $data['monitoring_sensor_result']['sensor_name']['field']['handler'] = 'Drupal\monitoring\Views\Handler\Field\SensorName';

    return $data;
  }
}
