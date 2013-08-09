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
 * Implements hook_monitoring_sensor_info().
 *
 * Module unspecific sensors:
 * - git
 * - disappearing sensors
 */
function monitoring_monitoring_sensor_info() {
  $info['monitoring_git_dirty_tree'] = array(
    'label' => 'Git dirty working tree',
    'description' => 'Repository local file changes',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorGitDirtyTree',
    'settings' => array(
      'category' => 'System',
      // Disabled by default as we cannot know if the sensor is running in a
      // source base managed by git.
      'enabled' => FALSE,
      // Path of the repository relative to the Drupal root. Set to NULL if
      // the repository root is the same as Drupal root.
      'repo_path' => NULL,
      'cmd' => 'git status --porcelain',
    ),
  );
  $info['monitoring_disappeared_sensors'] = array(
    'label' => 'Disappeared sensors',
    'description' => 'Keeps track of active sensors and reports if a sensor disappears',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorDisappearedSensors',
    'settings' => array(
      'category' => 'System',
    ),
  );

  return $info;
}

/**
 * Implements monitoring_MODULE_monitoring_sensor_info().
 *
 * Module: node
 */
function monitoring_node_monitoring_sensor_info() {
  $info = array();

  $info['node_new_all'] = array(
    'label' => 'All new nodes',
    'description' => 'All new nodes',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimpleDatabaseAggregator',
    'value_label' => 'Nodes',
    'services' => array('database'),
    'settings' => array(
      'category' => 'Content',
      'enabled' => FALSE,
      // Cache for one hour.
      'caching_time' => 3600,
      'table' => 'node_field_data',
      'time_interval_field' => 'created',
      'time_interval_value' => 60 * 60 * 24,
    ),
  );

  foreach (node_type_get_types() as $node_type) {
    $info['node_new_' . $node_type->type] = array(
      'label' => String::format('New @type nodes', array('@type' => $node_type->name)),
      'description' => String::format('New nodes of type @type', array('@type' => $node_type->name)),
      'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimpleDatabaseAggregator',
      'value_label' => String::format('@type nodes', array('@type' => $node_type->name)),
      'settings' => array(
        'category' => 'Content',
        'enabled' => FALSE,
        // Cache for 10 minutes.
        'caching_time' => 600,
        'thresholds' => array(
          'type' => 'falls',
        ),
        'table' => 'node_field_data',
        'conditions' => array(
          array('field' => 'type', 'value' => $node_type->type),
        ),
        'time_interval_field' => 'created',
        'time_interval_value' => 60 * 60 * 24,
      ),
    );
  }

  return $info;
}

/**
 * Implements monitoring_MODULE_monitoring_sensor_info().
 *
 * Module: comment
 */
function monitoring_comment_monitoring_sensor_info() {
  $info = array();

  $info['comment_new'] = array(
    'label' => 'New comments',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimpleDatabaseAggregator',
    'value_label' => 'Comments',
    'services' => array('database'),
    'settings' => array(
      'category' => 'Content',
      'enabled' => FALSE,
      // Cache for one hour.
      'caching_time' => 3600,
      'table' => 'comment',
      'time_interval_field' => 'created',
      'time_interval_value' => 60 * 60 * 24,
    ),
  );

  return $info;
}

/**
 * Implements monitoring_MODULE_monitoring_sensor_info().
 *
 * Module: user
 */
