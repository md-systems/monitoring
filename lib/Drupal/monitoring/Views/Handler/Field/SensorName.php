<?php
/**
 * @file
 * Contains \Drupal\monitoring\Views\Handler\Field\SensorName
 */

namespace Drupal\monitoring\Views\Handler\Field;


use Drupal\monitoring\Entity\SensorResultEntity;

/**
 * Views handler to output sensor name.
 */
class SensorName extends \views_handler_field_entity {

  /**
   * {@inheritdoc}
   */
  function render($values) {
    /**
     * @var SensorResultEntity $result
     */
    $result = $this->get_value($values);
    $sensor_info = monitoring_sensor_info_instance($result->sensor_name);

    return l($sensor_info->getLabel(), 'admin/reports/monitoring/sensors/' . $result->sensor_name);
  }
}
