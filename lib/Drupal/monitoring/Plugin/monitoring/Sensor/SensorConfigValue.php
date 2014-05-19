<?php

/**
 * @file
 * Contains \Drupal\monitoring\Sensor\Sensors\SensorConfigValue
 */

namespace Drupal\monitoring\Plugin\monitoring\Sensors;
use Drupal\monitoring\Sensor\Sensor;

/**
 * Generic sensor that checks for the config value.
 *
 * @MonitoringSensor(
 *   id = "config_value",
 *   label = @Translation("Config Value"),
 *   description = @Translation("Sensor that checks for the config value"))
 *
 */
class SensorConfigValue extends SensorValueComparisonBase, Sensor {

  /**
   * {@inheritdoc}
   */
  protected function getValueDescription() {
    return t('Expected value of config %config', array('%config' => $this->info->getSetting('config') . '->' . $this->info->getSetting('key')));
  }

  /**
   * {@inheritdoc}
   */
  protected function getActualValue() {
    $config = $this->getConfig($this->info->getSetting('config'));;
    return $config->get($this->info->getSetting('key'));
  }

  /**
   * Gets config.
   *
   * @param string $name
   *   Config name.
   *
   * @return \Drupal\Core\Config\Config
   */
  protected function getConfig($name) {
    return $this->getService('config.factory')->get($name);
  }
}
