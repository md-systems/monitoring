<?php

namespace Drupal\monitoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\monitoring\Sensor\NonExistingSensorException;
use Drupal\monitoring\SensorRunner;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ForceRunController extends ControllerBase {

  public function forceRunAll() {
    SensorRunner::resetCache();
    drupal_set_message($this->t('Force run of all cached sensors executed.'));
    return new RedirectResponse(\Drupal::url('monitoring.sensor_list', array(), array('absolute' => TRUE)));
  }

  public function forceRunSensor($sensor_name) {
    try {
      $sensor_info = monitoring_sensor_manager()->getSensorInfoByName($sensor_name);
      SensorRunner::resetCache(array($sensor_name));
      drupal_set_message($this->t('Force run of the sensor @name executed.', array('@name' => $sensor_info->getLabel())));
    }
    catch (NonExistingSensorException $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
    return new RedirectResponse(\Drupal::url('monitoring.sensor_list', array(), array('absolute' => TRUE)));
  }
}
