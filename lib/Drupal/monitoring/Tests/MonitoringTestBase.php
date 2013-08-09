<?php
/**
 * @file
 * Contains \Drupal\monitoring\Tests\MonitoringTestBase.
 */

namespace Drupal\monitoring\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base class for all monitoring tests.
 */
abstract class MonitoringTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('monitoring', 'monitoring_test');

  /**
   * @var \Drupal\monitoring\Sensor\SensorManager
   */
  protected $sensorManager;

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();
    $this->sensorManager = monitoring_sensor_manager();
    require_once drupal_get_path('module', 'monitoring') . '/monitoring.setup.inc';
  }

  /**
   * Executes a sensor and returns the result.
   *
   * @param string $sensor_name
   *   Name of the sensor to execute.
   *
   * @return \Drupal\monitoring\Result\SensorResultInterface
   *   The sensor result.
   */
  protected function runSensor($sensor_name) {
    // Make sure the sensor is enabled.
    monitoring_sensor_manager()->enableSensor($sensor_name);
    return monitoring_sensor_run($sensor_name, TRUE, TRUE);
  }

}
