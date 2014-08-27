<?php
/**
 * @file
 * Contains \Drupal\monitoring\Sensor\SensorThresholds.
 */

namespace Drupal\monitoring\Sensor;

use Drupal\Core\Form\FormStateInterface;

/**
 * Abstract class providing configuration form for Sensor with thresholds.
 *
 * Sensors may provide thresholds that apply by default.
 * Threshold values are validated for sequence.
 */
abstract class SensorThresholds extends SensorConfigurable implements SensorThresholdsInterface {

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, &$form_state) {

    $form = parent::settingsForm($form, $form_state);

    $form['thresholds'] = array(
      '#type' => 'fieldset',
      '#title' => t('Sensor thresholds'),
      '#description' => t('Here you can set limit values that switch the sensor to a given status.'),
      '#prefix' => '<div id="monitoring-sensor-thresholds">',
      '#suffix' => '</div>',
    );

    if (isset($form_state['values'][$this->getSensorName()]['thresholds']['type'])) {
      $type = $form_state['values'][$this->getSensorName()]['thresholds']['type'];
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

    switch ($type) {
      case 'exceeds':
        $form['thresholds']['#description'] = t('The sensor will be set to the corresponding status if the value exceeds the limits.');
        $form['thresholds']['warning'] = array(
          '#type' => 'number',
          '#title' => t('Warning'),
          '#default_value' => $this->info->getThresholdValue('warning'),
        );
        $form['thresholds']['critical'] = array(
          '#type' => 'number',
          '#title' => t('Critical'),
          '#default_value' => $this->info->getThresholdValue('critical'),
        );
        break;
      case 'falls':
        $form['thresholds']['#description'] = t('The sensor will be set to the corresponding status if the value falls below the limits.');
        $form['thresholds']['warning'] = array(
          '#type' => 'number',
          '#title' => t('Warning'),
          '#default_value' => $this->info->getThresholdValue('warning'),
        );
        $form['thresholds']['critical'] = array(
          '#type' => 'number',
          '#title' => t('Critical'),
          '#default_value' => $this->info->getThresholdValue('critical'),
        );
        break;
      case 'inner_interval':
        $form['thresholds']['#description'] = t('The sensor will be set to the corresponding status if the value is within the limits.');
        $form['thresholds']['warning_low'] = array(
          '#type' => 'number',
          '#title' => t('Warning low'),
          '#default_value' => $this->info->getThresholdValue('warning_low'),
        );
        $form['thresholds']['warning_high'] = array(
          '#type' => 'number',
          '#title' => t('Warning high'),
          '#default_value' => $this->info->getThresholdValue('warning_high'),
        );
        $form['thresholds']['critical_low'] = array(
          '#type' => 'number',
          '#title' => t('Critical low'),
          '#default_value' => $this->info->getThresholdValue('critical_low'),
        );
        $form['thresholds']['critical_high'] = array(
          '#type' => 'number',
          '#title' => t('Critical high'),
          '#default_value' => $this->info->getThresholdValue('critical_high'),
        );
        break;
      case 'outer_interval':
        $form['thresholds']['#description'] = t('The sensor will be set to the corresponding status if the value is outside of the limits.');
        $form['thresholds']['warning_low'] = array(
          '#type' => 'number',
          '#title' => t('Warning low'),
          '#default_value' => $this->info->getThresholdValue('warning_low'),
        );
        $form['thresholds']['warning_high'] = array(
          '#type' => 'number',
          '#title' => t('Warning high'),
          '#default_value' => $this->info->getThresholdValue('warning_high'),
        );
        $form['thresholds']['critical_low'] = array(
          '#type' => 'number',
          '#title' => t('Critical low'),
          '#default_value' => $this->info->getThresholdValue('critical_low'),
        );
        $form['thresholds']['critical_high'] = array(
          '#type' => 'number',
          '#title' => t('Critical high'),
          '#default_value' => $this->info->getThresholdValue('critical_high'),
        );
        break;
    }

    return $form;
  }

  /**
   * Sets a form error for the given threshold key.
   *
   * @param string $threshold_key
   *   Key of the threshold value form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Drupal form state object.
   * @param string $message
   *   The validation message.
   */
  protected function setFormError($threshold_key, FormStateInterface $form_state, $message) {
    $form_state->setErrorByName($this->info->getName() . '][thresholds][' . $threshold_key, $message);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValidate($form, &$form_state) {
    $form_key = $this->info->getName();
    $values = $form_state['values']['settings']['thresholds'];
    $type = $values['type'];

    switch ($type) {
      case 'exceeds':
        if (!empty($values['warning']) && !empty($values['critical']) && $values['warning'] >= $values['critical']) {
          $this->setFormError('warning', $form_state, t('Warning must be lower than critical or empty.'));
        }
        break;
      case 'falls':
        if (!empty($values['warning']) && !empty($values['critical']) && $values['warning'] <= $values['critical']) {
          $this->setFormError('warning', $form_state, t('Warning must be higher than critical or empty.'));
        }
        break;
      case 'inner_interval':
        if (empty($values['warning_low']) && !empty($values['warning_high']) || !empty($values['warning_low']) && empty($values['warning_high'])) {
          $this->setFormError('warning_low', $form_state, t('Either both warning values must be provided or none.'));
        }
        elseif (empty($values['critical_low']) && !empty($values['critical_high']) || !empty($values['critical_low']) && empty($values['critical_high'])) {
          $this->setFormError('critical_low', $form_state, t('Either both critical values must be provided or none.'));
        }
        elseif (!empty($values['warning_low']) && !empty($values['warning_high']) && $values['warning_low'] >= $values['warning_high']) {
          $this->setFormError('warning_low', $form_state, t('Warning low must be lower than warning high or empty.'));
        }
        elseif (!empty($values['critical_low']) && !empty($values['critical_high']) && $values['critical_low'] >= $values['critical_high']) {
          $this->setFormError('warning_low', $form_state, t('Critical low must be lower than critical high or empty.'));
        }
        elseif (!empty($values['warning_low']) && !empty($values['critical_low']) && $values['warning_low'] >= $values['critical_low']) {
          $this->setFormError('warning_low', $form_state, t('Warning low must be lower than critical low or empty.'));
        }
        elseif (!empty($values['warning_high']) && !empty($values['critical_high']) && $values['warning_high'] <= $values['critical_high']) {
          $this->setFormError('warning_high', $form_state, t('Warning high must be higher than critical high or empty.'));
        }
        break;
      case 'outer_interval':
        if (empty($values['warning_low']) && !empty($values['warning_high']) || !empty($values['warning_low']) && empty($values['warning_high'])) {
          $this->setFormError('warning_low', $form_state, t('Either both warning values must be provided or none.'));
        }
        elseif (empty($values['critical_low']) && !empty($values['critical_high']) || !empty($values['critical_low']) && empty($values['critical_high'])) {
          $this->setFormError('critical_low', $form_state, t('Either both critical values must be provided or none.'));
        }
        elseif (!empty($values['warning_low']) && !empty($values['warning_high']) && $values['warning_low'] >= $values['warning_high']) {
          $this->setFormError('warning_low', $form_state, t('Warning low must be lower than warning high or empty.'));
        }
        elseif (!empty($values['critical_low']) && !empty($values['critical_high']) && $values['critical_low'] >= $values['critical_high']) {
          $this->setFormError('warning_low', $form_state, t('Critical low must be lower than critical high or empty.'));
        }
        elseif (!empty($values['warning_low']) && !empty($values['critical_low']) && $values['warning_low'] <= $values['critical_low']) {
          $this->setFormError('warning_low', $form_state, t('Warning low must be higher than critical low or empty.'));
        }
        elseif (!empty($values['warning_high']) && !empty($values['critical_high']) && $values['warning_high'] >= $values['critical_high']) {
          $this->setFormError('warning_high', $form_state, t('Warning high must be lower than critical high or empty.'));
        }
        break;
    }
  }
}
