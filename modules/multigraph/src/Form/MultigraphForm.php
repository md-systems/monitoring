<?php
/**
 * @file
 * Contains \Drupal\monitoring_multigraph\Form\MultigraphForm.
 */

namespace Drupal\monitoring_multigraph\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\monitoring\Entity\SensorInfo;
use Drupal\monitoring_multigraph\Entity\Multigraph;

/**
 * Multigraph settings form controller.
 */
class MultigraphForm extends EntityForm {

  /**
   * Construct the form by finding and storing all available sensors.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#tree'] = TRUE;
    /** @var Multigraph $multigraph */
    $multigraph = $this->entity;

    // Find sensors that can be included.
    $sensor_ids = \Drupal::entityQuery('monitoring_sensor')
      ->condition('numeric', TRUE)
      ->condition('status', TRUE)
      ->execute();
    $sensor_ids = array_diff($sensor_ids, $multigraph->getSensorNames());
    ksort($sensor_ids);
    /** @var SensorInfo[] $sensors */
    $sensors = \Drupal::entityManager()
      ->getStorage('monitoring_sensor')
      ->loadMultiple($sensor_ids);

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $multigraph->getLabel(),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('ID'),
      '#maxlength' => 255,
      '#default_value' => $multigraph->id(),
      '#description' => $this->t("ID of the multigraph"),
      '#required' => TRUE,
      '#disabled' => !$multigraph->isNew(),
      '#machine_name' => array(
        'exists' => 'Drupal\monitoring_multigraph\Entity\Multigraph::load',
      ),
    );

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#default_value' => $multigraph->getDescription(),
    );

    // Fieldset for sensor list elements.
    $form['sensor_add'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Sensors'),
      '#prefix' => '<div id="selected-sensors">',
      '#suffix' => '</div>',
    );

    // Create an array suitable for the sensor_add_select element.
    $sensors_options = array();
    foreach ($sensors as $sensor) {
      $sensors_options[$sensor->getName()] = $sensor->getLabel();
    }

    // Select element for available sensors.
    $form['sensor_add']['sensor_add_select'] = array(
      '#type' => 'select',
      '#title' => $this->t('Available sensors'),
      '#options' => $sensors_options,
      '#description' => $this->t('Choose a sensor to add.'),
      '#empty_value' => '',
    );

    $form['sensor_add']['sensor_add_button'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#ajax' => array(
        'wrapper' => 'selected-sensors',
        'callback' => array($this, 'sensorsReplace'),
        'method' => 'replace',
      ),
      '#submit' => array('::addSensorSubmit'),
    );

    // Table for included sensors.
    $form['sensor_add']['sensors'] = array(
      '#type' => 'table',
      '#header' => array(
        'category' => $this->t('Category'),
        'label' => $this->t('Sensor label'),
        'description' => $this->t('Description'),
        'message' => $this->t('Sensor message'),
        'weight' => $this->t('Weight'),
        'operations' => $this->t('Operations'),
      ),
      '#prefix' => '<div id="selected-sensors">',
      '#suffix' => '</div>',
      '#empty' => $this->t(
        'Select and add sensors above to include them in this multigraph.'
      ),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'sensors-table-weight',
        ),
      ),
    );

    // Fill the sensors table with form elements for each sensor.
    foreach ($multigraph->getSensors() as $weight => $sensor) {
      $form['sensor_add']['sensors'][$sensor->id()] = array(
        'category' => array(
          '#markup' => $sensor->getCategory(),
        ),
        'label' => array(
          '#type' => 'textfield',
          '#default_value' => $sensor->getLabel(),
          '#title' => $this->t('Custom sensor label'),
          '#title_display' => 'invisible',
          '#required' => TRUE,
        ),
        'description' => array(
          '#markup' => $sensor->getDescription(),
        ),
        'message' => array(
          '#markup' => monitoring_sensor_run($sensor->id())->getMessage(),
        ),
        'weight' => array(
          '#type' => 'weight',
          '#title' => $this->t('Weight'),
          '#title_display' => 'invisible',
          '#default_value' => $weight,
          '#attributes' => array(
            'class' => array('sensors-table-weight'),
          ),
        ),
        'operations' => array(
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#description' => $this->t('Exclude sensor from multigraph'),
          '#name' => 'remove_' . $sensor->getName(),
          '#ajax' => array(
            'wrapper' => 'selected-sensors',
            'callback' => array($this, 'sensorsReplace'),
            'method' => 'replace',
          ),
          '#submit' => array('::removeSensorSubmit'),
        ),
        '#attributes' => array(
          'class' => array('draggable'),
        ),
      );
    }

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * Returns the updated 'sensors_add' fieldset for replacement by ajax.
   *
   * @param array $form
   *   The updated form structure array.
   * @param FormStateInterface $form_state
   *   The form state structure.
   *
   * @return array
   *   The updated form component for the selected sensors.
   */
  public function sensorsReplace(array $form, FormStateInterface $form_state) {
    return $form['sensor_add'];
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    // Flatten the sensor_add fieldset in the form structure.
    $form_state['values'] += $form_state['values']['sensor_add'];
    unset($form['sensor_add']);
    parent::submit($form, $form_state);
  }

  /**
   * Adds sensor to entity when 'Add sensor' button is pressed.
   *
   * @param array $form
   *   The form structure array
   * @param FormStateInterface $form_state
   *   The form state structure.
   */
  public function addSensorSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    /** @var Multigraph $multigraph */
    $multigraph = $this->entity;

    // Add any selected sensor to entity.
    if (isset($form_state['values']['sensor_add']['sensor_add_select'])) {
      $sensor_name = $form_state['values']['sensor_add']['sensor_add_select'];
      $sensor_label = \Drupal::entityManager()->getStorage('monitoring_sensor')->load($sensor_name)->getLabel();
      $multigraph->addSensor($sensor_name);
      drupal_set_message($this->t('Sensor "@sensor_label" added. You have unsaved changes.', array('@sensor_label' => $sensor_label)), 'warning');
    }
  }

  /**
   * Removes sensor from entity when 'Remove' button is pressed for sensor.
   *
   * @param array $form
   *   The form structure array
   * @param FormStateInterface $form_state
   *   The form state structure.
   */
  public function removeSensorSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    /** @var Multigraph $multigraph */
    $multigraph = $this->entity;

    // Remove sensor as indicated by triggering_element.
    $button_name = $form_state['triggering_element']['#name'];
    $sensor_name = substr($button_name, strlen('remove_'));
    $sensor_label = \Drupal::entityManager()->getStorage('monitoring_sensor')->load($sensor_name)->getLabel();
    $multigraph->removeSensor($sensor_name);
    drupal_set_message($this->t('Sensor "@sensor_label" removed.  You have unsaved changes.', array('@sensor_label' => $sensor_label)), 'warning');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var Multigraph $multigraph */
    $multigraph = $this->entity;

    // Clean entity properties, whose structure was imposed by form array.
    if ($multigraph->sensors) {
      foreach ($multigraph->sensors as &$sensor) {
        unset($sensor['operations']);
      }
    }

    $multigraph->save();
    $form_state->setRedirect('monitoring.multigraphs_overview');
    drupal_set_message($this->t('Multigraph settings saved.'));
  }
}
