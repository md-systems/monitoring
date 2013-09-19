<?php
/**
 * @file
 * Contains Drupal\monitoring\Sensor\SensorThresholds
 */

namespace Drupal\monitoring\Sensor;

/**
 * Provides configuration form for Sensor providing thresholds.
 */
abstract class SensorThresholds extends SensorConfigurable implements SensorThresholdsInterface {

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, &$form_state) {

    $form = parent::settingsForm($form, $form_state);
    $thresholds = $this->info->getThresholdsIntervals();

    if (!empty($thresholds)) {
      $form['thresholds'] = array(
        '#type' => 'fieldset',
        '#title' => t('Sensor thresholds'),
        '#description' => t('Here you can set limit values that switch sensor to a given state. <strong>Note that a sensor having the limit value will result in a state change.</strong>'),
      );

      if (isset($form_state['values'][monitoring_sensor_settings_key($this->getSensorName())]['thresholds']['type'])) {
        $type = $form_state['values'][monitoring_sensor_settings_key($this->getSensorName())]['thresholds']['type'];
      }
      else {
        $type = $this->info->getThresholdsType();
      }

      $form['thresholds']['type'] = array(
        '#type' => 'select',
        '#title' => t('Threshold type'),
        '#options' => array(
          'exceeds' => t('Exceeds'),
          'falls' => t('Falls'),
          'inner_interval' => t('Inner interval'),
          'outer_interval' => t('Outer interval'),
        ),
        '#default_value' => $type,
        '#ajax' => array(
          'callback' => 'monitoring_sensor_thresholds_ajax',
          'wrapper' => 'monitoring-sensor-thresholds',
        ),
      );

      $form['thresholds']['intervals'] = array(
        '#prefix' => '<div id="monitoring-sensor-thresholds">',
        '#suffix' => '</div>',
      );

      foreach ($thresholds as $status => $threshold) {

        switch ($type) {
          case 'exceeds':
          case 'falls':
            $form['thresholds']['intervals']['#type'] = 'fieldset';
            $form['thresholds']['intervals']['#title'] = t('Set limit values for individual statuses.');
            $form['thresholds']['intervals'][$status] = array(
              '#type' => 'textfield',
              '#title' => t('Status @status', array('@status' => $status)),
              '#default_value' => is_numeric($threshold) ? $threshold : NULL,
            );
            break;
          case 'inner_interval':
            $form['thresholds']['intervals'][$status]['#type'] = 'fieldset';
            $form['thresholds']['intervals'][$status]['#title'] = t('Set <em>inner</em> interval for status %status', array('%status' => $status));
            $form['thresholds']['intervals'][$status]['#description'] = t('If a sensor value will be inside the defined interval the status will become %status', array('%status' => $status));
            $form['thresholds']['intervals'][$status][0] = array(
              '#type' => 'textfield',
              '#title' => t('From'),
              '#default_value' => isset($threshold[0]) ? $threshold[0] : NULL,
            );
            $form['thresholds']['intervals'][$status][1] = array(
              '#type' => 'textfield',
              '#title' => t('To'),
              '#default_value' => isset($threshold[1]) ? $threshold[1] : NULL,
            );
            break;
          case 'outer_interval':
            $form['thresholds']['intervals'][$status]['#type'] = 'fieldset';
            $form['thresholds']['intervals'][$status]['#title'] = t('Set <em>outer</em> interval for status %status', array('%status' => $status));
            $form['thresholds']['intervals'][$status]['#description'] = t('If a sensor value will be outside the defined interval the status will become %status', array('%status' => $status));
            $form['thresholds']['intervals'][$status][0] = array(
              '#type' => 'textfield',
              '#title' => t('From'),
              '#default_value' => isset($threshold[0]) ? $threshold[0] : NULL,
            );
            $form['thresholds']['intervals'][$status][1] = array(
              '#type' => 'textfield',
              '#title' => t('To'),
              '#default_value' => isset($threshold[1]) ? $threshold[1] : NULL,
            );
            break;
        }
      }
    }

    return $form;
  }
}
