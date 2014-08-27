<?php
/**
 * @file
 * Contains \Drupal\monitoring\SensorRunner.
 */

namespace Drupal\monitoring;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Timer;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\DisabledSensorException;
use Drupal\monitoring\Entity\SensorInfo;
use Drupal;
use Drupal\monitoring\Sensor\SensorManager;

/**
 * Instantiate and run requested sensors.
 *
 * @todo more
 */
class SensorRunner {

  /**
   * Sensor manager.
   *
   * @var \Drupal\monitoring\Sensor\SensorManager
   */
  protected $sensorManager;

  /**
   * Internal sensor result cache.
   *
   * @var array
   */
  protected $sensorResultCache = array();

  /**
   * List of sensors info keyed by sensor name that are meant to run.
   *
   * @var \Drupal\monitoring\Entity\SensorInfo[]
   */
  protected $sensorInfo = array();

  /**
   * Flag to force sensor run.
   *
   * @var bool
   */
  protected $forceRun = FALSE;

  /**
   * Flag to switch the collecting of verbose output.
   *
   * @var bool
   */
  protected $verbose = FALSE;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a SensorRunner.
   *
   * @param \Drupal\monitoring\Sensor\SensorManager $sensor_manager
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(SensorManager $sensor_manager, CacheBackendInterface $cache, ConfigFactoryInterface $config_factory) {
    $this->sensorManager = $sensor_manager;
    $this->config = $config_factory->get('monitoring.settings');
    $this->cache = $cache;
  }

  /**
   * Loads available sensor results from cache.
   *
   * @param \Drupal\monitoring\Entity\SensorInfo[] $sensors_info
   *   List of sensor info object that we want to run.
   */
  protected function loadCache(array $sensors_info) {
    $cids = array();
    // Only load sensor caches if they define caching.
    foreach ($sensors_info as $sensor_info) {
      if ($sensor_info->getCachingTime()) {
        $cids[] = $this->getSensorCid($sensor_info->getName());
      }
    }
    if ($cids) {
      foreach ($this->cache->getMultiple($cids) as $cache) {
        $this->sensorResultCache[$cache->data['name']] = $cache->data;
      }
    }
  }

  /**
   * Runs the defined sensableors.
   *
   * @param \Drupal\monitoring\Entity\SensorInfo[] $sensors_info
   *   List of sensor info object that we want to run.
   * @param bool $force
   *   Force sensor execution.
   * @param bool $verbose
   *   Collect verbose info.
   *
   * @return \Drupal\monitoring\Result\SensorResultInterface[]
   *   Array of sensor results.
   *
   * @throws \Drupal\monitoring\Sensor\DisabledSensorException
   *   Thrown if any of the passed sensors is not enabled.
   *
   * @see \Drupal\monitoring\SensorRunner::runSensor()
   */
  public function runSensors(array $sensors_info = array(), $force = FALSE, $verbose = FALSE) {

    $this->verbose = $verbose;
    $this->forceRun = $force;

    if (empty($sensors_info)) {
      $sensors_info = $this->sensorManager->getEnabledSensorInfo();
    }

    $this->loadCache($sensors_info);
    $results = array();
    foreach ($sensors_info as $name => $info) {
      if ($result = $this->runSensor($info)) {
        $results[$name] = $result;
      }
    }
    $this->logResults($results);
    $this->cacheResults($results);
    return $results;
  }

  /**
   * Run a single given sensor.
   *
   * @param SensorInfo $sensor_info
   *   Sensor info
   *
   * @return SensorResultInterface
   *   Sensor result.
   *
   * @throws \Drupal\monitoring\Sensor\DisabledSensorException
   *   Thrown if the passed sensor is not enabled.
   *
   * @see \Drupal\monitoring\Sensor\SensorInterface::runSensor()
   */
  protected function runSensor(SensorInfo $sensor_info) {
    $sensor = $sensor_info->getPlugin();
    // Check if sensor is enabled.
    if (!$sensor->isEnabled()) {
      throw new DisabledSensorException(String::format('Sensor @sensor_name is not enabled and must not be run.', array('@sensor_name' => $sensor_info->getName())));
    }

    $result = $this->getResultObject($sensor_info);

    // In case result is not yet cached run sensor.
    if (!$result->isCached()) {
      Timer::start($sensor_info->getName());
      try {
        $sensor->runSensor($result);
      } catch (\Exception $e) {
        // In case the sensor execution results in an exception, mark it as
        // critical and set the sensor status message.
        $result->setStatus(SensorResultInterface::STATUS_CRITICAL);
        $result->setMessage(get_class($e) . ': ' . $e->getMessage());
        // Log the error to watchdog.
        watchdog_exception('monitoring_exception', $e);
        // @todo Improve logging by e.g. integrating with past or save the
        //   backtrace as part of the sensor verbose output.
      }

      $timer = Timer::stop($sensor_info->getName());
      $result->setExecutionTime($timer['time']);

      // Capture verbose output if requested and if we are able to do so.
      if ($this->verbose && $sensor_info->isExtendedInfo()) {
        $result->setVerboseOutput($sensor->resultVerbose($result));
      }

      try {
        $result->compile();
      }
      catch (\Exception $e) {
        $result->setStatus(SensorResultInterface::STATUS_CRITICAL);
        $result->setMessage(get_class($e) . ': ' . $e->getMessage());
      }
    }


    return $result;
  }

