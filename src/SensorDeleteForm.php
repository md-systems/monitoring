<?php

/**
 * @file
 * Contains \Drupal\monitoring\SensorDeleteForm.
 */

namespace Drupal\monitoring;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete a monitoring sensor.
 */
class SensorDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete %name sensor?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('monitoring.sensors_overview_settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message(t('Sensor %label has been deleted.', array('%label' => $this->entity->label())));
    $form_state['redirect_route'] = $this->getCancelRoute();
  }
}
