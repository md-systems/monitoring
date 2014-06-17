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
 * Module: past_db
 */
function monitoring_past_db_monitoring_sensor_info() {
  $info = array();
  $severities = monitoring_event_severities();

  foreach (past_event_severities() as $severity => $name) {
    $info['past_db_event_severity_' . $severities[$severity]] = array(
      'label' => String::format('Severity @severity', array('@severity' => $name)),
      'description' => String::format('Past events with severity @severity', array('@severity' => $name)),
      'sensor_id' => 'database_aggregator',
      'value_label' => 'Events',
      'services' => array('database'),
      'settings' => array(
        'category' => 'Past',
        // Cache for one hour.
        'caching_time' => 3600,
        'table' => 'past_event',
        'conditions' => array(
          array('field' => 'severity', 'value' => $severity),
        ),
        'time_interval_field' => 'timestamp',
        'time_interval_value' => 24 * 60 * 60,
      ),
    );
  }

  // Default thresholds for criticals (fatal errors).
  $info['past_db_event_severity_' . $severities[PAST_SEVERITY_CRITICAL]]['settings']['thresholds'] = array(
    'type' => 'exceeds',
    'warning' => 5,
    'critical' => 1,
  );
  return $info;
}

/**
 * Implements monitoring_MODULE_monitoring_sensor_info().
 *
 * Module: system
 */
function monitoring_system_monitoring_sensor_info() {

  // === Variables === //

  $info['core_theme_default'] = array(
    'label' => 'Default theme',
    'description' => 'Site default theme',
    'sensor_id' => 'config_value',
    'numeric' => FALSE,
    'services' => array('config.factory'),
    'settings' => array(
      'config' => 'system.theme',
      'key' => 'default',
      'value' => 'bartik',
      'enabled' => FALSE,
    ),
  );

  $info['core_maintenance_mode'] = array(
    'label' => 'Maintenance mode',
    'description' => 'Site maintenance mode',
    'sensor_id' => 'state_value',
    'numeric' => FALSE,
    'value_type' => 'bool',
    'services' => array('state'),
    'settings' => array(
      'state' => 'system.maintenance_mode',
      'value' => FALSE,
    ),
  );

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

  // === System === //

  $info['monitoring_enabled_modules'] = array(
    'label' => 'Enabled modules',
    'description' => 'Enabled only expected modules',
    'sensor_id' => 'monitoring_enabled_modules',
    'value_label' => 'Modules delta',
    'settings' => array(
      'category' => 'System',
      // Caching this for 1 hour as each run calls system_rebuild_module_data().
      'caching_time' => 3600,
      'enabled' => FALSE,
      'allow_additional' => FALSE,
      'modules' => array(),
    ),
  );

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

/**
 * Implements monitoring_MODULE_monitoring_sensor_info().
 *
 * Module: elysia_cron
 */
function monitoring_elysia_cron_monitoring_sensor_info() {
  $info = array();

  $result = db_select('elysia_cron', 'e')->fields('e', array('name'))->execute()->fetchAll();

  foreach ($result as $row) {
    // Remove the colon from the name as it breaks munin graphs.
    $name = str_replace(':', '', $row->name);
    $info['elysia_cron_' . $name . '_last_execution'] = array(
      'label' => String::format('Elysia @name execution', array('@name' => $row->name)),
      'description' => String::format('Last execution duration for elysia @name cron', array('@name' => $row->name)),
      'sensor_id' => 'elysia_cron',
      'value_type' => 'time_interval',
      'settings' => array(
        'category' => 'Elysia Cron',
        'enabled' => FALSE,
        'name' => $row->name,
        'metric' => 'last_execution_time',
      ),
    );
    $info['elysia_cron_' . $name . '_last_run'] = array(
      'label' => String::format('Elysia @name run', array('@name' => $row->name)),
      'description' => String::format('Time since last elysia @name run', array('@name' => $row->name)),
      'sensor_id' => 'elysia_cron',
      'value_type' => 'time_interval',
      'settings' => array(
        'category' => 'Elysia Cron',
        'enabled' => FALSE,
        'name' => $row->name,
        'metric' => 'last_run',
      ),
    );
  }

  return $info;
}

/**
 * Implements monitoring_MODULE_monitoring_sensor_info().
 *
 * Module: commerce_order
 */
function monitoring_commerce_order_monitoring_sensor_info() {
  $info = array();

  // Expose a sensor for each order status, disabled by default.
  foreach (commerce_order_statuses() as $status_name => $status_info) {
    $info['commerce_order_status_' . $status_name] = array(
      'label' => String::format('Orders in status @name', array('@name' => $status_info['title'])),
      'sensor_id' => 'database_aggregator',
      'value_label' => 'Orders',
      'settings' => array(
        'enabled' => FALSE,
        'category' => 'Commerce',
        'entity_type' => 'commerce_order',
        'conditions' => array(
          array('field' => 'status', 'value' => $status_name),
        ),
        'time_interval_field' => 'created',
        'time_interval_value' => 60 * 60 * 24,
        'caching_time' => 3600,
      ),
    );
  }

  // Expose a turnover sensor for each currency.
  $currencies = commerce_currencies(TRUE);
  foreach ($currencies as $currency) {
    $info['commerce_order_turnover_' . strtolower($currency['code'])] = array(
      'label' => String::format('Turnover in @currency', array('@currency' => $currency['code'])),
      'description' => String::format('Turnover from completed orders in @currency', array('@currency' => $currency['code'])),
      'sensor_id' => 'commerce_turnover',
      'value_label' => $currency['code'],
      'value_type' => 'commerce_currency',
      'settings' => array(
        'category' => 'Commerce',
        'table' => 'commerce_order',
        'conditions' => array(
          'status' => array(
            'field' => 'status',
            'value' => array('pending'),
            'operator' => 'IN',
          ),
        ),
        'time_interval_field' => 'created',
        'time_interval_value' => 60 * 60 * 24,
        'caching_time' => 3600,
        'currency_code' => $currency['code'],
      ),
    );
  }
  // Provide total turnover sensor if we have more than one enabled currency
  // that sums up all currencies and reports in the default currency.
  if (count($currencies) > 1) {
    $info['commerce_order_turnover_total'] = array(
      'label' => 'Total turnover',
      'description' => 'Total turnover from completed orders in all currencies',
      'sensor_id' => 'commerce_turnover',
      'value_label' => $currencies[commerce_default_currency()]['code'],
      'value_type' => 'commerce_currency',
      'settings' => array(
        'category' => 'Commerce',
        'table' => 'commerce_order',
        'conditions' => array(
          'status' => array(
            'field' => 'status',
            'value' => array('pending'),
            'operator' => 'IN',
          ),
        ),
        'time_interval_field' => 'created',
        'caching_time' => 3600,
        'time_interval_value' => 60 * 60 * 24,
      ),
    );
  }

  return $info;
}
