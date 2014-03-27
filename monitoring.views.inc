<?php
/**
 * Monitoring views definitions.
 */

/**
 * Implements hook_views_data().
 */
function monitoring_views_data() {
  $data['monitoring_sensor_result']['table']['group'] = t('Monitoring');

  // Advertise this table as a possible base table.
  $data['monitoring_sensor_result']['table']['base'] = array(
    'field' => 'record_id',
    'title' => t('Sensor result log'),
  );

  $data['monitoring_sensor_result']['table']['entity type'] = 'monitoring_sensor_result';

  $data['monitoring_sensor_result']['record_id'] = array(
    'title' => t('Record ID'),
    'help' => t('The unique ID of the sensor result log.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['monitoring_sensor_result']['sensor_name'] = array(
    'title' => t('Sensor name'),
    'help' => t('The machine name of the sensor.'),
    'field' => array(
      'id' => 'monitoring_sensor_name',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );
  $data['monitoring_sensor_result']['timestamp'] = array(
    'title' => t('Timestamp'),
    'help' => t('The time when the sensor triggered.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date'
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );
  $data['monitoring_sensor_result']['sensor_status'] = array(
    'title' => t('Status'),
    'help' => t('The status at the moment when the sensor triggered.'),
    'field' => array(
      'id' => 'standard',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );
  $data['monitoring_sensor_result']['sensor_message'] = array(
    'title' => t('Status message'),
    'help' => t('The sensor status message.'),
    'field' => array(
      'id' => 'standard',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );
  $data['monitoring_sensor_result']['sensor_value'] = array(
    'title' => t('Sensor value'),
    'help' => t('The value at the moment when the sensor triggered.'),
    'field' => array(
      'id' => 'standard',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );
  $data['monitoring_sensor_result']['execution_time'] = array(
    'title' => t('Execution time'),
    'help' => t('Sensor execution time in ms.'),
    'field' => array(
      'id' => 'standard',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  return $data;
}
