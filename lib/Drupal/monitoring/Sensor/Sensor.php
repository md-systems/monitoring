<?php
/**
 * @file
 * Contains \Drupal\monitoring\Sensor\Sensor.
 */

namespace Drupal\monitoring\Sensor;

/**
 * Abstract SensorInterface implementation with common behaviour and will be extended by
 * sensor plugins.
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
   * The plugin_id.
   *
   * @var string
   */
  protected $pluginId;
  
  /**
   * The plugin implementation definition.
   *
   * @var array
   */
  protected $pluginDefinition;

  /**
   * Instantiates a sensor object.
   *
   * @param SensorInfo $info
   *   Sensor info object.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  function __construct(SensorInfo $info, $plugin_id, $plugin_definition) {
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
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

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return $this->pluginDefinition;
  }
}
