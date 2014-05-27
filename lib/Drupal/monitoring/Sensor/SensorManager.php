<?php
/**
 * @file
 * Contains \Drupal\monitoring\Sensor\SensorManager.
 */

namespace Drupal\monitoring\Sensor;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\monitoring\SensorRunner;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\monitoring\Entity\SensorInfo;

/**
 * Manages sensor definitions and settings.
 *
 * Provides list of enabled sensors.
 * Sensors can be listed by category.
 *
 * Maintains a (non persistent) info cache.
 * Enables and disables sensors.
 *
 */
class SensorManager extends DefaultPluginManager {

  /**
   * List of sensor definitions.
   *
   * @var \Drupal\monitoring\Sensor\SensorInfo[]
   */
  protected $info;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructes a sensor manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config) {
    parent::__construct('Plugin/monitoring/Sensor', $namespaces, $module_handler, 'Drupal\monitoring\Annotation\Sensor');
    $this->alterInfo('block');
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, $language_manager, 'monitoring_sensor_plugins');
    $this->config = $config;
  }
  
  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    // Configuration contains sensor_info object. Extracting
    // it to use for sensor object creation.
    $sensor_info = $configuration['sensor_info'];
    $definition = $this->getDefinition($plugin_id);
    // Sensor class from the sensor definition.
    $class = $definition['class'];
    // Creating instance of the sensor. Refer Sensor.php for arguments.
    return new $class($sensor_info, $plugin_id, $definition);
  }
  
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
   * Returns monitoring sensor info for enabled sensors.
   *
   * @return \Drupal\monitoring\Sensor\SensorInfo[]
   *   List of SensorInfo instances.
   */
  public function getEnabledSensorInfo() {
    $enabled_sensors = array();
    foreach ($this->getSensorInfo() as $sensor_info) {
      if ($sensor_info->isEnabled()) {
        $enabled_sensors[$sensor_info->getName()] = $sensor_info;
      }
    }
    return $enabled_sensors;
  }

  /**
   * Returns monitoring sensor info for a given sensor.
   *
   * @param string $sensor_name
   *   Sensor id.
   *
   * @return \Drupal\monitoring\Sensor\SensorInfo
   *   A single SensorInfo instance.
   *
   * @throws \Drupal\monitoring\Sensor\NonExistingSensorException
   *   Thrown if the requested sensor does not exist.
   */
  public function getSensorInfoByName($sensor_name) {
    $info = $this->getSensorInfo();
    if (isset($info[$sensor_name])) {
      return $info[$sensor_name];
    }
    throw new NonExistingSensorException(String::format('Sensor @sensor_name does not exist', array('@sensor_name' => $sensor_name)));
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
  public function getSensorInfoByCategories($enabled = TRUE) {
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
   * Saves sensor settings.
   *
   * @param string $sensor_name
   *   The sensor name.
   * @param $settings
   *   Settings that should be saved.
   */
  public function saveSettings($sensor_name, array $settings) {
    $config = $this->config->get('monitoring.settings');
    $saved_settings = (array) $config->get($sensor_name);
    foreach ($settings as $key => $value) {
      $saved_settings[$key] = $value;
    }

    $config->set($sensor_name, $saved_settings)->save();
    // After settings save reset the cache.
    $this->resetCache();
    // Changed settings might affect the sensor result.
    \Drupal::service('monitoring.sensor_runner')->resetCache(array($sensor_name));
  }

  /**
   * Enable a sensor.
   *
   * Checks if the sensor is enabled and enables it if not.
   *
   * @param string $sensor_name
   *   Sensor name to be enabled.
   *
   * @throws \Drupal\monitoring\Sensor\NonExistingSensorException
   *   Thrown if the requested sensor does not exist.
   */
  public function enableSensor($sensor_name) {
    $sensor_info = $this->getSensorInfoByName($sensor_name);
    if (!$sensor_info->isEnabled()) {
      $this->saveSettings($sensor_name, array('enabled' => TRUE));
      $available_sensors = \Drupal::state()->get('monitoring.available_sensors', array());

      if (!isset($available_sensors[$sensor_name])) {
        // Use the watchdog message as the disappeared sensor does when new
        // sensors are detected.
        watchdog('monitoring', '@count new sensor/s added: @names',
          array('@count' => 1, '@names' => $sensor_name));
      }

      $available_sensors[$sensor_name]['enabled'] = TRUE;
      $available_sensors[$sensor_name]['name'] = $sensor_name;
      \Drupal::state()->set('monitoring.available_sensors', $available_sensors);
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
   *
   * @throws \Drupal\monitoring\Sensor\NonExistingSensorException
   *   Thrown if the requested sensor does not exist.
   */
  public function disableSensor($sensor_name) {
    $sensor_info = $this->getSensorInfoByName($sensor_name);
    if ($sensor_info->isEnabled()) {
      $this->saveSettings($sensor_name, array('enabled' => FALSE));
      $available_sensors = \Drupal::state()->get('monitoring.available_sensors', array());
      $available_sensors[$sensor_name]['enabled'] = FALSE;
      $available_sensors[$sensor_name]['name'] = $sensor_name;
      \Drupal::state()->set('monitoring.available_sensors', $available_sensors);
    }
  }

  /**
   * Callback for uasort() to order sensors by category and label.
   *
   * @param \Drupal\monitoring\Sensor\SensorInfo $a
   *   1st Object to compare with.
   *
   * @param \Drupal\monitoring\Sensor\SensorInfo $b
   *   2nd Object to compare with.
   *
   * @return int
   *   Sort order of the passed in SensorInfo objects.
   */
  public static function orderSensorInfo(SensorInfo $a, SensorInfo $b) {
    // Checks whether both labels and categories are equal.
    if ($a->getLabel() == $b->getLabel() && $a->getCategory() == $b->getCategory()) {
      return 0;
    }
    // If the categories are not equal, their order is determined.
    elseif ($a->getCategory() != $b->getCategory()) {
      return ($a->getCategory() < $b->getCategory()) ? -1 : 1;
    }
    // In the end, the label's order is determined.
    return ($a->getLabel() < $b->getLabel()) ? -1 : 1;
  }

  /**
   * Loads sensor info from hooks.
   *
   * Instantiates a SensorInfo for each sensor with merged settings.
   * Creates also instances for disabled sensors.
   *
   * @return \Drupal\monitoring\Sensor\SensorInfo[]
   *   List of SensorInfo instances.
   *
   * @see hook_monitoring_sensor_info()
   * @see hook_monitoring_sensor_info_alter()
   */
  protected function loadSensorInfo() {
    $info = array();
    // A module might provide a separate file with sensor definitions. Try to
    // include it prior to checking if a hook exists.
    // @todo: Use hook_hook_info().
    foreach (array_keys($this->moduleHandler->getModuleList()) as $module) {
      $sensors_file = drupal_get_path('module', $module) . '/' . $module . '.monitoring_sensors.inc';
      if (file_exists($sensors_file)) {
        require_once $sensors_file;
      }
    }

    // Collect sensors info.
    $custom_implementations = $this->moduleHandler->getImplementations('monitoring_sensor_info');
    foreach (array_keys($this->moduleHandler->getModuleList()) as $module) {
      // Favor custom implementation.
      if (in_array($module, $custom_implementations)) {
        $result = $this->moduleHandler->invoke($module, 'monitoring_sensor_info');
        $info = NestedArray::mergeDeep($info, $result);
      }
      // If there is no custom implementation try to find local integration.
      elseif (function_exists('monitoring_' . $module . '_monitoring_sensor_info')) {
        $function = 'monitoring_' . $module . '_monitoring_sensor_info';
        $result = $function();
        if (is_array($result)) {
          $info = NestedArray::mergeDeep($info, $result);
        }
      }
    }

    // Allow to alter the collected sensors info.
    $this->moduleHandler->alter('monitoring_sensor_info', $info);

    // Merge in saved sensor settings.
    foreach ($info as $key => &$value) {
      // Set default values.
      $value += array(
        'description' => '',
        'result_class' => 'Drupal\monitoring\Result\SensorResult',
        'numeric' => TRUE,
        'value_label' => NULL,
        'settings' => array(),
      );
      $value['settings'] += array(
        'enabled' => TRUE,
        'caching_time' => 0,
        'category' => 'Other',
      );
      $value['settings'] = $this->mergeSettings($key, $value['settings']);
    }

    $info = NestedArray::mergeDeep($info, (array)$this->config->get('monitoring.sensor_info')->get());

    // Convert the arrays into SensorInfo objects.
    foreach ($info as $sensor_name => $sensor_info) {
      $info[$sensor_name] = new SensorInfo($sensor_name, $sensor_info);
    }

    // Sort the sensors by category and label.
    uasort($info, "\Drupal\monitoring\Sensor\SensorManager::orderSensorInfo");

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
  protected function mergeSettings($sensor_name, array $default_settings) {
    $saved_settings = (array)$this->config->get('monitoring.settings')->get($sensor_name);
    return $this->mergeSettingsArrays(array($default_settings, $saved_settings));
  }

  /**
   * Merges settings arrays.
   *
   * Based on drupal_array_merge_deep_array() but with the additional special
   * case that flat arrays (arrays that don't have other arrays as values)
   * are replaced, not merged.
   *
   * That allows to expose settings forms for multiple values that can override
   * the default configuration.
   *
   * @param $arrays
   *   List of arrays to merge, later arrays replace keys in the previous.
   *
   * @return array
   *   The merged array.
   */
  protected function mergeSettingsArrays($arrays) {
    $result = array();

    foreach ($arrays as $array) {
      foreach ($array as $key => $value) {
        // Renumber integer keys as array_merge_recursive() does. Note that PHP
        // automatically converts array keys that are integer strings (e.g., '1')
        // to integers.
        if (is_integer($key)) {
          $result[] = $value;
        }
        // Check if we have an array and the first array is flat.
        elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value) && $this->isFlatArray($result[$key]))  {
          $result[$key] = $value;
        }
        // Recurse when both values are arrays.
        elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
          $result[$key] = $this->mergeSettingsArrays(array($result[$key], $value));
        }
        // Otherwise, use the latter value, overriding any previous value.
        else {
          $result[$key] = $value;
        }
      }
    }
    return $result;
  }

  /**
   * Returns if an array is flat.
   *
   * @param $array
   *   The array to check.
   *
   * @return bool
   *   TRUE if the array has no values that are arrays again.
   */
  protected function isFlatArray($array) {
    foreach ($array as $value) {
      if (is_array($value)) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
