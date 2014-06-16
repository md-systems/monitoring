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
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Sensor settings form controller.
 */
class SensorForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $form['#tree'] = TRUE;
    $sensor_info = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $sensor_info->getLabel(),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => t('ID'),
      '#maxlength' => 255,
      '#default_value' => $sensor_info->id(),
      '#description' => t("ID of the sensor"),
      '#required' => TRUE,
      '#disabled' => $sensor_info->isNew(),
      '#machine_name' => array(
        'exists' => 'Drupal\monitoring\Entity\SensorInfo::load',
      ),
    );

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#maxlength' => 255,
      '#default_value' => $sensor_info->getDescription(),
    );

    if ($sensor_info->isNew()) {
      $plugin_types = array();
      foreach (monitoring_sensor_manager()->getDefinitions() as $plugin_id => $definition) {
        $plugin_types[$plugin_id] = $definition['label']->render();
      }
      uasort($plugin_types, 'strnatcasecmp');
      $form['sensor_id'] = array(
        '#type' => 'select',
        '#options' => $plugin_types,
        '#title' => $this->t('Sensor Plugin'),
        '#limit_validation_errors' => array(array('sensor_id')),
        '#submit' => array(array($this, 'submitSelectPlugin')),
        '#required' => TRUE,
        '#executes_submit_callback' => TRUE,
        '#ajax' => array(
          'callback' => array($this, 'updateSelectedPluginType'),
          'wrapper' => 'monitoring-sensor-plugin',
          'method' => 'replace',
        ),
      );

      $form['update'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Select sensor'),
        '#limit_validation_errors' => array(array('sensor_id')),
        '#submit' => array(array($this, 'submitSelectPlugin')),
        '#attributes' => array('class' => array('js-hide')),
      );

    }
    else {
      // Set the sensor object into $form_state to make it available for validate
      // and submit callbacks.
      $form['sensor_id'] = array(
        '#type' => 'textfield',
        '#title' => t('Sensor Plugin'),
        '#maxlength' => 255,
        '#attributes' => array('readonly' => 'readonly'),
        '#default_value' => monitoring_sensor_manager()->getDefinition($sensor_info->sensor_id)['label']->render()
      );
    }

    // If sensor provides settings form, automatically provide settings to
    // enable the sensor.
    $form['status'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#description' => t('Check to have the sensor trigger.'),
      '#default_value' => $sensor_info->status(),
    );

    if (isset($this->entity->sensor_id)) {
      $form['settings'] = array(
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => t('Sensor Settings'),
        '#description' => t("Here you change settings of the sensor."),
        '#prefix' => '<div id="monitoring-sensor-plugin">',
        '#suffix' => '</div>',
      );
      $form['settings'] += (array) $sensor_info->getPlugin()->settingsForm($form['settings'], $form_state);
    }
    else {
      $form['settings'] = array(
        '#type' => 'container',
        '#prefix' => '<div id="monitoring-sensor-plugin">',
        '#suffix' => '</div>',
      );
    }
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * Handles switching the configuration type selector.
   */
  public function updateSelectedPluginType($form, &$form_state) {
    return $form['settings'];
  }

  /**
   * Handles submit call when sensor type is selected.
   */
  public function submitSelectPlugin(array $form, array &$form_state) {
    $this->entity = $this->buildEntity($form, $form_state);
    $form_state['rebuild'] = TRUE;
    // @todo: This is necessary because there are two different instances of the
    //   form object. Core should handle this.
    $form_state['build_info']['callback_object'] = $form_state['controller'];
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);
    /** @var SensorConfigurableInterface $sensor */
    if ($this->entity->isNew()) {
      $plugin = $form_state['values']['sensor_id'];
      $sensor = monitoring_sensor_manager()->createInstance($plugin, array('sensor_info' => $this->entity));
    }
    else {
      $sensor = $this->entity->getPlugin();
    }
    $sensor->settingsFormValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    /** @var Sensor $sensor */
    $sensor_info = $this->entity;
    if ($sensor_info->isNew()) {
      $plugin = $form_state['values']['sensor_id'];
      $sensor = monitoring_sensor_manager()->createInstance($plugin, array('sensor_info' => $this->entity));
      //      $sensor_info->id = $form_state['values'][''];


    }
    drupal_set_message('<pre>'.print_r($form,TRUE).'</pre>');
    drupal_set_message('<pre>'.print_r($form_state['values'],TRUE).'</pre>');
    /*    else {
      $sensor = $sensor_info->getPlugin();
    }
    $new_settings = $form_state['values'];
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
    drupal_set_message($this->t('Sensor settings saved.'));*/
  }
}
