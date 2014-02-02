<?php

/**
 * @file
 * Abstract implementation of the SensorInterface.
 */

namespace Drupal\monitoring\Sensor;

/**
 * Abstract implementation of the SensorInterface providing generic
 * sensor functionality. To create a custom sensor use extend this class.
 */
abstract class Sensor implements SensorInterface {

  /**
   * Current sensor info object.
   *
   * @var SensorInfo
   */
  protected $info;

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
