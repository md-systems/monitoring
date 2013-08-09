<?php
/**
 * @file
 * Contains \Drupal\monitoring\Sensor\Sensor.
 */

namespace Drupal\monitoring\Sensor;

/**
 * Abstract SensorInterface implementation with common behaviour.
 *
 * @todo more
 */
abstract class Sensor implements SensorInterface {

  /**
   * Current sensor info object.
   *
   * @var SensorInfo
   */
  protected $info;
  protected $services = array();

  /**
   * Instantiates a sensor object.
   *
   * @param SensorInfo $info
   *   Sensor info object.
   */
  function __construct(SensorInfo $info) {
    $this->info = $info;
  }

  /**
   * {@inheritdoc}
   */
  public function addService($id, $service) {
    $this->services[$id] = $service;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: Replace with injection
   */
  public function getService($id) {
    return \Drupal::service($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getSensorName() {
    return $this->info->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (boolean) $this->info->getSetting('enabled');
  }

}
