<?php
/**
 * @file
 * Monitoring sensor settings interface.
 */

namespace Drupal\monitoring\Sensor;

/**
 * Base interface defining implicit operations for a monitoring sensor exposing
 * custom settings.
 */
interface SensorConfigurableInterface {

  /**
   * Gets settings form for a specific sensor.
   *
   * @param $form
   *   Drupal $form structure.
   * @param array $form_state
   *   Drupal $form_state object.
   *
   * @return array
   *   Drupal form structure.
   */
  function settingsForm($form, &$form_state);

  /**
   * Form validator for a sensor settings form.
   *
   * @param $form
   *   Drupal $form structure.
   * @param array $form_state
   *   Drupal $form_state object.
   */
  function settingsFormValidate($form, &$form_state);

}
