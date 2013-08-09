<?php

namespace Drupal\monitoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorRunner;

class SensorList extends ControllerBase {

  public function content() {
    $rows = array();
    $runner = new SensorRunner();
    $results = $runner->runSensors();
    $status_overview = array(
      SensorResultInterface::STATUS_OK => 0,
      SensorResultInterface::STATUS_INFO => 0,
      SensorResultInterface::STATUS_WARNING => 0,
      SensorResultInterface::STATUS_CRITICAL => 0,
      SensorResultInterface::STATUS_UNKNOWN => 0,
    );
    $total_execution_time = 0;
    $non_cached_execution_time = 0;
    // Oldest sensor age in seconds.
    $oldest_sensor_age = 0;
    // Oldest sensor info.
    $oldest_sensor_info = NULL;

    foreach (monitoring_sensor_info_by_categories() as $category => $category_sensor_info) {

      // Category grouping row.
      $rows[] = array(
        'data' => array(
          'label' => array(
            'data' => '<h3>' . $category . '</h3>',
            'colspan' => 7
          ),
        ),
      );
      $ok_row_count = 0;

      foreach ($category_sensor_info as $sensor_name => $sensor_info) {
        if (!isset($results[$sensor_name])) {
          continue;
        }
        /** @var SensorResultInterface $sensor_result */
        $sensor_result = $results[$sensor_name];
        $called_before = REQUEST_TIME - $sensor_result->getTimestamp();
        if ($called_before > $oldest_sensor_age) {
          $oldest_sensor_info = $sensor_info;
          $oldest_sensor_age = $called_before;
        }

        $row['data']['label'] = '<span title="' . $sensor_info->getDescription() . '">' . $sensor_info->getLabel() . '</span>';

        $row['data']['sensor_status'] = array(
          'data' => $sensor_result->getStatus(),
          'class' => array('status'),
        );

        $row['data']['timestamp'] = \Drupal::service('date')->formatInterval(REQUEST_TIME - $sensor_result->getTimestamp());
        $row['data']['execution_time'] = array(
          'data' => $sensor_result->getExecutionTime() . 'ms',
          'class' => array('execution-time'),
        );
        $row['data']['sensor_status_message'] = truncate_utf8(strip_tags($sensor_result->getMessage()), 200, TRUE, TRUE);

        $row['class'] = array('monitoring-' . strtolower($sensor_result->getStatus()));

        $links = array();
        $links['details'] = array('title' => t('Details'), 'href' => 'admin/reports/monitoring/sensors/' . $sensor_name);
        if ($this->currentUser()->hasPermission('monitoring verbose')) {
          $links['log'] = array('title' => t('Log'), 'href' => 'admin/reports/monitoring/sensors/' . $sensor_name . '/log');
        }
        // Display a force execution link for any sensor that can be cached.
        if ($sensor_info->getCachingTime() && $this->currentUser()->hasPermission('monitoring force run')) {
          $links['force_execution'] = array('title' => t('Force execution'), 'href' => 'monitoring/sensors/force/' . $sensor_name);
        }
        if ($sensor_info->isConfigurable() && $this->currentUser()->hasPermission('administer monitoring')) {
          $links['settings'] = array('title' => t('Settings'), 'href' => 'admin/config/system/monitoring/sensors/' . $sensor_name,
            'query' => array('destination' => 'admin/reports/monitoring'));
        }

        \Drupal::moduleHandler()->alter('monitoring_sensor_links', $links, $sensor_info);

        $row['data']['actions'] = array();
        if (!empty($links)) {
          $row['data']['actions']['data'] = array('#type' => 'dropbutton', '#links' => $links);
        }

        $rows[] = $row;

        $status_overview[$sensor_result->getStatus()]++;
        $total_execution_time += $sensor_result->getExecutionTime();
        if (!$sensor_result->isCached()) {
          $non_cached_execution_time += $sensor_result->getExecutionTime();
        }
        if ($sensor_result->getStatus() == SensorResultInterface::STATUS_OK) {
          $ok_row_count++;
        }
        else {
          $ok_row_count = -1;
        }
      }

      // Add special class if all sensors of a category are ok.
      if ($ok_row_count >= 0) {
        $index = count($rows) - $ok_row_count - 1;
        $rows[$index]['class'][] = 'sensor-category-ok';
      }
    }

    $output['summary'] = array(
      '#theme' => 'monitoring_overview_summary',
      '#status_overview' => $status_overview,
      '#total_execution_time' => $total_execution_time,
      '#non_cached_execution_time' => $non_cached_execution_time,
    );

    // We can add the oldest_sensor_* data only if there are sensor results cached.
    if (!empty($oldest_sensor_info)) {
      $output['summary']['#oldest_sensor_label'] = $oldest_sensor_info->getLabel();
      $output['summary']['#oldest_sensor_category'] = $oldest_sensor_info->getCategory();
      $output['summary']['#oldest_sensor_called_before'] = \Drupal::service('date')->formatInterval($oldest_sensor_age);
    }

    $header = array(
      t('Sensor name'),
      array('data' => t('Status'), 'class' => array('status')),
      t('Called before'),
      t('Execution time'),
      t('Status Message'),
      array('data' => t('Actions'), 'class' => array('actions')),
    );

    $monitoring_escalated_sensors = $status_overview[SensorResultInterface::STATUS_WARNING] +
        $status_overview[SensorResultInterface::STATUS_CRITICAL] +
        $status_overview[SensorResultInterface::STATUS_UNKNOWN];

    $output['table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#attributes' => array(
        'class' => array('monitoring-severity-colors'),
        'id' => 'monitoring-sensors-overview',
      ),
      '#attached' => array(
        'css' => array(
          drupal_get_path('module', 'monitoring') . '/monitoring.css',
        ),
        'js' => array(
          array(
            'data' => drupal_get_path('module', 'monitoring') . '/monitoring.js',
            'type' => 'file',
          ),
          array(
            'data' => array('monitoring_escalated_sensors' => $monitoring_escalated_sensors),
            'type' => 'setting',
          ),
        ),
      ),
    );

    return $output;
  }
}
