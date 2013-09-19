<?php
/**
 * @file
 * Watchdog missing image style sensor.
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Result\SensorResultInterface;


/**
 * Extends the SensorDatabaseAggregator generic class to capture missing
 * image style error.
 */
class SensorWatchdogMissingImageStyle extends SensorDatabaseAggregator {

  protected $sourceImagePath;

  /**
   * {@inheritdoc}
   *
   * Extends the watchdog query.
   */
  public function alterQuery(\SelectQuery $query) {
    $query->addField('watchdog', 'variables');
    $query->groupBy('variables');
    $query->orderBy('records_count', 'DESC');
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    parent::runSensor($result);
    $query_result = $this->fetchObject();
    if (!empty($query_result)) {
      $variables = unserialize($query_result->variables);
      if (isset($variables['%source_image_path'])) {
        $result->addSensorStatusMessage($variables['%source_image_path']);
        $this->sourceImagePath = $variables['%source_image_path'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result, $as_array = FALSE) {

    $verbose = parent::resultVerbose($result, $as_array);

    // If non found, no reason to query file_managed table.
    if ($result->getSensorValue() == 0) {
      return $verbose;
    }

    // In case we were not able to retrieve this info from the watchdog
    // variables.
    if (empty($this->sourceImagePath)) {
      $message = t('Source image path is empty, cannot query file_managed table');
    }

    $file = db_query('SELECT * FROM file_managed WHERE uri = :uri', array(':uri' => $this->sourceImagePath))->fetchObject();

    if (!empty($file)) {
      $message = t('File managed records: <pre>@file_managed</pre>', array('@file_managed' => var_export(file_usage_list($file), TRUE)));
    }

    if (empty($message)) {
      $message = t('File @file record not found in the file_managed table.', array('@file' => $result->getSensorMessage()));
    }

    if ($as_array) {
      $verbose['message'] = $message;
    }
    else {
      $verbose .=  ' ' . $message;
    }

    return $verbose;
  }
}