function monitoring_user_monitoring_sensor_info() {
  $info = array();

  $info['user_new'] = array(
    'label' => 'New users',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimpleDatabaseAggregator',
    'value_label' => 'Users',
    'services' => array('database'),
    'settings' => array(
      'category' => 'User activity',
      'enabled' => FALSE,
      // Cache for one hour.
      'caching_time' => 3600,
      'table' => 'users',
      'time_interval_field' => 'created',
      'time_interval_value' => 60 * 60 * 24,
    ),
  );

  $info['user_active'] = array(
    'label' => 'Active users',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimpleDatabaseAggregator',
    'value_label' => 'Users',
    'services' => array('database'),
    'settings' => array(
      'category' => 'User activity',
      'enabled' => FALSE,
      // Cache for one hour.
      'caching_time' => 3600,
      'table' => 'users',
      'time_interval_field' => 'created',
      'time_interval_value' => 60 * 60 * 24,
    ),
  );

  // If a user logs out, the session is destroyed. This decreases visibility of
  // previous user sessions within timeframe.
  // Also this can lead to completely unidentified previous user sessions with
  // recent activity and a following logout.
  $info['user_sessions_authenticated'] = array(
    'label' => 'Authenticated user sessions',
    'description' => 'Authenticated user sessions without logout',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimpleDatabaseAggregator',
    'value_label' => 'Active sessions',
    'settings' => array(
      'category' => 'User activity',
      'table' => 'sessions',
      'conditions' => array(
        array('field' => 'uid', 'value' => '0', 'operator' => '!='),
      ),
      'time_interval_field' => 'timestamp',
      'time_interval_value' => 60 * 60 * 24,
    ),
  );
  $info['user_sessions_all'] = array(
    'label' => 'All active sessions',
    'description' => 'All active sessions of logged in and anonymous users without logout',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimpleDatabaseAggregator',
    'value_label' => 'Active sessions',
    'settings' => array(
      'category' => 'User activity',
      'table' => 'sessions',
      'time_interval_field' => 'timestamp',
      'time_interval_value' => 60 * 60 * 24,
    ),
  );

  return $info;
}

/**
 * Implements monitoring_MODULE_monitoring_sensor_info().
 *
 * Module: dblog
 */
