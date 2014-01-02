<?php

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Implements hook_sensor_info().
 */
function monitoring_monitoring_sensor_info() {
  $info['git_dirty_tree'] = array(
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

  return $info;
}

/**
 * Implements monitoring_MODULE_monitoring_sensor_info().
 *
 * Module: node
 */
function monitoring_node_monitoring_sensor_info() {
  $info = array();

  $info['new_nodes'] = array(
    'label' => 'New nodes',
    'description' => 'New nodes added in last 24 hours',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
    'settings' => array(
      'category' => 'Content',
      'units_label' => 'Nodes',
      'enabled' => FALSE,
      // Cache for one hour.
      'caching time' => 3600,
      'table' => 'node',
      'time_period' => array(
        'field' => 'created',
        'value' => 24 * 60 * 60,
      ),
    ),
  );

  return $info;
}

/**
 * Implements monitoring_MODULE_monitoring_sensor_info().
 *
 * Module: comment
 */
function monitoring_comment_monitoring_sensor_info() {
  $info = array();

  $info['new_comments'] = array(
    'label' => 'New comments',
    'description' => 'New comments added in last 24 hours',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
    'settings' => array(
      'category' => 'Content',
      'units_label' => 'Comments',
      'enabled' => FALSE,
      // Cache for one hour.
      'caching time' => 3600,
      'table' => 'comment',
      'time_period' => array(
        'field' => 'created',
        'value' => 24 * 60 * 60,
      ),
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

  $info['new_users'] = array(
    'label' => 'New users',
    'description' => 'New users registered in last 24 hours',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
    'settings' => array(
      'category' => 'User activity',
      'units_label' => 'Users',
      'enabled' => FALSE,
      // Cache for one hour.
      'caching time' => 3600,
      'table' => 'users',
      'time_period' => array(
        'field' => 'created',
        'value' => 24 * 60 * 60,
      ),
    ),
  );
  $info['active_users'] = array(
    'label' => 'Active users',
    'description' => 'Active users in last 24 hours',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
    'settings' => array(
      'category' => 'User activity',
      'units_label' => 'Users',
      'enabled' => FALSE,
      // Cache for one hour.
      'caching time' => 3600,
      'table' => 'users',
      'time_period' => array(
        'field' => 'access',
        'value' => 24 * 60 * 60,
      ),
    ),
  );

  return $info;
}

/**
 * Implements monitoring_MODULE_monitoring_sensor_info().
 *
 * Module: image
 */
function monitoring_image_monitoring_sensor_info() {
  // Sensor requires the {watchdog} table from the dblog module.
  // @todo: Provide an alternative for past_db.
  if (module_exists('dblog')) {
    $info['image_style_missing_count'] = array(
      'label' => 'Image derivative creation fail',
      'description' => 'Image derivative creation fails in last 24 hours',
      'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorWatchdogMissingImageStyle',
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
        'time_period' => array(
          'field' => 'timestamp',
          'value' => 24 * 60 * 60,
        ),
      ),
    );
  }
  return $info;
}

/**
 * Implements monitoring_MODULE_monitoring_sensor_info().
 *
 * Module: dblog
 */
function monitoring_dblog_monitoring_sensor_info() {
  $info['404_in_last_24_hours'] = array(
    'label' => '404 in last 24 hours',
    'description' => '404 logged by watchdog in last 24 hours',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorWatchdog404',
    'settings' => array(
      'category' => 'Watchdog',
      'units_label' => 'Watchdog items',
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
      'time_period' => array(
        'field' => 'timestamp',
        'value' => 24 * 60 * 60,
      ),
    ),
  );


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
    $info['watchdog_severity_' . $level] = array(
      'label' => format_string('Watchdog severity @severity', array('@severity' => $name)),
      'description' => format_string('Watchdog entries of severity @severity in last 24 hours', array('@severity' => $name)),
      'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
      'settings' => array(
        'category' => 'Watchdog',
        'units_label' => 'Watchdog entries',
        'caching time' => $caching_time,
        'thresholds' => array(
          'type' => 'exceeds',
          SensorResultInterface::STATUS_CRITICAL => $critical_threshold,
          SensorResultInterface::STATUS_WARNING => $warning_threshold,
        ),
        'table' => 'watchdog',
        'conditions' => array(
          array('field' => 'severity', 'value' => $level),
          // This is to exclude monitoring records.
          array('field' => 'type', 'value' => 'monitoring', 'operator' => '!='),
        ),
        'time_period' => array(
          'field' => 'timestamp',
          'value' => 24 * 60 * 60,
        ),
      ),
    );
  }

  $info['successful_user_logins'] = array(
    'label' => 'Successful user logins',
    'description' => 'Successful user logins by Watchodg in last 24 hours',
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
      'time_period' => array(
        'field' => 'timestamp',
        'value' => 24 * 60 * 60,
      ),
    ),
  );
  $info['failed_user_logins'] = array(
    'label' => 'Failed user logins',
    'description' => 'Failed user logins by Watchodg in last 24 hours',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorFailedUserLogins',
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
      'time_period' => array(
        'field' => 'timestamp',
        'value' => 24 * 60 * 60,
      ),
    ),
  );

  return $info;
}

