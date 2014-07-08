<?php
/**
 * @file
 * Contains \Drupal\monitoring_multigraph\Form\MultigraphForm.
 */

namespace Drupal\monitoring_multigraph\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\monitoring\Entity\SensorInfo;
use Drupal\monitoring_multigraph\Entity\Multigraph;

/**
 * Multigraph settings form controller.
 */
class MultigraphForm extends EntityForm {

  /**
   * The available sensors that can be selected.
   *
   * @var SensorInfo[] $sensors
   */
  protected $sensors = array();

  /**
   * Construct the form by finding and storing all available sensors.
   */
  public function __construct() {
    // Find sensors that can be included.
    $sensor_ids = \Drupal::entityQuery('monitoring_sensor')
      ->condition('numeric', TRUE)
      ->condition('status', TRUE)
      ->execute();
    $this->sensors = \Drupal::entityManager()
      ->getStorage('monitoring_sensor')
      ->loadMultiple($sensor_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $form['#tree'] = TRUE;
    /** @var Multigraph $multigraph */
    $multigraph = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $multigraph->getLabel(),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => t('ID'),
      '#maxlength' => 255,
      '#default_value' => $multigraph->id(),
      '#description' => t("ID of the multigraph"),
      '#required' => TRUE,
      '#disabled' => !$multigraph->isNew(),
      '#machine_name' => array(
        'exists' => 'Drupal\monitoring_multigraph\Entity\Multigraph::load',
      ),
    );

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#maxlength' => 255,
      '#default_value' => $multigraph->getDescription(),
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    // Create an array suitable for the sensor_add_select element.
    $sensors_options = array();
    foreach ($this->sensors as $sensor) {
      $sensors_options[$sensor->getName()] = $sensor->getLabel();
    }

    // Selector elements for available sensors.
    $form['sensor_add_select'] = array(
      '#type' => 'select',
      '#title' => t('Add sensor'),
      '#options' => $sensors_options,
      '#description' => t('Choose a sensor to add.'),
    );

    $form['sensor_add_button'] = array(
      '#type' => 'submit',
      '#value' => t('Add sensor'),
      '#ajax' => array(
        'wrapper' => 'selected-sensors',
        'callback' => array($this, 'addSensorReplace'),
        'method' => 'replace',
      ),
      '#submit' => array(
        array($this, 'addSensorSubmit'),
      ),
    );

    // Table for included sensors. Let the theme hook format it as a table.
    $form['sensors'] = array(
      '#theme' => 'monitoring_multigraph_sensor_table',
    );

    // Fill the sensors element with form elements.
    foreach ($multigraph->getSensors() as $sensor) {
      $form['sensors'][$sensor->id()] = array(
        '#sensor' => $sensor,
        'name' => array(
          '#type' => 'value',
          '#value' => $sensor->getName(),
        ),
        'weight' => array(
          '#type' => 'weight',
          '#title' => t('Weight'),
          '#title_display' => 'invisible',
        ),
        'label' => array(
          'data' => array(
            '#type' => 'textfield',
            '#default_value' => $sensor->getLabel(),
            '#title' => t('Custom sensor label'),
            '#title_display' => 'invisible',
            '#required' => TRUE,
          ),
        ),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);
  }

  /**
   * Returns the updated 'sensors' form component for replacement by ajax.
   *
   * @param array $form
   *   The updated form structure array.
   * @param array $form_state
   *   The form state structure array.
   *
   * @return array
   *   The updated form component for the selected sensors.
   */
  public function addSensorReplace(array $form, array &$form_state) {
    return $form['sensors'];
  }

  /**
   * Add sensor to entity when 'Add sensor' button is pressed.
   *
   * @param array $form
   *   The form structure array
   * @param array $form_state
   *   The form state structure array.
   */
  public function addSensorSubmit(array $form, array &$form_state) {
    $this->entity = $this->buildEntity($form, $form_state);
    $form_state['rebuild'] = TRUE;

    /** @var Multigraph $multigraph */
    $multigraph = $this->entity;

    // Add any selected sensor to entity.
    if (isset($form_state['values']['sensor_add_select'])) {
      $sensor_name = $form_state['values']['sensor_add_select'];
      $multigraph->addSensor($this->sensors[$sensor_name]);
    }

    // @todo: This is necessary because there are two different instances of the
    //   form object. Core should handle this.
    $form_state['build_info']['callback_object'] = $form_state['controller'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    /** @var Multigraph $multigraph */
    $multigraph = $this->entity;
    $multigraph->save();
    $form_state['redirect_route']['route_name'] = 'monitoring.multigraphs_overview';
    drupal_set_message($this->t('Multigraph settings saved.'));
  }
}
