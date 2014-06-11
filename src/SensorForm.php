<?php
/**
 * @file
 *   Contains \Drupal\monitoring\SensorForm.
 */

namespace Drupal\monitoring;

use Drupal\Core\Entity\EntityForm;
use Drupal\monitoring\Sensor\NonExistingSensorException;
use Drupal\monitoring\Sensor\Sensor;
use Drupal\monitoring\Sensor\SensorConfigurableInterface;
use Drupal\monitoring\Sensor\SensorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Sensor settings form controller.
 */
class SensorForm extends EntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $sensor_info = $this->entity;
    $sensor_name = $sensor_info->getName();
    if (!$sensor_info->isConfigurable()) {
      return $form;
    }
    $sensor = $sensor_info->getPlugin();
    // Set the sensor object into $form_state to make it available for validate
    // and submit callbacks.
    $form_state['sensor'] = $sensor;

    $form[$sensor_name] = array(
      '#tree' => TRUE,
    );
  $form[$sensor_name]['plugin'] = array(
      '#type' => 'textfield',
      '#title' => t('Sensor Plugin'), 
      '#maxlength' => 255,
      '#attributes' => array('readonly' => 'readonly'),
      '#default_value' => $sensor_info->sensor_id
    );
    $form[$sensor_name]['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $sensor_info->getLabel(),
      '#description' => t("Example: 'website feedback' or 'product information'."),
      '#required' => TRUE,
    );
    $form[$sensor_name]['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#maxlength' => 255,
      '#default_value' => $sensor_info->getDescription(),
      '#description' => t("About the sensor."),
      '#required' => TRUE,
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
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);
    /** @var SensorConfigurableInterface $sensor */
    $sensor = $form_state['sensor'];
    $sensor->settingsFormValidate($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    /** @var Sensor $sensor */
    $sensor = $form_state['sensor'];
    $sensor_name = $sensor->getSensorName();
    $sensor_info = $this->entity;
    $new_settings = $form_state['values'][$sensor_name];
    $sensor_info->status = $new_settings['enabled'];
    $sensor_info->label = $new_settings['label'];
    $sensor_info->description = $new_settings['description'];
    $settings = $sensor_info->getSettings();
    foreach($new_settings as $key => $value) {
      if(isset($settings[$key])) {
	$settings[$key] = $value;
      }
    }
    $sensor_info->settings = $settings;
    $sensor_info->save();
    $form_state['redirect_route']['route_name'] = 'monitoring.sensors_overview_settings';
  }

}