/**
 * Implements monitoring_MODULE_monitoring_sensor_info().
 *
 * Module: past_db
 */
function monitoring_past_db_monitoring_sensor_info() {
  $info = array();

  foreach (past_event_severities() as $severity => $name) {
    $info['past_severity_' . $severity] = array(
      'label' => format_string('Past severity @severity', array('@severity' => $name)),
      'description' => format_string('Past events with severity @severity in last 24 hours', array('@severity' => $name)),
      'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
      'settings' => array(
        'category' => 'Past',
        'units_label' => 'Past entries',
        // Cache for one hour.
        'caching time' => 3600,
        'table' => 'past_event',
        'conditions' => array(
          array('field' => 'severity', 'value' => $severity),
          // This is to exclude monitoring records.
          array('field' => 'type', 'value' => 'monitoring', 'operator' => '!='),
        ),
        'time_period' => array(
          'field' => 'timestamp',
          'value' => 24 * 60 * 60,
        ),
      ),
    );
  }

  // Default thresholds for criticals (fatal errors).
  $info['past_severity_' . PAST_SEVERITY_CRITICAL]['settings']['thresholds'] = array(
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
    'label' => 'Past form submissions',
    'description' => 'Past events logging form submissions',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDatabaseAggregator',
    'settings' => array(
      'category' => 'Past',
      'units_label' => 'Past entries',
      // Cache for one hour.
      'caching time' => 3600,
      'table' => 'past_event',
      'conditions' => array(
        array('field' => 'module', 'value' => 'past_form'),
      ),
      'time_period' => array(
        'field' => 'timestamp',
        'value' => 24 * 60 * 60,
      ),
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

  $info['maintenance_mode'] = array(
    'label' => 'Maintenance mode',
    'description' => 'Site maintenance mode',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorVariable',
    'type' => 'state',
    'settings' => array(
      'category' => 'Variables',
      'units_label' => 'Status',
      'variable_name' => 'maintenance_mode',
      'variable_value' => FALSE,
      'variable_default_value' => FALSE,
    ),
  );

  // === Cron === //

  $info['last_cron_run'] = array(
    'label' => 'Last cron run',
    'description' => 'Number of seconds since last cron run',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorLastCronRun',
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

  $info['cron_safe_threshold_enabled'] = array(
    'label' => 'Cron safe threshold enabled',
    'description' => 'Cron safe threshold (Poormanscron) is enabled',
    'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorVariable',
    'type' => 'state',
    'settings' => array(
      'category' => 'Variables',
      'units_label' => 'Status',
      'variable_name' => 'cron_safe_threshold',
      'variable_value' => '0',
      'variable_default_value' => DRUPAL_CRON_DEFAULT_THRESHOLD,
    ),
  );

  // === Queue === //

  $queues = array_keys(module_invoke_all('cron_queue_info'));

  foreach ($queues as $queue) {
    $info['queue_' . $queue] = array(
      'label' => format_string('Core queue @queue', array('@queue' => $queue)),
      'description' => format_string('Core queue size of @queue', array('@queue' => $queue)),
      'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorQueue',
      'settings' => array(
        'category' => 'Drupal queue',
        'units_label' => 'Items',
        'queue' => $queue,
      ),
    );
  }

  // === System === //

  $info['enabled_modules'] = array(
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
    $info['drupal_requirements_' . $module] = array(
      'label' => format_string('Drupal requirements @module', array('@module' => $module)),
      'description' => format_string('Drupal requirements of module @module', array('@module' => $module)),
      'sensor class' => 'Drupal\monitoring\Sensor\Sensors\SensorDrupalRequirements',
      'type' => 'state',
      'settings' => array(
        'category' => 'Requirements',
        // Not really applicable in this case.
        'units_label' => NULL,
        'caching time' => 3600,
        'module' => $module,
        // List requirements keys which reports will be suppressed.
        'exclude keys' => array('cron'),
      ),
    );
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
        'label' => format_string('Queue size of @index', array('@index' => $index->name)),
        'description' => format_string('Search index queue size of @index', array('@index' => $index->name)),
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
    'label' => 'Update Core status',
    'description' => 'Available updates for the Drupal core',
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
    'label' => 'Update modules and themes status',
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
    $info['elysia_' . $name . '_last_execution'] = array(
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
    $info['elysia_' . $name . '_last_run'] = array(
      'label' => format_string('Elysia @name run', array('@name' => $row->name)),
      'description' => format_string('Number of seconds since last elysia @name run', array('@name' => $row->name)),
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
