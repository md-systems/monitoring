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
   * The Sensor type.
   */
  protected $sensor;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $form['#tree'] = TRUE;
    $sensor_info = $this->entity;
    $form['id'] = array();
    if($this->entity->isNew()) {
      $plugin_types = array();
      foreach(monitoring_sensor_manager()->getDefinitions() as $plugins => $definition) {
        $plugin_types[$plugins] = $definition['label']->render();
      }
      uasort($plugin_types, 'strnatcasecmp');
      $plugin_types = array(
        'select.plugin' => t('- Select -'),
      ) + $plugin_types;
      $form['sensor_id'] = array(
        '#type' => 'select',
        '#options' => $plugin_types,
        '#title' => t('Sensor Plugin'),
        '#default_value' => $plugin_types,
        '#ajax' => array(
          'callback' => array($this,'updateSelectedPluginType'),
          'wrapper' => 'monitoring-sensor-add-form',
          'method' => 'replace',
        )
      );
    }
    else {
      if (!$sensor_info->isConfigurable()) {
	return $form;
      }
      $sensor = $sensor_info->getPlugin();
      // Set the sensor object into $form_state to make it available for validate
      // and submit callbacks.
      $form['sensor_id'] = array(
        '#type' => 'textfield',
        '#title' => t('Sensor Plugin'),
        '#maxlength' => 255,
        '#attributes' => array('readonly' => 'readonly'),
        '#default_value' => monitoring_sensor_manager()->getDefinition($sensor_info->sensor_id)['label']->render()
      );
      $form['id']['#attributes'] = array('readonly' => 'readonly');
    }
    /* $form['submit'] = array(
       '#type' => 'submit',
       '#value' => $this->t('Save2'),
       '#submit' => array('\Drupal\monitoring\SensorForm::submitFunction'),
     ); */
    $form['id'] = array(
      '#type' => 'textfield',
      '#title' => t('ID'),
      '#maxlength' => 255,
      '#default_value' => $sensor_info->id,
      '#description' => t("ID of the config entity"),
      '#required' => TRUE,
    ) + $form['id'];

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $sensor_info->getLabel(),
      '#description' => t("Example: 'website feedback' or 'product information'."),
      '#required' => TRUE,
    );

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#maxlength' => 255,
      '#default_value' => $sensor_info->getDescription(),
      '#description' => t("About the sensor."),
      '#required' => TRUE,
    );

    $form['sensors'] = array(
      '#type' => 'fieldset',
      '#title' => t('Sensor Settings'),
      '#description' => t("Here you change settings of the sensor."),
      '#prefix' => '<div id="monitoring-sensor-plugin"',
      '#suffix' => '</div>',
    );

    if (isset($this->entity->sensor_id)) {
      $form['sensors'] = $sensor->settingsForm($form['sensors'], $form_state);
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
    $this->entity->sensor_id = $form_state['values']['sensor_id'];
    $sensor = monitoring_sensor_manager()->createInstance($this->entity->sensor_id, array('sensor_info' => $this->entity));
    $form_state['rebuild'] = TRUE;
    return $form;
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

  public function submitFunction(array $form, array &$form_state) {
    $form_state['rebuild'] = TRUE;
  }
}
