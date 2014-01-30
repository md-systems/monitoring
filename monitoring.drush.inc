<?php
/**
 * @file
 * Monitoring base drush file.
 */

use Drupal\monitoring\Sensor\DisabledSensorException;
use Drupal\monitoring\Sensor\NonExistingSensorException;

/**
 * Implements hook_drush_command().
 */
function monitoring_drush_command() {
  $items = array();

  $items['monitoring-info'] = array(
    'callback' => 'monitoring_drush_info',
    'description' => 'Displays info about available sensors.',
    'examples' => array(
      'drush monitoring-info' => 'Prints sensors info',
    ),
    'drupal dependencies' => array('monitoring'),
  );

  $items['monitoring-run'] = array(
    'callback' => 'monitoring_drush_run',
    'description' => 'Runs specific sensor and provides verbose data.',
    'arguments' => array(
      'sensor_name' => 'Sensor name to invoke.',
    ),
    'options' => array(
      'verbose' => 'Display verbose information'
    ),
    'examples' => array(
      'drush monitoring-run monitoring_git_dirty_tree' => 'Runs sensor to monitor the git status',
      'drush monitoring-run --verbose monitoring_git_dirty_tree' => 'Runs sensor to monitor the git status and displays verbose information',
    ),
    'drupal dependencies' => array('monitoring'),
  );

  $items['monitoring-set'] = array(
    'callback' => 'monitoring_drush_set',
    'description' => 'Sets a sensor setting value. Possible type is only string.',
    'options' => array(
      'sensor_name' => 'Sensor name to invoke.',
      'settings_key' => 'Sensor settings key to set.',
      'settings_value' => 'Sensor settings value to set.',
    ),
    'examples' => array(
      'drush monitoring-set monitoring_git_dirty_tree enabled 1' => 'Enables monitoring_git_dirty_tree sensor.',
    ),
    'drupal dependencies' => array('monitoring'),
  );

  $items['monitoring-purge-settings'] = array(
    'callback' => 'monitoring_drush_purge_settings',
    'description' => 'Purges settings for a specific sensor, or all sensors.',
    'options' => array(
      'sensor_name' => 'Sensor name for which to purge settings.',
    ),
    'examples' => array(
      'drush monitoring-purge-settings monitoring_git_dirty_tree' => 'Enables monitoring_git_dirty_tree sensor.',
    ),
    'drupal dependencies' => array('monitoring'),
  );

  return $items;
}

/**
 * Provides info about available sensors.
 * @todo - provide more info, maybe also with argument.
 */
function monitoring_drush_info() {
  foreach (monitoring_sensor_info() as $name => $info) {
    drush_print($name);
  }
}

/**
 * Drush callback to run a sensor.
 *
 * @param string $sensor_name
 *   Sensor name to run.
 */
function monitoring_drush_run($sensor_name) {
  try {
    $result = monitoring_sensor_run($sensor_name, TRUE, drush_get_option('verbose'));
  }
  catch (NonExistingSensorException $e) {
    drush_set_error('MONITORING_SENSOR_INVALID_NAME', dt('Sensor "@name" does not exist.', array('@name' => $sensor_name)));
    return 2;
  }
  catch (DisabledSensorException $e) {
    drush_set_error('MONITORING_SENSOR_DISABLED', dt('Sensor "@name" is not enabled.', array('@name' => $sensor_name)));
    return 2;
  }

  $rows = array(
    array(dt('Name'), $result->getSensorName()),
    array(dt('Status'), $result->getSensorStatusLabel()),
    array(dt('Value'), $result->getSensorValue()),
    array(dt('Message'), $result->getSensorMessage()),
    array(dt('Execution time'), $result->getSensorExecutionTime() . 'ms'),
  );

  // Add the verbose output if requested.
  if ($result->getVerboseOutput()) {
    $rows[] = array(dt('Verbose output'), $result->getVerboseOutput());
  }
  drush_print_table($rows);

  // Set the exit status based on the sensor status.
  if ($result->isOk()) {
    return 0;
  }
  elseif ($result->isWarning()) {
    return 1;
  }
  elseif ($result->isCritical()) {
    return 2;
  }
}

/**
 * Drush callback to set sensor setting value.
 *
 * @param string $sensor_name
 *   Sensor name.
 * @param string $setting_key
 *   Sensor setting key.
 * @param string $setting_value
 *   Sensor setting value.
 */
function monitoring_drush_set($sensor_name, $setting_key, $setting_value) {
  monitoring_sensor_settings_save($sensor_name, array($setting_key => $setting_value));
  drush_print(dt('Settings saved'));
}

/**
 * Drush callback to purge sensor settings.
 *
 * @param string $sensor_name
 *   Sensor name for which to purge settings.
 */
function monitoring_drush_purge_settings($sensor_name = NULL) {
  if (empty($sensor_name)) {
    if (drush_confirm(dt('Do you want to purge all sensor settings?'))) {
      $query = db_query('DELETE FROM {variable} WHERE name LIKE :name', array(':name' => 'monitoring_%'));
      drush_print(dt('Affected rows: @rows', array('@rows' => $query->rowCount())));
    }
    return;
  }

  $query = db_query('DELETE FROM {variable} WHERE name = :name', array(':name' => monitoring_sensor_settings_key($sensor_name)));
  drush_print(dt('Affected rows: @rows', array('@rows' => $query->rowCount())));
}
