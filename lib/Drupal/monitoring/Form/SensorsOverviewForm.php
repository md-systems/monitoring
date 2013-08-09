<?php

namespace Drupal\monitoring\Form;

use Drupal\Core\Form\FormBase;
use Drupal\monitoring\Sensor\SensorInfo;

class SensorsOverviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sensor_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $options = array();
    $default_value = array();

    /** @var SensorInfo $sensor_info */
    foreach (monitoring_sensor_info() as $sensor_name => $sensor_info) {
      $row = array(
        'category' => $sensor_info->getCategory(),
        'label' => $sensor_info->getLabel(),
        'description' => $sensor_info->getDescription(),
      );

      $row['status'] = $sensor_info->isEnabled() ? $this->t('Enabled') : $this->t('Disabled');

      $links = array();
      if ($sensor_info->isConfigurable()) {
        $links[] = array('title' => $this->t('Settings'), 'href' => 'admin/config/system/monitoring/sensors/' . $sensor_name,
          'query' => array('destination' => 'admin/config/system/monitoring/sensors'));
      }

      $row['actions'] = array();
      if (!empty($links)) {
        $row['actions']['data'] = array('#type' => 'dropbutton', '#links' => $links);
      }

      $options[$sensor_name] = $row;
      $default_value[$sensor_name] = $sensor_info->isEnabled();
    }

    $header = array(
      'category' => $this->t('Category'),
      'label' => $this->t('Label'),
      'description' => $this->t('Description'),
      'status' => $this->t('Status'),
      'actions' => $this->t('Actions'),
    );

    $form['sensors'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#default_value' => $default_value,
      '#attributes' => array(
        'id' => 'monitoring-sensors-config-overview',
      ),
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    );

    return $form;
  }

  public function submitForm(array &$form, array &$form_state) {
    foreach ($form_state['values']['sensors'] as $sensor_name => $enabled) {
      if ($enabled) {
        monitoring_sensor_manager()->enableSensor($sensor_name);
      }
      else {
        monitoring_sensor_manager()->disableSensor($sensor_name);
      }
    }
    drupal_set_message($this->t('Configuration has been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
  }
}
