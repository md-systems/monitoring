<?php
/**
 * @file
 * Contains Drupal\monitoring\Sensor\SensorThresholds
 */

namespace Drupal\monitoring\Sensor;

use Drupal\monitoring\Result\SensorResultInterface;

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
        '#description' => t('Here you can set limit values that switch sensor to a given state.'),
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
            $form['thresholds']['intervals'][$status][0] = array(
              '#type' => 'textfield',
              '#title' => t('Status @status', array('@status' => $status)),
              '#default_value' => is_numeric($threshold) ? $threshold : NULL,
              '#element_validate' => array('element_validate_number'),
            );
            $form['thresholds']['intervals'][$status][1] = array(
              '#type' => 'hidden',
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
              '#element_validate' => array('element_validate_number'),
            );
            $form['thresholds']['intervals'][$status][1] = array(
              '#type' => 'textfield',
              '#title' => t('To'),
              '#default_value' => isset($threshold[1]) ? $threshold[1] : NULL,
              '#element_validate' => array('element_validate_number'),
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
              '#element_validate' => array('element_validate_number'),
            );
            $form['thresholds']['intervals'][$status][1] = array(
              '#type' => 'textfield',
              '#title' => t('To'),
              '#default_value' => isset($threshold[1]) ? $threshold[1] : NULL,
              '#element_validate' => array('element_validate_number'),
            );
            break;
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValidate($form, &$form_state) {
    $thresholds = $this->info->getThresholdsIntervals();
    $form_key = monitoring_sensor_settings_key($this->info->getName());
    $last_highest_state = array();
    $type = $form_state['values'][$form_key]['thresholds']['type'];
    foreach ($thresholds as $status => $threshold) {
      if (in_array($type, array('inner_interval', 'outer_interval'))) {
        $values = $form_state['values'][$form_key]['thresholds']['intervals'][$status];
        if (!empty($values[0]) && !empty($values[1]) &&
          $values[0] >= $values[1]) {
          form_set_error($form_key, t('Interval <em>FROM</em> for !status has to be smaller than <em>TO</em>.', array('!status' => $status)));
        }
        if (count($last_highest_state) > 0) {
          if ($type == 'inner_interval') {
            if (!empty($values[0]) && !empty($values[1]) &&
              !empty($last_highest_state['values'][0]) && !empty($last_highest_state['values'][1]) &&
              ($values[0] > $last_highest_state['values'][0] ||
                $values[1] < $last_highest_state['values'][1])) {
              form_set_error($form_key, t('@outside threshold has to be inside @inside.', array('@outside' => $last_highest_state['status'], '@inside' => $status)));
            }
          }
          else if ($type == 'outer_interval') {
            if (!empty($values[0]) && !empty($values[1]) &&
              !empty($last_highest_state['values'][0]) && !empty($last_highest_state['values'][1]) &&
              ($values[0] < $last_highest_state['values'][0] ||
                $values[1] > $last_highest_state['values'][1])) {
              form_set_error($form_key, t('@outside threshold has to be inside @inside.', array('@inside' => $last_highest_state['status'], '@outside' => $status)));
            }
          }
        }
        $last_highest_state['status'] = $status;
        $last_highest_state['values'] = $values;
      }
      else {
        $form_state['values'][$form_key]['thresholds']['intervals'][$status] = reset($form_state['values'][$form_key]['thresholds']['intervals'][$status]);

        if (!empty($last_highest_state['value'])) {
          if ($type == 'falls') {
            if ($form_state['values'][$form_key]['thresholds']['intervals'][$status] <= $last_highest_state['value']) {
              form_set_error($form_key, t('@bigger has to be smaller than @inside.', array('@inside' => $status, '@bigger' => $last_highest_state['status'])));
            }
          }
          else {
            if ($form_state['values'][$form_key]['thresholds']['intervals'][$status] >= $last_highest_state['value']) {
              form_set_error($form_key, t('@inside has to be bigger than @bigger.', array('@inside' => $last_highest_state['status'], '@bigger' => $status)));
            }
          }
        }
        $last_highest_state['status'] = $status;
        $last_highest_state['value'] = $form_state['values'][$form_key]['thresholds']['intervals'][$status];
      }
    }
  }
}
