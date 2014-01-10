<?php

/**
 * @file
 * Contains \Drupal\monitoring\Sensor\SensorManager.
 */

namespace Drupal\monitoring\Sensor;

/**
 * Manages sensor definitions and settings.
 */
class SensorManager {

  /**
   * List of sensor definitions.
   *
   * @var \Drupal\monitoring\Sensor\SensorInfo[]
   */
  protected $info;

  /**
   * Returns monitoring sensor info.
   *
   * @return \Drupal\monitoring\Sensor\SensorInfo[]
   *   List of SensorInfo instances.
   */
  public function getSensorInfo() {
    if (empty($this->info)) {
      $this->info = $this->loadSensorInfo();
    }

    return $this->info;
  }

  /**
   * Returns monitoring sensor info for a given sensor.
   *
   * @param string $sensor_name
   *   Sensor id.
   * @param bool $reset
   *   Static cache reset flag.
   *
   * @return \Drupal\monitoring\Sensor\SensorInfo|null
   *   A single SensorInfo instance or NULL if the sensor does not exist.
   */
  public function getSensorInfoByName($sensor_name) {
    $info = $this->getSensorInfo();
    return isset($info[$sensor_name]) ? $info[$sensor_name] : NULL;
  }

  /**
   * Gets sensor info grouped by categories.
   *
   * @todo: The enabled flag is strange, FALSE should return all?
   *
   * @param bool $enabled
   *   Sensor isEnabled flag.
   *
   * @return \Drupal\monitoring\Sensor\SensorInfo[]
   *   Sensor info.
   */
  function getSensorInfoByCategories($enabled = TRUE) {
    $info_by_categories = array();
    foreach ($this->getSensorInfo() as $sensor_name => $sensor_info) {
      if ($sensor_info->isEnabled() != $enabled) {
        continue;
      }

      $info_by_categories[$sensor_info->getCategory()][$sensor_name] = $sensor_info;
    }

    return $info_by_categories;
  }

  /**
   * Reset the static cache
   */
  public function resetCache() {
    $this->info = array();
  }

  /**
   * Enable a sensor.
   *
   * Checks if the sensor is enabled and enables it if not.
   *
   * @param string $sensor_name
   *   Sensor name to be enabled.
   */
  function enableSensor($sensor_name) {
    $sensor_info = $this->getSensorInfoByName($sensor_name);
    if (!$sensor_info->isEnabled()) {
      $settings = monitoring_sensor_settings_get($sensor_name);
      $settings['enabled'] = TRUE;
      monitoring_sensor_settings_save($sensor_name, $settings);
      // @todo the following part is SensorDisappearedSensors specific. We need
      //   to move it into the sensor somehow.
      $available_sensors = variable_get('monitoring_available_sensors', array());
      $available_sensors[$sensor_name]['enabled'] = TRUE;
      $available_sensors[$sensor_name]['name'] = $sensor_name;
      variable_set('monitoring_available_sensors', $available_sensors);
    }
  }

  /**
   * Disable a sensor.
   *
   * Checks if the sensor is enabled and if so it will disable it and remove
   * from the active sensor list.
   *
   * @param string $sensor_name
   *   Sensor name to be disabled.
   */
  function disableSensor($sensor_name) {
    $sensor_info = $this->getSensorInfoByName($sensor_name);
    if ($sensor_info->isEnabled()) {
      $settings = monitoring_sensor_settings_get($sensor_name);
      $settings['enabled'] = FALSE;
      monitoring_sensor_settings_save($sensor_name, $settings);
      // @todo - the following part is SensorDisappearedSensors specific. We need
      // to move it into the sensor somehow.
      $available_sensors = variable_get('monitoring_available_sensors', array());
      $available_sensors[$sensor_name]['enabled'] = FALSE;
      $available_sensors[$sensor_name]['name'] = $sensor_name;
      variable_set('monitoring_available_sensors', $available_sensors);
    }
  }

  /**
   * Loads sensor info from hooks.
   *
   * @return \Drupal\monitoring\Sensor\SensorInfo[]
   *   List of SensorInfo instances.
   */
  protected function loadSensorInfo() {
    $info = array();
    // A module might provide a separate file with sensor definitions. Try to
    // include it prior to checking if a hook exists.
    // @todo: Use hook_hook_info().
    foreach (module_list() as $module) {
      $sensors_file = drupal_get_path('module', $module) . '/' . $module . '.monitoring_sensors.inc';
      if (file_exists($sensors_file)) {
        require_once $sensors_file;
      }
    }

    // Collect sensors info.
    $custom_implementations = module_implements('monitoring_sensor_info');
    foreach (module_list() as $module) {

      // Favor custom implementation.
      if (in_array($module, $custom_implementations)) {
        $result = module_invoke($module, 'monitoring_sensor_info');
        $info = drupal_array_merge_deep($info, $result);
      }
      // If there is no custom implementation try to find local integration.
      elseif (function_exists('monitoring_' . $module . '_monitoring_sensor_info')) {
        $function = 'monitoring_' . $module . '_monitoring_sensor_info';
        $result = $function();
        if (is_array($result)) {
          $info = drupal_array_merge_deep($info, $result);
        }
      }
    }

    // Allow to alter the collected sensors info.
    drupal_alter('monitoring_sensor_info', $info);

    // Merge in saved sensor settings.
    foreach ($info as $key => &$value) {
      // Set default values.
      $value += array(
        'description' => '',
        'result class' => 'Drupal\monitoring\Result\SensorResult',
        'type' => 'numeric',
        'settings' => array(),
      );
      $value['settings'] += array(
        'enabled' => TRUE,
        'caching time' => 0,
        'category' => 'Other',
        'units_label' => NULL,
      );
      $value['settings'] = $this->mergeSettings($key, $value['settings']);
    }

    // Support variable overrides.
    // @todo This will change in https://drupal.org/node/2170955.
    $info = drupal_array_merge_deep($info, variable_get('monitoring_sensor_info', array()));

    // Convert the arrays into SensorInfo objects.
    foreach ($info as $sensor_name => $sensor_info) {
      $info[$sensor_name] = new SensorInfo($sensor_name, $sensor_info);
    }

    return $info;
  }

  /**
   * Merges provided sensor settings with saved settings.
   *
   * @param string $sensor_name
   *   Sensor name.
   * @param array $default_settings
   *   Default sensor settings.
   *
   * @return array
   *   Merged settings.
   */
  function mergeSettings($sensor_name, array $default_settings) {
    $saved_settings = monitoring_sensor_settings_get($sensor_name);
    $default_settings = array_merge($default_settings, $saved_settings);
    return $default_settings;
  }

}
