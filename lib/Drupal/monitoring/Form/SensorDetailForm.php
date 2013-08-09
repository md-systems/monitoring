<?php

namespace Drupal\monitoring\Form;

use Drupal\Core\Form\FormBase;
use Drupal\monitoring\Sensor\DisabledSensorException;
use Drupal\monitoring\Sensor\NonExistingSensorException;
use Drupal\views\Views;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SensorDetailForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sensor_detail_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $sensor_name = '') {
    $form_state['sensor_name'] = $sensor_name;

    try {
      $sensor_info = monitoring_sensor_manager()->getSensorInfoByName($sensor_name);
      $result = monitoring_sensor_run($sensor_info->getName(), FALSE, TRUE);
    }
    catch (DisabledSensorException $e) {
      throw new NotFoundHttpException();
    }
    catch (NonExistingSensorException $e) {
      throw new NotFoundHttpException();
    }

    if ($sensor_info->getDescription()) {
      $form['sensor_info']['description'] = array(
        '#type' => 'item',
        '#title' => $this->t('Description'),
        '#markup' => $sensor_info->getDescription(),
      );
    }

    if ($sensor_info->getCategory()) {
      $form['sensor_info']['category'] = array(
        '#type' => 'item',
        '#title' => $this->t('Category'),
        '#markup' => $sensor_info->getCategory(),
      );
    }

    $form['sensor_result'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Result'),
    );

    $form['sensor_result']['status'] = array(
      '#type' => 'item',
      '#title' => $this->t('Status'),
      '#markup' => $result->getStatusLabel(),
    );

    $form['sensor_result']['message'] = array(
      '#type' => 'item',
      '#title' => $this->t('Message'),
      '#markup' => $result->getMessage(),
    );


    $form['sensor_result']['execution_time'] = array(
      '#type' => 'item',
      '#title' => $this->t('Execution time'),
      '#markup' => $result->getExecutionTime() . 'ms',
    );

    if ($result->isCached()) {
      $form['sensor_result']['cached'] = array(
        '#type' => 'item',
        '#title' => $this->t('Cache information'),
        '#markup' => $this->t('Executed @interval ago, valid for @valid', array('@interval' => \Drupal::service('date')->formatInterval(REQUEST_TIME - $result->getTimestamp()), '@valid' => \Drupal::service('date')->formatInterval($sensor_info->getCachingTime()))),
      );

      $form['sensor_result']['force_run'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Run now'),
        '#access' => user_access('monitoring force run'),
      );
    }
    elseif ($sensor_info->getCachingTime()) {
      $form['sensor_result']['cached'] = array(
        '#type' => 'item',
        '#title' => $this->t('Cache information'),
        '#markup' => $this->t('Executed now, valid for @valid', array('@valid' => \Drupal::service('date')->formatInterval($sensor_info->getCachingTime()))),
      );

      $form['sensor_result']['force_run'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Run again'),
        '#access' => user_access('monitoring force run'),
      );
    }
    else {
      $form['sensor_result']['cached'] = array(
        '#type' => 'item',
        '#title' => $this->t('Cache information'),
        '#markup' => $this->t('Not cached'),
      );
    }

    if ($sensor_info->isExtendedInfo()) {
      $form['sensor_result']['verbose'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Verbose'),
        '#access' => user_access('monitoring verbose'),
      );
      if ($result->isCached()) {
        $form['sensor_result']['verbose']['output'] = array(
          '#type' => 'markup',
          '#markup' => '<p>' . $this->t('Verbose output is not available for cached sensor results. Click force run to see verbose output.') . '</p>',
        );
      }
      elseif ($verbose_output = $result->getVerboseOutput()) {
        $form['sensor_result']['verbose']['output'] = array(
          '#type' => 'markup',
          '#markup' => '<pre>' . $verbose_output . '</pre>',
        );
      }
      else {
        $form['sensor_result']['verbose']['output'] = array(
          '#type' => 'markup',
          '#markup' => '<p>' . $this->t('No verbose output available for this sensor execution.') . '</p>',
        );
      }
    }

    $form['settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#description' => '<pre>' . var_export($sensor_info->getSettings(), TRUE) . '</pre>',
      '#open' => FALSE,
    );

    $view = Views::getView('monitoring_sensor_results');
    if (!empty($view)) {
      $view->initDisplay();
      $output = $view->preview('detail_page_log', array($sensor_info->getName()));
      if (!empty($view->result)) {
        $form['sensor_log'] = array(
          '#type' => 'details',
          '#title' => $this->t('Log'),
          '#open' => FALSE,
        );
        $form['sensor_log']['view'] = $output;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $result = monitoring_sensor_run($form_state['sensor_name'], TRUE);
    if (!empty($result)) {
      drupal_set_message($this->t('Sensor force run executed.'));
    }
    else {
      drupal_set_message($this->t('Error executing sensor force run.'), 'error');
    }
  }

  /**
   * Settings form page title callback.
   */
  public function formTitle($sensor_name) {
    if ($sensor_info = monitoring_sensor_manager()->getSensorInfoByName($sensor_name)) {
      return $this->t('@label (@category)', array('@category' => $sensor_info->getCategory(), '@label' => $sensor_info->getLabel()));
    }
    return '';
  }
}
