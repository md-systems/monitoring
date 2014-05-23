<?php

/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\Sensor\SensorConfigValue
 */

namespace Drupal\monitoring\Plugin\monitoring\Sensor;
use Drupal\monitoring\Sensor\Sensor;
use Drupal\monitoring\Sensor\Sensors\SensorValueComparisonBase;

/**
 * Generic sensor that checks for the config value.
 *
 * @Sensor(
 *   id = "config_value",
 *   label = @Translation("Config Value"),
 *   description = @Translation("Checks for a specific configuration value.")
 * )
 *
 */
class SensorConfigValue extends SensorValueComparisonBase {

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
