<?php
/**
 * @file
 * Define default sensors for core and contrib modules.
 *
 * Default sensors provide sensors for core and contrib modules with
 * monitoring_MODULE_monitoring_sensor_info()
 *
 * The default integration for MODULE is omitted if a module implements
 * hook_monitoring_sensor_info() on its own.
 *
 * @see hook_monitoring_sensor_info()
 */

use Drupal\Component\Utility\String;

/**
 * Implements monitoring_MODULE_monitoring_sensor_info().
 *
 * Module: system
 */
function monitoring_system_monitoring_sensor_info() {

  // === Queue === //

  $queues = array_keys(Drupal::moduleHandler()->invokeAll('queue_info'));

  foreach ($queues as $queue) {
    $info['core_queue_' . $queue] = array(
      'label' => String::checkPlain($queue),
      'description' => String::format('Size of @queue queue', array('@queue' => $queue)),
      'sensor_id' => 'queue_size',
      'value_label' => 'Items',
      'settings' => array(
        'category' => 'Queue',
        'queue' => $queue,
      ),
    );
  }

  // Load .install files
  include_once DRUPAL_ROOT . '/core/includes/install.inc';
  drupal_load_updates();

  foreach (\Drupal::moduleHandler()->getImplementations('requirements') as $module) {
    // Skip update module as there is a separate sensors for core and contrib.
    if ($module == 'update') {
      continue;
    }
    $info['core_requirements_' . $module] = array(
      'label' => String::format('Module @module', array('@module' => $module)),
      'description' => String::format('Requirements of the @module module', array('@module' => $module)),
      'sensor_id' => 'core_requirements',
      'numeric' => FALSE,
      'settings' => array(
        'category' => 'Requirements',
        'caching_time' => 3600,
        'module' => $module,
        // List requirements keys which reports will be suppressed.
        'exclude keys' => array(),
      ),
    );
    // Ignore the cron key for system requirements, as we have a separate
    // sensor for this.
    if ($module == 'system') {
      $info['core_requirements_' . $module]['settings']['exclude keys'][] = 'cron';
    }
  }

  return $info;
}
