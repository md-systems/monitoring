<?php
/**
 * @file
 *   Contains \Drupal\monitoring\Form\SensorDetailForm.
 */

namespace Drupal\monitoring\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\monitoring\Sensor\DisabledSensorException;
use Drupal\monitoring\Sensor\NonExistingSensorException;
use Drupal\monitoring\Sensor\SensorManager;
use Drupal\monitoring\SensorRunner;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Sensor detail form controller.
 */
class SensorDetailForm extends EntityForm {

  /**
   * Stores the sensor runner.
   *
   * @var \Drupal\monitoring\SensorRunner
   */
  protected $sensorRunner;

  /**
   * Stores the sensor manager.
   *
   * @var \Drupal\monitoring\Sensor\SensorManager
   */
  protected $sensorManager;

  /**
   * Constructs a \Drupal\monitoring\Form\SensorDetailForm object.
   *
   * @param \Drupal\monitoring\SensorRunner $sensor_runner
   *   The factory for configuration objects.
   * @param \Drupal\monitoring\Sensor\SensorManager $sensor_manager
   *   The sensor manager service.
   */
  public function __construct(SensorRunner $sensor_runner, SensorManager $sensor_manager) {
    $this->sensorRunner = $sensor_runner;
    $this->sensorManager = $sensor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('monitoring.sensor_runner'),
      $container->get('monitoring.sensor_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $sensor_name = '') {
    $form_state['sensor_name'] = $sensor_name;
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $sensor_name = $form_state['sensor_name'];
    try {
      $sensor_info = $this->sensorManager->getSensorInfoByName($sensor_name);
      $results = $this->sensorRunner->runSensors(array($sensor_info), FALSE, TRUE);
      $result = array_shift($results);
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
	'#access' => \Drupal::currentUser()->hasPermission('monitoring force run'),
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
        '#access' => \Drupal::currentUser()->hasPermission('monitoring force run'),
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
        '#access' => \Drupal::currentUser()->hasPermission('monitoring verbose'),
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
  public function validate(array $form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->sensorRunner->resetCache(array($form_state['sensor_name']));
    drupal_set_message(t('Sensor force run executed.'));
  }

  /**
   * Settings form page title callback.
   */
  public function formTitle($sensor_name) {
    if ($sensor_info = $this->sensorManager->getSensorInfoByName($sensor_name)) {
      return $this->t('@label (@category)', array('@category' => $sensor_info->getCategory(), '@label' => $sensor_info->getLabel()));
    }
    return '';
  }
}
