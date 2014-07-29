<?php
/**
 * @file
 * Contains \Drupal\monitoring_multigraph\Entity\Multigraph.
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
 *     "canonical" = "monitoring.multigraphs_overview",
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
   * This is an associative array, where keys are sensor machine names and each
   * value contains:
   *   - weight: the sensor weight for this multigraph
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
   * Compiles entity properties into an associative array.
   *
   * @return array
   *   An associative array containing the following multigraph info:
   *     - id: The machine name
   *     - label
   *     - description
   *     - sensors: An associative array of included sensors, where keys are
   *       sensor machine names and values are associative arrays, containing:
   *         - weight
   *         - label: A custom label of the sensor for this multigraph
   */
  public function getDefinition() {
    return array(
      'id' => $this->id(),
      'label' => $this->getLabel(),
      'description' => $this->getDescription(),
      'sensors' => $this->sensors,
    );
  }

  /**
   * Gets the included sensors.
   *
   * @return SensorInfo[]
   *   The included sensors as an indexed array, where keys are weights and
   *   values are sensors with custom labels.
   */
  public function getSensors() {
    if (!$this->sensors) {
      return array();
    }
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
    ksort($sensors);

    return $sensors;
  }

  /**
   * Gets the machine names of the sensors included in this multigraph.
   *
   * @return string[]
   *   An indexed array containing the id's of the included sensors.
   */
  public function getSensorNames() {
    return $this->sensors ? array_keys($this->sensors) : array();
  }

  /**
   * Includes a sensor.
   *
   * @param string $name
   *   The machine name of the sensor that should be included by the multigraph.
   * @param string $label
   *   (optional) Custom label for the sensor within the multigraph.
   */
  public function addSensor($name, $label = NULL) {
    $this->sensors[$name] = array(
      'name' => $name,
      'label' => $label,
      'weight' => $this->sensors ? 1 + max(array_map(
        function ($mapping) {return $mapping['weight'];},
        $this->sensors
      )) : 0,
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
