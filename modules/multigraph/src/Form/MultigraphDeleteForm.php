<?php

/**
 * @file
 * Contains \Drupal\monitoring_multigraph\Form\MultigraphDeleteForm.
 */

namespace Drupal\monitoring_multigraph\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Builds the form to delete a monitoring multigraph.
 */
class MultigraphDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t(
      'Are you sure you want to delete the %name multigraph?',
      array('%name' => $this->entity->getLabel())
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('monitoring.multigraphs_overview');
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
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message(t(
      'The %label multigraph has been deleted.',
      array('%label' => $this->entity->getLabel())
    ));
    $form_state['redirect_route'] = $this->getCancelRoute();
  }
}
