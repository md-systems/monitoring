<?php

namespace Drupal\monitoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\monitoring\Sensor\NonExistingSensorException;
use Drupal\monitoring\Sensor\SensorManager;
use Drupal\monitoring\SensorRunner;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ForceRunController extends ControllerBase {

  /**
   * Stores the sensor manager.
   *
   * @var \Drupal\monitoring\Sensor\SensorManager
   */
  protected $sensorManager;

  /**
   * Constructs a \Drupal\monitoring\Form\SensorSettingsForm object.
   *
   * @param \Drupal\monitoring\Sensor\SensorManager $sensor_manager
   *   The sensor manager service.
   */
  public function __construct(SensorManager $sensor_manager) {
    $this->sensorManager = $sensor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('monitoring.sensor_manager')
    );
  }

  public function forceRunAll() {
    SensorRunner::resetCache();
    drupal_set_message($this->t('Force run of all cached sensors executed.'));
    return $this->redirect('monitoring.sensor_list');
  }

  public function forceRunSensor($sensor_name) {
    try {
      $sensor_info = $this->sensorManager->getSensorInfoByName($sensor_name);
      SensorRunner::resetCache(array($sensor_name));
      drupal_set_message($this->t('Force run of the sensor @name executed.', array('@name' => $sensor_info->getLabel())));
    }
    catch (NonExistingSensorException $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
    return $this->redirect('monitoring.sensor_list');
  }
}
