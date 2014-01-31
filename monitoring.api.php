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
    'sensor_class' => 'CronRunMonitoring',
    // Result class. Default value is SensorResult.
    'result_class' => 'SensorResult',
    // Defines the value type and therefore its presentation on UI. The value
    // type is empty by default and optional. The value type must be one of
    // those defined by monitoring_value_types().
    'value_type' => 'time_interval',
    // May define a value label that will be used in the UI. The value label is
    // empty by default and optional.
    'value_label' => 'Druplicons',
    // Defines if the sensor value is numeric. Defaults to TRUE.
    'numeric' => FALSE,
    // Sensor instance specific settings.
    'settings' => array(
      // Category to which the sensor belongs to. Defaults to "Other".
      'category' => 'Cron',
      // Flag if to log sensor activity.
      'result_logging' => FALSE,
      // Default value is set to TRUE. Set this to FALSE to prevent the sensor
      // from being triggered.
      'enabled' => TRUE,
      // Time in seconds during which the sensor result should be cached.
      'caching_time' => 0,
      // A sensor may define a time interval. This will be added to the default
      // message automatically.
      'time_interval_value' => 900,
      // Define sensor value thresholds, which allow to have (configurable)
      // intervals that set the sensor status to warning or critical based on
      // the value. All sensors that extend from
      // \Drupal\monitoring\Sensor\SensorThresholds support thresholds, this
      // definition is only necessary to provide explicit default thresholds.
      'thresholds' => array(
        // The threshold type, this defines which of the additional keys
        // are supported. exceeds and falls use warning/critical, the interval
        // types use the low/high keys.
        //   - exceeds: Escalates if the value exceeds the configured limits,
        //              warning must be lower than critical.
        //   - falls: Escalates if the value falls below the configured limits,
        //            warning must be higher than critical.
        //   - inner_interval: Escalates if the value is within the configured
        //                     intervals, warning must be outside of critical.
        //   - outer_interval: Escalates if the value is outside of the
        //                     configured intervals, warning must be inside of
        //                     critical.
        'type' => 'exceeds|falls|inner_interval|outer_interval',
        'warning' => 5,
        'critical' => 10,
        'warning_high' => 5,
        'warning_low' => 7,
        'critical_high' => 9,
        'critical_low' => 3,
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
