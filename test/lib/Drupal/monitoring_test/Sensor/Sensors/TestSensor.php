<?php

/**
 * @file
 * Test sensor to report status as provided by external arguments.
 */

namespace Drupal\monitoring_test\Sensor\Sensors;

use Drupal\monitoring\Sensor\SensorConfigurable;
use Drupal\monitoring\Sensor\SensorExtendedInfoInterface;
use Drupal\monitoring\Sensor\SensorInfo;
use Drupal\monitoring\Sensor\Thresholds;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\SensorThresholds;


/**
 * Class TestSensor
 */
class TestSensor extends SensorThresholds implements SensorExtendedInfoInterface {

  protected $testSensorResultData;

  function __construct(SensorInfo $info) {

    // If we have testing sensor info, override the one provided by the info
    // hook.
    if ($_sensor_info = variable_get('test_sensor_info')) {
      $info = new SensorInfo($info->getName(), $_sensor_info);
    }

    parent::__construct($info);

    // Load test sensor data which will be used in the runSensor() logic.
    $this->testSensorResultData = variable_get('test_sensor_result_data', array(
      'sensor_status' => NULL,
      'sensor_message'=> NULL,
      'sensor_value' => NULL,
      'sensor_expected_value' => NULL,
      'sensor_exception_message' => NULL,
    ));
  }

  function runSensor(SensorResultInterface $result) {
    // Sleep here for a while as running this sensor may result in 0 execution
    // time.
    usleep(1);

    if (isset($this->testSensorResultData['sensor_exception_message'])) {
      throw new \RuntimeException($this->testSensorResultData['sensor_exception_message']);
    }

    if (isset($this->testSensorResultData['sensor_value'])) {
      $result->setSensorValue($this->testSensorResultData['sensor_value']);
    }

    if (!empty($this->testSensorResultData['sensor_status'])) {
      $result->setSensorStatus($this->testSensorResultData['sensor_status']);
    }

    if (!empty($this->testSensorResultData['sensor_message'])) {
      $result->addSensorStatusMessage($this->testSensorResultData['sensor_message']);
    }
  }

  function sensorVerbose() {
    return t('This is testing sensor that does return same values as you set into test_sensor_data variable.');
  }

  function resultVerbose(SensorResultInterface $result) {
    return 'call debug';
  }
}

/*

search example:

timer_start('search');



    $this->setStartTime(timer_read('search'));
    $this->response = drupal_http_request(url('search/node/search phrase', array('absolute' => TRUE)));
    $this->setEndTime(timer_read('search'));

    $htmlDom = new DOMDocument();
    @$htmlDom->loadHTML($this->response->data);
    $elements = simplexml_import_dom($htmlDom);
    $h2 = $elements->xpath('//div[@id="block-system-main"]/div/h2');

    if ($this->response->code != 200) {
      $this->setSensorStatus(self::SENSOR_STATUS_CRITICAL, 'Search is not accessible');
      return;
    }
    elseif ($h2[0] != t('Your search yielded no results')) {
      $this->setSensorStatus(self::SENSOR_STATUS_WARNING, 'Search does not yield expected results');
    }
    else {
      $this->setSensorStatus(self::SENSOR_STATUS_OK, 'Search accessible');
    }

*/
