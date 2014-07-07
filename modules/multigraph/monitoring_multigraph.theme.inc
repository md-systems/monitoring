<?php
/**
 * @file
 * Theme functions for Multigraph.
 */

use \Drupal\monitoring\Entity\SensorInfo;
use \Drupal\Core\Render\Element;

/**
 * Form table displaying the sensors included in a multigraph.
 *
 * @param array $variables
 *   An associative array containing:
 *     - sensors: A list of associative arrays containing:
 *       - #sensor: A SensorInfo object.
 *       - other Form API element properties.
 *
 * @return string
 *   HTML for the table.
 */
function theme_monitoring_multigraph_sensor_table(array $variables) {
  $sensors = $variables['sensors'];
  $options = array();
  foreach (Element::children($sensors) as $key) {
    /** @var SensorInfo $sensor */
    $sensor = $sensors[$key]['#sensor'];
    $options[$sensor->getName()] = array(
      'category' => $sensor->getCategory(),
      'title' => $sensors[$key]['title'],
      'description' => $sensor->getDescription(),
    );
  }

  $table = array(
    '#type' => 'table',
    '#rows' => $options,
    '#header' => array(
      'category' => t('Category'),
      'title' => t('Sensor label'),
      'description' => t('Description'),
    ),
    '#prefix' => '<div id="selected-sensors">',
    '#suffix' => '</div>',
    '#empty' => t(
      'Select and add sensors above to include them in this multigraph.'
    ),
  );

  return drupal_render($table);
}