function monitoring_dblog_monitoring_sensor_info() {

  $info['dblog_404'] = array(
    'label' => 'Page not found errors',
    'description' => 'Page not found errors logged by watchdog',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorDblog404',
    'value_label' => 'Watchdog events',
    'services' => array('database'),
    'settings' => array(
      'category' => 'Watchdog',
      // Cache for one hour.
      'caching_time' => 3600,
      'thresholds' => array(
        'type' => 'exceeds',
        'warning' => 20,
        'critical' => 100,
      ),
      'table' => 'watchdog',
      'conditions' => array(
        array('field' => 'type', 'value' => 'page not found'),
      ),
      'time_interval_field' => 'timestamp',
      'time_interval_value' => 60 * 60 * 24,
    ),
  );

  $severities = monitoring_event_severities();

  foreach (watchdog_severity_levels() as $level => $name) {
    // Error level and higher cache for 5 minutes, the other 15.
    if ($level <= WATCHDOG_ERROR) {
      $caching_time = 300;
      $critical_threshold = 5;
      $warning_threshold = 1;
    }
    else {
      $caching_time = 900;
      $critical_threshold = 50;
      $warning_threshold = 20;
    }

    $info['dblog_event_severity_' . $severities[$level]] = array(
      'label' => String::format('Severity @severity', array('@severity' => $name)),
      'description' => String::format('Watchdog entries of severity @severity', array('@severity' => $name)),
      'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimpleDatabaseAggregator',
      'value_label' => 'Events',
      'services' => array('database'),
      'settings' => array(
        'category' => 'Watchdog',
        'caching_time' => $caching_time,
        'thresholds' => array(
          'type' => 'exceeds',
          'warning' => $warning_threshold,
          'critical' => $critical_threshold,
        ),
        'table' => 'watchdog',
        'conditions' => array(
          array('field' => 'severity', 'value' => $level),
        ),
        'time_interval_field' => 'timestamp',
        'time_interval_value' => 24 * 60 * 60,
      ),
    );
  }

  $info['user_successful_logins'] = array(
    'label' => 'Successful user logins',
    'description' => 'Successful user logins by Watchdog',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimpleDatabaseAggregator',
    'value_label' => 'Login attempts',
    'services' => array('database'),
    'settings' => array(
      'category' => 'User activity',
      // Cache for one hour.
      'caching_time' => 3600,
      'table' => 'watchdog',
      'conditions' => array(
        array('field' => 'type', 'value' => 'user'),
        array('field' => 'message', 'value' => 'Session opened for %name.'),
      ),
      'time_interval_field' => 'timestamp',
      'time_interval_value' => 60 * 60 * 24,
    ),
  );
  $info['user_session_logouts'] = array(
    'label' => 'User session logouts',
    'description' => 'User session logouts by Watchdog',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimpleDatabaseAggregator',
    'value_label' => 'Logouts',
    'settings' => array(
      'category' => 'User activity',
      // Cache for one hour.
      'caching time' => 3600,
      'table' => 'watchdog',
      'conditions' => array(
        array('field' => 'type', 'value' => 'user'),
        array('field' => 'message', 'value' => 'Session closed for %name.'),
      ),
      'time_interval_field' => 'timestamp',
      'time_interval_value' => 60 * 60 * 24,
    ),
  );
  $info['user_failed_logins'] = array(
    'label' => 'Failed user logins',
    'description' => 'Failed user logins by Watchdog',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorUserFailedLogins',
    'value_label' => 'Login attempts',
    'services' => array('database'),
    'settings' => array(
      'category' => 'User activity',
      // Cache for one hour.
      'caching_time' => 3600,
      // If failed logins exceed some level we need to escalate. Default value
      // will probably not fit all sites, especially larger once.
      'thresholds' => array(
        'type' => 'exceeds',
        'warning' => 50,
        'critical' => 120,
      ),
      'table' => 'watchdog',
      'conditions' => array(
        array('field' => 'type', 'value' => 'user'),
        array('field' => 'message', 'value' => 'Login attempt failed for %user.'),
      ),
      'time_interval_field' => 'timestamp',
      'time_interval_value' => 60 * 60 * 24,
    ),
  );

  // @todo: Provide an alternative for past_db.
  if (module_exists('image')) {
    $info['dblog_image_missing_style'] = array(
      'label' => 'Image derivative creation fail',
      'description' => 'Image derivative creation fails',
      'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorImageMissingStyle',
      'value_label' => 'Watchdog entries',
      'settings' => array(
        'category' => 'Watchdog',
        // Cache for one hour.
        'caching_time' => 3600,
        'thresholds' => array(
          'type' => 'exceeds',
          'warning' => 5,
          'critical' => 20,
        ),
        'table' => 'watchdog',
        'conditions' => array(
          array('field' => 'type', 'value' => 'image'),
          array('field' => 'message', 'value' => 'Source image at %source_image_path not found while trying to generate derivative image at %derivative_path.'),
        ),
        'time_interval_field' => 'timestamp',
        'time_interval_value' => 60 * 60 * 24,
      ),
    );
  }

  return $info;
}

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
      'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimpleDatabaseAggregator',
      'value_label' => 'Events',
      'services' => array('database'),
      'settings' => array(
        'category' => 'Past',
        // Cache for one hour.
        'caching_time' => 3600,
        'table' => 'past_event',
        'conditions' => array(
          array('field' => 'severity', 'value' => $severity),
          // This is to exclude monitoring records.
          array('field' => 'type', 'value' => 'monitoring', 'operator' => '!='),
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
 * Module: past_form
 */
function monitoring_past_form_monitoring_sensor_info() {
  $info['past_form'] = array(
    'label' => 'Logged form submissions',
    'description' => 'Past event logging form submissions',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimpleDatabaseAggregator',
    'value_label' => 'Past events',
    'services' => array('database'),
    'settings' => array(
      'category' => 'Past',
      // Cache for one hour.
      'caching_time' => 3600,
      'table' => 'past_event',
      'conditions' => array(
        array('field' => 'module', 'value' => 'past_form'),
      ),
      'time_interval_field' => 'timestamp',
      'time_interval_value' => 24 * 60 * 60,
    ),
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
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorConfigValue',
    'numeric' => FALSE,
    'services' => array('config.factory'),
    'settings' => array(
      'config' => 'system.theme',
      'key' => 'default',
      'value' => 'bartik',
    ),
  );

  $info['core_maintenance_mode'] = array(
    'label' => 'Maintenance mode',
    'description' => 'Site maintenance mode',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorStateValue',
    'numeric' => FALSE,
    'value_type' => 'bool',
    'services' => array('state'),
    'settings' => array(
      'state' => 'system.maintenance_mode',
      'value' => FALSE,
    ),
  );

  // === Cron === //

  $info['core_cron_last_run_age'] = array(
    'label' => 'Last cron run age',
    'description' => 'Time since last cron run',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorCronLastRunAge',
    'value_type' => 'time_interval',
    'settings' => array(
      'category' => 'Cron',
      'thresholds' => array(
        'type' => 'exceeds',
        'warning' => 60 * 60 * 24,
        'critical' => 60 * 60 * 24 * 3,
      ),
    ),
  );

  $info['core_cron_safe_threshold'] = array(
    'label' => 'Cron safe threshold enabled',
    'description' => 'Cron safe threshold (Poormanscron) is enabled',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorConfigValue',
    'numeric' => FALSE,
    'value_type' => 'bool',
    'services' => array('config.factory'),
    'settings' => array(
      'category' => 'Cron',
      'config' => 'system.cron',
      'key' => 'threshold.autorun',
      'value' => '0',
    ),
  );

  // === Queue === //

  $queues = array_keys(Drupal::moduleHandler()->invokeAll('queue_info'));

  foreach ($queues as $queue) {
    $info['core_queue_' . $queue] = array(
      'label' => String::checkPlain($queue),
      'description' => String::format('Size of @queue queue', array('@queue' => $queue)),
      'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorQueue',
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
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorEnabledModules',
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
      'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorCoreRequirements',
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
 * Module: search_api
 */
function monitoring_search_api_monitoring_sensor_info() {
  $info = array();

  $indexes = search_api_index_load_multiple(FALSE);

  if (!empty($indexes)) {
    foreach ($indexes as $index) {
      $info['search_api_' . $index->machine_name] = array(
        'label' => String::format('Search index queue size of @index', array('@index' => $index->name)),
        'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSearchApi',
        'value_label' => 'Unindexed items',
        'settings' => array(
          'category' => 'Search API',
          // Cache for 15 minutes.
          'caching_time' => 900,
          'index_id' => $index->id,
          'enabled' => FALSE,
        ),
      );
    }
  }

  return $info;
}

/**
 * Implements monitoring_MODULE_monitoring_sensor_info().
 *
 * Module: simplenews
 */
function monitoring_simplenews_monitoring_sensor_info() {
  $info = array();

  $info['simplenews_pending'] = array(
    'label' => 'Simplenews spool size',
    'description' => 'Pending items in the simplenews spool.',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimplenewsPending',
    'value_label' => 'Pending items',
    'settings' => array(
      'category' => 'Simplenews',
      'enabled' => FALSE,
    ),
  );

  return $info;
}

/**
 * Implements monitoring_MODULE_monitoring_sensor_info().
 *
 * Module: update
 */
function monitoring_update_monitoring_sensor_info() {
  $info = array();

  $info['update_core'] = array(
    'label' => 'Core',
    'description' => 'Available updates for Drupal core',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorDrupalUpdate',
    'numeric' => FALSE,
    'value_label' => 'Outdated modules',
    'settings' => array(
      'category' => 'Update',
      // Cache for 1 day.
      'caching_time' => 24 * 3600,
      'type' => 'core',
    ),
  );
  $info['update_contrib'] = array(
    'label' => 'Modules and themes',
    'description' => 'Available updates for contrib modules and themes',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorDrupalUpdate',
    'numeric' => FALSE,
    'value_label' => 'Outdated modules',
    'settings' => array(
      'category' => 'Update',
      // Cache for 1 day.
      'caching_time' => 24 * 3600,
      'type' => 'contrib',
    ),
  );

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
      'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorElysiaCron',
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
      'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorElysiaCron',
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
 * Module: maillog
 */
function monitoring_maillog_monitoring_sensor_info() {
  $info['maillog_records_count'] = array(
    'label' => 'Maillog records count',
    'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimpleDatabaseAggregator',
    'value_label' => 'Mail records',
    'settings' => array(
      'table' => 'maillog',
      'time_interval_field' => 'sent_date',
      'time_interval_value' => 60 * 60 * 24,
    ),
  );

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
      'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorEntityDatabaseAggregator',
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
      'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorCommerceTurnover',
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
      'sensor_class' => 'Drupal\monitoring\Sensor\Sensors\SensorCommerceTurnover',
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

/**
 * Maps severities to their machine names.
 *
 * @return array
 *   Severity names array keyed by WATCHDOG_* constants.
 */
function monitoring_event_severities() {
  return array(
    WATCHDOG_DEBUG => 'debug',
    WATCHDOG_INFO => 'info',
    WATCHDOG_NOTICE => 'notice',
    WATCHDOG_WARNING => 'notice',
    WATCHDOG_ERROR => 'error',
    WATCHDOG_EMERGENCY => 'emergency',
    WATCHDOG_ALERT => 'alert',
    WATCHDOG_CRITICAL => 'critical',
  );
}
