<?php
/**
 * @file
 * Sensor Extended Info interface.
 */

namespace Drupal\monitoring\Sensor;

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Sensor Extended Info interface.
 */
interface SensorExtendedInfoInterface {

  /**
   * Provide additional info about sensor call.
   *
   * @param SensorResultInterface $result
   *   Sensor result.
   *
   * @return string
   *   Sensor call verbose info.
   */
  function resultVerbose(SensorResultInterface $result);

}
