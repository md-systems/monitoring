<?php

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Implements hook_monitoring_sensor_info().
 */
function monitoring_test_monitoring_sensor_info() {
  return array(
    'test_sensor_custom' => array(
      'label' => 'Test from custom hook',
      'description' => '',
      'sensor_class' => 'Drupal\monitoring_test\Sensor\Sensors\TestSensor',
      'settings' => array(
        'result_logging' => FALSE,
        'category' => 'Test',
      ),
    ),
    'watchdog_aggregate_test' => array(
      'label' => 'Watchdog aggregate test',
      'description' => 'Test sensor for watchdog aggregate.',
      'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimpleDatabaseAggregator',
      'settings' => array(
        'result_logging' => FALSE,
        'category' => 'Test',
        'thresholds' => array(
          'warning' => 1,
          'critical' => 2,
        ),
        'table' => 'watchdog',
        'conditions' => array(
          array('field' => 'type', 'value' => 'test_watchdog_aggregate_sensor')
        ),
      ),
    ),
    'test_sensor' => array(
      'label' => 'Test sensor',
      'description' => 'Test sensor status',
      'sensor_class' => 'Drupal\monitoring_test\Sensor\Sensors\TestSensor',
      'settings' => array(
        'result_logging' => TRUE,
        'category' => 'Test',
      ),
    ),
    'test_sensor_inner' => array(
      'label' => 'Test sensor inner',
      'description' => 'Test sensor that sets inner error intervals',
      'sensor_class' => 'Drupal\monitoring_test\Sensor\Sensors\TestSensor',
      'settings' => array(
        'result_logging' => TRUE,
        'category' => 'Test',
        'thresholds' => array(
          'type' => 'inner_interval',
          'warning_low' => 1,
          'critical_low' => 4,
          'critical_high' => 6,
          'warning_high' => 9,
        ),
      ),
    ),
    'test_sensor_outer' => array(
      'label' => 'Test sensor outer',
      'description' => 'Test sensor that sets outer error intervals',
      'sensor_class' => 'Drupal\monitoring_test\Sensor\Sensors\TestSensor',
      'settings' => array(
        'result_logging' => TRUE,
        'category' => 'Test',
        'thresholds' => array(
          'type' => 'outer_interval',
          'critical_low' => 60,
          'warning_low' => 70,
          'warning_high' => 80,
          'critical_high' => 90,
        ),
      ),
    ),
    'test_sensor_exceeds' => array(
      'label' => 'Test sensor exceeds',
      'description' => 'Test sensor that sets error interval above give value',
      'sensor_class' => 'Drupal\monitoring_test\Sensor\Sensors\TestSensor',
      'settings' => array(
        'result_logging' => TRUE,
        'category' => 'Test',
        'thresholds' => array(
          'type' => 'exceeds',
          'warning' => 5,
          'critical' => 10,
        ),
      ),
    ),
    'test_sensor_falls' => array(
      'label' => 'Test sensor falls',
      'description' => 'Test sensor that sets error interval below give value',
      'sensor_class' => 'Drupal\monitoring_test\Sensor\Sensors\TestSensor',
      'settings' => array(
        'result_logging' => TRUE,
        'category' => 'Test',
        'thresholds' => array(
          'type' => 'falls',
          'warning' => 10,
          'critical' => 5,
        ),
      ),
    ),
  );
}
