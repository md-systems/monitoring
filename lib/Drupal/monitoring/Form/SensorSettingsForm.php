<?php

namespace Drupal\monitoring\Form;

use Drupal\Core\Form\FormBase;
use Drupal\monitoring\Sensor\NonExistingSensorException;
use Drupal\monitoring\Sensor\Sensor;
use Drupal\monitoring\Sensor\SensorConfigurableInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SensorSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sensor_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $sensor_name = '') {
    try {
      $sensor_info = monitoring_sensor_manager()->getSensorInfoByName($sensor_name);
    }
    catch (NonExistingSensorException $e) {
      throw new NotFoundHttpException();
    }

    if (!$sensor_info->isConfigurable()) {
      return $form;
    }

    $sensor_class = $sensor_info->getSensorClass();
    /** @var SensorConfigurableInterface $sensor */
    $sensor = new $sensor_class($sensor_info);
    // Set the sensor object into $form_state to make it available for validate
    // and submit callbacks.
    $form_state['sensor'] = $sensor;

    $form[$sensor_name] = array(
      '#tree' => TRUE,
    );

    $form[$sensor_name] = $sensor->settingsForm($form[$sensor_name], $form_state);

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    /** @var SensorConfigurableInterface $sensor */
    $sensor = $form_state['sensor'];
    $sensor->settingsFormValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    /** @var Sensor $sensor */
    $sensor = $form_state['sensor'];
    $sensor_name = $sensor->getSensorName();
    monitoring_sensor_settings_save($sensor_name, $form_state['values'][$sensor_name]);
    drupal_set_message($this->t('Sensor settings saved.'));
  }

  /**
   * Settings form page title callback.
   */
  public function formTitle($sensor_name) {
    if ($sensor_info = monitoring_sensor_manager()->getSensorInfoByName($sensor_name)) {
      return $this->t('@label settings (@category)', array('@category' => $sensor_info->getCategory(), '@label' => $sensor_info->getLabel()));
    }
    return '';
  }
}
