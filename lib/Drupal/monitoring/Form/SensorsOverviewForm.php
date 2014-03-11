<?php
/**
 * @file
 *   Contains \Drupal\monitoring\Form\SensorOverviewForm.
 */

namespace Drupal\monitoring\Form;

use Drupal\Core\Form\FormBase;
use Drupal\monitoring\Sensor\SensorInfo;
use Drupal\monitoring\Sensor\SensorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sensor overview form controller.
 */
class SensorsOverviewForm extends FormBase {

  /**
   * Stores the sensor manager.
   *
   * @var \Drupal\monitoring\Sensor\SensorManager
   */
  protected $sensorManager;

  /**
   * Constructs a \Drupal\monitoring\Form\SensorSettingsForm object.
   *
   * @param \Drupal\monitoring\Sensor\SensorManager $sensor_manager
   *   The sensor manager service.
   */
  public function __construct(SensorManager $sensor_manager) {
    $this->sensorManager = $sensor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('monitoring.sensor_manager')
    );
  }

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
    foreach ($this->sensorManager->getSensorInfo() as $sensor_name => $sensor_info) {
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
        $this->sensorManager->enableSensor($sensor_name);
      }
      else {
        $this->sensorManager->disableSensor($sensor_name);
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
