<?php
/**
 * @file
 * @todo
 */

namespace Drupal\monitoring_multigraph\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\monitoring\Entity\SensorInfo;

/**
 * Represents an aggregation of related sensors, called a multigraph.
 *
 * A multigraph can be read like a sensor, but its result is calculated directly
 * from the included sensors.
 *
 * @ConfigEntityType(
 *   id = "monitoring_multigraph",
 *   label = @Translation("Multigraph"),
 *   controllers = {
 *     "list_builder" = "\Drupal\monitoring_multigraph\MultigraphListBuilder",
 *     "form" = {
 *       "add" = "\Drupal\monitoring_multigraph\Form\MultigraphForm",
 *       "edit" = "\Drupal\monitoring_multigraph\Form\MultigraphForm",
 *       "delete" = "\Drupal\monitoring_multigraph\Form\MultigraphDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer sensors",
 *   config_prefix = "multigraph",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "delete-form" = "monitoring.multigraph_delete",
 *     "edit-form" = "monitoring.multigraph_edit"
 *   }
 * )
 */
class Multigraph extends ConfigEntityBase {

  /**
   * The config id.
   *
   * @var string
   */
  public $id;

  /**
   * The multigraph label.
   *
   * @var string
   */
  public $label;

  /**
   * The multigraph description.
   *
   * @var string
   */
  public $description = '';

  /**
   * The included sensors.
   *
   * This is an indexed array, where each element contains:
   *   - name: machine name of the sensor
   *   - label: custom sensor label for the multigraph
   *
   * @var string[]
   */
  public $sensors = array();

  /**
   * Gets the multigraph name.
   *
   * @return string
   *   The name of the Multigraph
   */
  public function getName() {
    return $this->id;
  }

  /**
   * Gets the multigraph label.
   *
   * @return string
   *   Multigraph label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Gets the multigraph description.
   *
   * @return string
   *   Sensor description.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Gets the included sensors.
   *
   * @return SensorInfo[]
   *   The included sensors as an indexed array, where keys are weights and
   *   values are sensors with custom labels.
   */
  public function getSensors() {
    $sensors = array();
    foreach ($this->sensors as $name => $entry) {
      /** @var SensorInfo $sensor */
      $sensor = \Drupal::entityManager()
        ->getStorage('monitoring_sensor')
        ->load($name);
      if ($entry['label']) {
        $sensor->label = $entry['label'];
      }
      $sensors[$entry['weight']] = $sensor;
    }
    return $sensors;
  }

  /**
   * Includes a sensor.
   *
   * @param SensorInfo $sensor
   *   The new sensor that should be aggregated by the multigraph.
   * @param int $weight
   *   (optional) The weight of the sensor within the multigraph.
   * @param string $label
   *   (optional) Custom label for the sensor within the multigraph.
   */
  public function addSensor(SensorInfo $sensor, $weight = NULL, $label = NULL) {
    // @todo Respect $weight
    $this->sensors[$sensor->getName()] = array(
      'name' => $sensor->getName(),
      'label' => array('data' => $label ? $label : $sensor->getLabel()),
    );
  }

  /**
   * Excludes a sensor that has previously been included.
   *
   * @param string $name
   *   Machine name of included sensor.
   */
  public function removeSensor($name) {
    unset($this->sensors[$name]);
  }

  /**
   * Gets multigraph result class.
   *
   * @return string
   *   Result class.
   */
  public function getResultClass() {
    return '\Drupal\monitoring\Result\SensorResult';
  }
}
