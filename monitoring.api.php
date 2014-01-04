<?php

/**
 * @file
 * Monitoring API documentation.
 */

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Provides info about available sensors.
 *
 * @return array
 *   Sensor definition.
 */
function hook_monitoring_sensor_info() {
  $sensors = array();

  $sensor['cron_run'] = array(
    // Name/label of the sensor. Should always be displayed in combination
    // with the category and does not have to unnecessarily repeat the category.
    'label' => 'Last cron run',
    // Description to better understand the sensor purpose.
    'description' => 'Monitors cron run',
    // Sensor class that will trigger checks.
    'sensor class' => 'CronRunMonitoring',
    // Result class. Default value is SensorResult.
    'result class' => 'SensorResult',
    // Defines the sensor value type [numeric, state]. Defaults to "numeric".
    'type' => 'numeric',
    // Sensor instance specific settings.
    'settings' => array(
      // Category to which the sensor belongs to.
      'category' => 'Cron',
      // The sensor value units name/label.
      'units_label' => t('Seconds'),
      // Flag if to log sensor activity.
      'log_calls' => FALSE,
      // Default value is set to TRUE. Set this to FALSE to prevent the sensor
      // from being triggered.
      'enabled' => TRUE,
      // Time in seconds during which the sensor result should be cached.
      'caching time' => 0,
      // Define sensor value thresholds.
      // Threshold can be warning or critical.
      // Threshold is defined by an array with two values which represents a sharp
      // limit. In case you want to consider only one value, set the other one to
      // NULL.
      'thresholds' => array(
        // This means that anything smaller or equal to 3 and anything grater or
        // equal to 6 will result in warning status.
        //
        // !! In case any of the provided threshold value is NULL the threshold
        // will not be assessed and considered in OK status. !!
        SensorResultInterface::STATUS_WARNING => array(
          'inner_interval' => array(),
          'outer_interval' => array(),
          'exceeds' => 0,
          'falls' => 0,
        ),
        // Same apply for status critical.
        SensorResultInterface::STATUS_CRITICAL => array(/* .. */),
      ),
    ),
  );

  return $sensors;
}

/**
 * Allows to alter sensor links on the sensor overview page.
 *
 * @param array $links
 *   Links to be altered.
 * @param \Drupal\monitoring\Sensor\SensorInfo $sensor_info
 *   Sensor info object of a sensor for which links are being altered.
 */
function hook_monitoring_sensor_links_alter(&$links, \Drupal\monitoring\Sensor\SensorInfo $sensor_info) {

}
