<?php
/**
 * @file
 * Contains Drupal\monitoring\Sensor\Sensors\SensorEnabledModules
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\SensorConfigurable;

/**
 * Monitoring installed modules.
 */
class SensorEnabledModules extends SensorConfigurable {


  /**
   * {@inheritdoc}
   */
  function settingsForm($form, &$form_state) {
    $form = parent::settingsForm($form, $form_state);

    module_load_include('inc', 'system', 'system.admin');

    $form['allow_additional'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow additional modules to be enabled'),
      '#description' => t('If checked the additional modules being enabled will not be considered as an error state.'),
      '#default_value' => $this->info->getSetting('allow_additional'),
    );

    // Get current list of modules.
    $files = system_rebuild_module_data();

    // Remove hidden modules from display list.
    $visible_files = $files;
    foreach ($visible_files as $filename => $file) {
      if (!empty($file->info['hidden'])) {
        unset($visible_files[$filename]);
      }
    }

    uasort($visible_files, 'system_sort_modules_by_info_name');

    $modules = array();
    foreach ($visible_files as $module) {
      $modules[$module->name] = $module->info['name'];
    }

    $default_value = $this->info->getSetting('modules');

    if (empty($default_value)) {
      $default_value = module_list();
      $form['preselect_info'] = array(
        '#type' => 'markup',
        '#markup' => '<div class="messages warning">' . t('Note that preselected modules are not actual settings of this sensor. To have the preselection become the settings you need to confirm the selection by saving the form values.') . '</div>',
      );
    }

    $form['modules'] = array(
      '#type' => 'checkboxes',
      '#options' => $modules,
      '#title' => t('Modules expected to be enabled'),
      '#description' => t('Check all modules that are supposed to be enabled.'),
      '#default_value' => $default_value,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function runSensor(SensorResultInterface $result) {

    $result->setSensorExpectedValue(0);
    $delta = 0;

    $enabled_modules = module_list();
    $expected_modules = array_filter($this->info->getSetting('modules'));

    // Check for modules not being installed but expected.
    $non_installed_modules = array_diff($expected_modules, $enabled_modules);
    if (!empty($non_installed_modules)) {
      $delta += count($non_installed_modules);
      $result->addSensorStatusMessage('Following modules are expected to be installed: @modules', array('@modules' => implode(', ', $non_installed_modules)));
    }

    // In case we do not allow additional modules check for modules installed
    // but not expected.
    $unexpected_modules = array_diff($enabled_modules, $expected_modules);
    if (!$this->info->getSetting('allow_additional') && !empty($unexpected_modules)) {
      $delta += count($unexpected_modules);
      $result->addSensorStatusMessage('Following modules are NOT expected to be installed: @modules', array('@modules' => implode(', ', $unexpected_modules)));
    }

    $result->setSensorValue($delta);
  }
}
