<?php
/**
 * @file
 *   Monitoring remote API resource callbacks.
 */

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\NonExistingSensorException;
use Drupal\monitoring\Sensor\SensorInfo;

/**
 * Sensor info resource.
 *
 * @param string $sensor_name
 *   Specific sensor name.
 *
 * @return array
 *   Sensor info data as array.
 */
function monitoring_services_sensor_info($sensor_name) {
  try {
    return monitoring_sensor_manager()->getSensorInfoByName($sensor_name)->toArray();
  }
  catch (NonExistingSensorException $e) {
    services_error($e->getMessage(), 404);
  }
}

/**
 * Sensor info list resource.
 *
 * @return array
 *   List of all sensors info data.
 */
function monitoring_services_sensors_info() {
  $result = array();
  foreach (monitoring_sensor_manager()->getSensorInfo() as $sensor_name => $sensor_info) {
    $result[$sensor_name] = $sensor_info->toArray();
  }

  return $result;
}

/**
 * Sensor result resource.
 *
 * @param string $sensor_name
 *   Specific sensor name.
 *
 * @return array
 *   Single sensor result data.
 */
function monitoring_services_sensor_result($sensor_name) {
  try {
    return monitoring_services_compile_sensor_result(monitoring_sensor_run($sensor_name));
  }
  catch (InvalidArgumentException $e) {
    return services_error($e->getMessage(), 404);
  }
}

/**
 * Sensor result resource.
 *
 * @return array
 *   List of all sensors results data.
 */
function monitoring_services_sensors_results() {
  $result = array();
  foreach (monitoring_sensor_run_multiple() as $sensor_name => $sensor_result) {
    $result[$sensor_name] = monitoring_services_compile_sensor_result($sensor_result);
  }

  return $result;
}

/**
 * Compiles the sensor result.
 *
 * @param SensorResultInterface $sensor_result
 *   Sensor result object.
 *
 * @return array
 *   Compiled sensor result.
 */
function monitoring_services_compile_sensor_result(SensorResultInterface $sensor_result) {
  return array(
    'host' => url('', array('absolute' => TRUE)),
    'sensor' => $sensor_result->getSensorInfo()->getName(),
    'label' => $sensor_result->getSensorInfo()->getLabel(),
    'value_label' => $sensor_result->getSensorInfo()->getValueLabel(),
    'status' => $sensor_result->getSensorStatus(),
    'value' => $sensor_result->getSensorValue(),
    'expected_value' => $sensor_result->getSensorExpectedValue(),
    'metric' => $sensor_result->toNumber(),
    'message' => $sensor_result->getSensorMessage(),
    'timestamp' => $sensor_result->getTimestamp(),
    'execution_time' => $sensor_result->getSensorExecutionTime(),
    'caching_time' => $sensor_result->getSensorInfo()->getCachingTime(),
  );
}
