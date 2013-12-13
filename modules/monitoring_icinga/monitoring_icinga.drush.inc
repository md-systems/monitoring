<?php

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Implements hook_drush_command().
 */
function monitoring_icinga_drush_command() {
  $items = array();

  $items['monitoring-icinga'] = array(
    'callback' => 'monitoring_icinga_drush_callback',
    'options' => array(
      'sensor_name' => 'Sensor name to invoke.',
    ),
    'description' => 'Run munin plugins.',
    'examples' => array(
      'drush monitoring-icinga plugin_name' => 'Run munin plugin.',
    ),
    'drupal dependencies' => array('monitoring_icinga'),
  );

  return $items;
}

/**
 * Drush callback to get the sensor info.
 *
 * @param string $sensor_name
 *   Sensor name.
 */
function monitoring_icinga_drush_callback($sensor_name = NULL) {

  // If we are provided with sensor name we expect an active check.
  if (!empty($sensor_name)) {
    monitoring_icinga_active_check($sensor_name);
  }

  $results = monitoring_sensor_run_multiple();
  $output = '';

  /** @var SensorResultInterface $result */
  foreach ($results as $result) {
    $message = iconv('utf-8', 'ASCII', truncate_utf8(strip_tags($result->getSensorMessage()), 124, TRUE, TRUE));
    $service_description = monitoring_icinga_service_description($result->getSensorInfo());

    // Map INFO status to OK.
    $status = $result->getSensorStatus();
    if ($status == SensorResultInterface::STATUS_INFO) {
      $status = SensorResultInterface::STATUS_OK;
    }

    // Make sure the output does not have any new lines.
    $output .= preg_replace('/\s+/', ' ', $service_description . '|' . $status . '|' . $message) . '~';

  }
  drush_print($output, 0, NULL, FALSE);
}

/**
 * Prints sensor status message and exits with sensor status code.
 *
 * This callback should be used for active icinga checks where icinga directly
 * invokes the sensor and expects printed output and exit code.
 *
 * @param string $sensor_name
 *   Sensor name to run.
 */
function monitoring_icinga_active_check($sensor_name) {
  $result = monitoring_sensor_run($sensor_name);

  if (empty($result)) {
    drush_print(format_string('Undefined sensor name @name', array('@name' => $sensor_name)));
  }
  else {
    drush_print(iconv('utf-8', 'ASCII', $result->getSensorMessage()));
  }

  drush_set_context('DRUSH_EXECUTION_COMPLETED', TRUE);

  // Map INFO status to OK.
  $status = $result->getSensorStatus();
  if ($status == SensorResultInterface::STATUS_INFO) {
    $status = SensorResultInterface::STATUS_OK;
  }

  $statuses = monitoring_icinga_status_codes();
  exit($statuses[$status]);
}
