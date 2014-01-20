<?php

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Implements hook_sensor_info().
 */
function monitoring_monitoring_sensor_info() {
  $info['monitoring_git_dirty_tree'] = array(
    'label' => 'Git dirty working tree',
    'description' => 'Repository local file changes',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorGitDirtyTree',
    'settings' => array(
      'units_label' => NULL,
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
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDisappearedSensors',
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
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
    'settings' => array(
      'category' => 'Content',
      'units_label' => 'Nodes',
      'enabled' => FALSE,
      // Cache for one hour.
      'caching time' => 3600,
      'table' => 'node',
      'time_interval_field' => 'created',
      'time_interval_value' => 60 * 60 * 24,
    ),
  );

  foreach (node_type_get_types() as $node_type) {
    $info['node_new_' . $node_type->type] = array(
      'label' => format_string('New @type nodes', array('@type' => $node_type->name)),
      'description' => format_string('New nodes of type @type', array('@type' => $node_type->name)),
      'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
      'settings' => array(
        'category' => 'Content',
        'units_label' => format_string('@type nodes', array('@type' => $node_type->name)),
        'enabled' => FALSE,
        // Cache for 10 minutes.
        'caching time' => 600,
        'thresholds' => array(
          'type' => 'falls',
        ),
        'table' => 'node',
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
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
    'settings' => array(
      'category' => 'Content',
      'units_label' => 'Comments',
      'enabled' => FALSE,
      // Cache for one hour.
      'caching time' => 3600,
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
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
    'settings' => array(
      'category' => 'User activity',
      'units_label' => 'Users',
      'enabled' => FALSE,
      // Cache for one hour.
      'caching time' => 3600,
      'table' => 'users',
      'time_interval_field' => 'created',
      'time_interval_value' => 60 * 60 * 24,
    ),
  );

  $info['user_active'] = array(
    'label' => 'Active users',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
    'settings' => array(
      'category' => 'User activity',
      'units_label' => 'Users',
      'enabled' => FALSE,
      // Cache for one hour.
      'caching time' => 3600,
      'table' => 'users',
      'time_interval_field' => 'created',
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
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDblog404',
    'settings' => array(
      'category' => 'Watchdog',
      'units_label' => 'Watchdog events',
      // Cache for one hour.
      'caching time' => 3600,
      'thresholds' => array(
        'type' => 'exceeds',
        'intervals' => array(
          SensorResultInterface::STATUS_CRITICAL => 100,
          SensorResultInterface::STATUS_WARNING => 20,
        ),
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
      'label' => format_string('Severity @severity', array('@severity' => $name)),
      'description' => format_string('Watchdog entries of severity @severity', array('@severity' => $name)),
      'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
      'settings' => array(
        'category' => 'Watchdog',
        'units_label' => 'Events',
        'caching time' => $caching_time,
        'thresholds' => array(
          'type' => 'exceeds',
          'intervals' => array(
            SensorResultInterface::STATUS_CRITICAL => $critical_threshold,
            SensorResultInterface::STATUS_WARNING => $warning_threshold,
          ),
        ),
        'table' => 'watchdog',
        'conditions' => array(
          array('field' => 'severity', 'value' => $level),
          // This is to exclude monitoring records.
          array('field' => 'type', 'value' => 'monitoring', 'operator' => '!='),
        ),
        'time_interval_field' => 'timestamp',
        'time_interval_value' => 24 * 60 * 60,
      ),
    );
  }

  $info['user_successful_logins'] = array(
    'label' => 'Successful user logins',
    'description' => 'Successful user logins by Watchdog',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
    'settings' => array(
      'category' => 'User activity',
      'units_label' => 'Login attempts',
      // Cache for one hour.
      'caching time' => 3600,
      'table' => 'watchdog',
      'conditions' => array(
        array('field' => 'type', 'value' => 'user'),
        array('field' => 'message', 'value' => 'Session opened for %name.'),
      ),
      'time_interval_field' => 'timestamp',
      'time_interval_value' => 60 * 60 * 24,
    ),
  );

  $info['user_failed_logins'] = array(
    'label' => 'Failed user logins',
    'description' => 'Failed user logins by Watchdog',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorUserFailedLogins',
    'settings' => array(
      'category' => 'User activity',
      'units_label' => 'Login attempts',
      // Cache for one hour.
      'caching time' => 3600,
      // If failed logins exceed some level we need to escalate. Default value
      // will probably not fit all sites, especially larger once.
      'thresholds' => array(
        'type' => 'exceeds',
        'intervals' => array(
          SensorResultInterface::STATUS_CRITICAL => 120,
          SensorResultInterface::STATUS_WARNING => 50,
        ),
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
      'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorImageMissingStyle',
      'settings' => array(
        'category' => 'Watchdog',
        'units_label' => 'Watchdog entries',
        // Cache for one hour.
        'caching time' => 3600,
        'thresholds' => array(
          'type' => 'exceeds',
          'intervals' => array(
            SensorResultInterface::STATUS_CRITICAL => 20,
            SensorResultInterface::STATUS_WARNING => 5,
          ),
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
      'label' => format_string('Severity @severity', array('@severity' => $name)),
      'description' => format_string('Past events with severity @severity', array('@severity' => $name)),
      'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
      'settings' => array(
        'category' => 'Past',
        'units_label' => 'Events',
        // Cache for one hour.
        'caching time' => 3600,
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
    'intervals' => array(
      SensorResultInterface::STATUS_CRITICAL => 5,
      SensorResultInterface::STATUS_WARNING => 1,
    ),
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
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
    'settings' => array(
      'category' => 'Past',
      'units_label' => 'Past events',
      // Cache for one hour.
      'caching time' => 3600,
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

  $info['core_maintenance_mode'] = array(
    'label' => 'Maintenance mode',
    'description' => 'Site maintenance mode',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorVariable',
    'type' => 'state',
    'settings' => array(
      'units_label' => 'Status',
      'variable_name' => 'maintenance_mode',
      'variable_value' => FALSE,
      'variable_default_value' => FALSE,
    ),
  );

  // === Cron === //

  $info['core_cron_last_run_age'] = array(
    'label' => 'Last cron run age',
    'description' => 'Time since last cron run',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorCronLastRunAge',
    'settings' => array(
      'category' => 'Cron',
      'units_label' => 'Seconds',
      'thresholds' => array(
        'type' => 'exceeds',
        'intervals' => array(
          SensorResultInterface::STATUS_CRITICAL => 60 * 60 * 24 * 3,
          SensorResultInterface::STATUS_WARNING => 60 * 60 * 24,
        ),
      ),
    ),
  );

  $info['core_cron_safe_threshold'] = array(
    'label' => 'Cron safe threshold enabled',
    'description' => 'Cron safe threshold (Poormanscron) is enabled',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorVariable',
    'type' => 'state',
    'settings' => array(
      'category' => 'Cron',
      'units_label' => 'Status',
      'variable_name' => 'cron_safe_threshold',
      'variable_value' => '0',
      'variable_default_value' => DRUPAL_CRON_DEFAULT_THRESHOLD,
    ),
  );

  // === Queue === //

  $queues = array_keys(module_invoke_all('cron_queue_info'));

  foreach ($queues as $queue) {
    $info['core_queue_' . $queue] = array(
      'label' => check_plain($queue),
      'description' => format_string('Size of @queue queue', array('@queue' => $queue)),
      'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorQueue',
      'settings' => array(
        'category' => 'Queue',
        'units_label' => 'Items',
        'queue' => $queue,
      ),
    );
  }

  // === System === //

  $info['monitoring_enabled_modules'] = array(
    'label' => 'Enabled modules',
    'description' => 'Enabled only expected modules',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorEnabledModules',
    'settings' => array(
      'category' => 'System',
      'units_label' => 'Modules delta',
      'enabled' => FALSE,
      'allow_additional' => FALSE,
      'modules' => array(),
    ),
  );

  // Load .install files
  include_once DRUPAL_ROOT . '/includes/install.inc';
  drupal_load_updates();

  foreach (module_implements('requirements') as $module) {
    // Skip update module as there is a separate sensors for core and contrib.
    if ($module == 'update') {
      continue;
    }
    $info['core_requirements_' . $module] = array(
      'label' => format_string('Module @module', array('@module' => $module)),
      'description' => format_string('Requirements of the @module module', array('@module' => $module)),
      'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorCoreRequirements',
      'type' => 'state',
      'settings' => array(
        'category' => 'Requirements',
        // Not really applicable in this case.
        'units_label' => NULL,
        'caching time' => 3600,
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
        'label' => format_string('Search index queue size of @index', array('@index' => $index->name)),
        'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorSearchApi',
        'settings' => array(
          'category' => 'Search API',
          'units_label' => 'Unindexed items',
          // Cache for 15 minutes.
          'caching time' => 900,
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
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorSimplenewsPending',
    'settings' => array(
      'category' => 'Simplenews',
      'units_label' => 'Pending items',
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
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDrupalUpdate',
    'type' => 'state',
    'settings' => array(
      'category' => 'Update',
      'units_label' => 'Outdated modules',
      // Cache for 1 day.
      'caching time' => 24 * 3600,
      'type' => 'core',
    ),
  );
  $info['update_contrib'] = array(
    'label' => 'Modules and themes',
    'description' => 'Available updates for contrib modules and themes',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDrupalUpdate',
    'type' => 'state',
    'settings' => array(
      'category' => 'Update',
      'units_label' => 'Outdated modules',
      // Cache for 1 day.
      'caching time' => 24 * 3600,
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

  foreach($result as $row) {
    // Remove the colon from the name as it breaks munin graphs.
    $name = str_replace(':', '', $row->name);
    $info['elysia_cron_' . $name . '_last_execution'] = array(
      'label' => format_string('Elysia @name execution', array('@name' => $row->name)),
      'description' => format_string('Last execution duration for elysia @name cron', array('@name' => $row->name)),
      'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorElysiaCron',
      'settings' => array(
        'category' => 'Elysia Cron',
        'units_label' => 'Seconds',
        'enabled' => FALSE,
        'name' => $row->name,
        'metric' => 'last_execution_time',
      ),
    );
    $info['elysia_cron_' . $name . '_last_run'] = array(
      'label' => format_string('Elysia @name run', array('@name' => $row->name)),
      'description' => format_string('Time since last elysia @name run', array('@name' => $row->name)),
      'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorElysiaCron',
      'settings' => array(
        'category' => 'Elysia Cron',
        'units_label' => 'Seconds',
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
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
    'settings' => array(
      'units_label' => 'Mail records',
      'table' => 'maillog',
      'time_interval_field' => 'sent_date',
      'time_interval_value' => 60 * 60 * 24,
    ),
  );

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