  /**
   * Log results if needed.
   *
   * @param \Drupal\monitoring\Result\SensorResultInterface[] $results
   *   Results to be saved.
   */
  protected function logResults(array $results) {
    foreach ($results as $result) {
      // Skip if the result is cached.
      if ($result->isCached()) {
        continue;
      }

      $old_status = NULL;
      // Try to load the previous log result for this sensor.
      if ($last_result = monitoring_sensor_result_last($result->getSensorName())) {
        $old_status = $last_result->sensor_status->value;
      }

      // Check if we need to log the result.
      if ($this->needsLogging($result, $old_status, $result->getStatus())) {
        monitoring_sensor_result_save($result);
      }
    }
  }

  /**
   * Checks if sensor results should be logged.
   *
   * @param \Drupal\monitoring\Result\SensorResultInterface $result
   *   The sensor result.
   * @param string $old_status
   *   The old sensor status.
   * @param string $new_status
   *   Thew new sensor status.
   *
   * @return bool
   *   TRUE if the result should be logged, FALSE if not.
   */
  protected function needsLogging($result, $old_status = NULL, $new_status = NULL) {
    $log_activity = $result->getSensorInfo()->getSetting('result_logging', FALSE);


    // We log if requested or on status change.
    if ($this->config->get('sensor_call_logging') == 'on_request') {
      return $log_activity || ($old_status != $new_status);
    }

    // We are logging all.
    if ($this->config->get('sensor_call_logging') == 'all') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Cache results if caching applies.
   *
   * @param \Drupal\monitoring\Result\SensorResultInterface[] $results
   *   Results to be cached.
   */
  protected function cacheResults(array $results) {
    // @todo: Cache in a single array, with per item expiration?
    foreach ($results as $result) {
      $definition = $result->getSensorInfo();
      if ($definition->getCachingTime() && !$result->isCached()) {
        $data = array(
          'name' => $result->getSensorName(),
          'sensor_status' => $result->getStatus(),
          'sensor_message' => $result->getMessage(),
          'sensor_expected_value' => $result->getExpectedValue(),
          'sensor_value' => $result->getValue(),
          'execution_time' => $result->getExecutionTime(),
          'timestamp' => $result->getTimestamp(),
        );
        $this->cache->set(
          $this->getSensorCid($result->getSensorName()),
          $data,
          REQUEST_TIME + $definition->getCachingTime(),
          array('monitoring_sensor_result')
        );
      }
    }
  }

  /**
   * Instantiates sensor result object.
   *
   * @param SensorInfo $sensor_info
   *   Sensor info.
   *
   * @return \Drupal\monitoring\Result\SensorResultInterface
   *   Instantiated sensor result object.
   */
  protected function getResultObject(SensorInfo $sensor_info) {
    $result_class = '\Drupal\monitoring\Result\SensorResult';

    if (!$this->forceRun && isset($this->sensorResultCache[$sensor_info->getName()])) {
      $result = new $result_class($sensor_info, $this->sensorResultCache[$sensor_info->getName()]);
    }
    else {
      $result = new $result_class($sensor_info);
    }
    return $result;
  }

  /**
   * Gets sensor cache id.
   *
   * @param string $sensor_name
   *
   * @return string
   *   Cache id.
   */
  protected function getSensorCid($sensor_name) {
    return 'monitoring_sensor_result:' . $sensor_name;
  }

  /**
   * Reset sensor result caches.
   *
   * @param array $sensor_names
   *   (optional) Array of sensors to reset the cache for. An empty array clears
   *   all results, which is the default.
   */
  public function resetCache(array $sensor_names = array()) {
    if (empty($sensor_names)) {
      // No sensor names provided, clear all caches.
      $this->cache->deleteTags(array('monitoring_sensor_result'));
    }
    else {
      $cids = array();
      foreach ($sensor_names as $sensor_name) {
        $cids[] = $this->getSensorCid($sensor_name);
      }
      $this->cache->deleteMultiple($cids);
    }
  }

}
