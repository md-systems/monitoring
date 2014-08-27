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
 *   description = @Translation("Checks for a specific configuration value."),
 *   addable = TRUE
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

  /**
   * Adds UI for variables config object and key.
   */
  public function settingsForm($form, &$form_state) {
    $form = parent::settingsForm($form, $form_state);
    $settings = $this->info->getSettings();
    $form['config'] = array(
      '#type' => 'textfield',
      '#default_value' => $this->info->getSetting('config')? $this->info->getSetting('config') : '',
      '#autocomplete_route_name' => 'monitoring.config_autocomplete',
      '#maxlength' => 255,
      '#title' => t('Config Object'),
      '#required' => TRUE,
    );
    $form['key'] = array(
      '#type' => 'textfield',
      '#default_value' => $this->info->getSetting('key')? $this->info->getSetting('key') : '',
      '#maxlength' => 255,
      '#title' => t('Key'),
      '#required' => TRUE,
    );
    return $form;
  }
}
