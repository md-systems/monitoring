<?php
/**
 * @file
 * @todo
 */

namespace Drupal\monitoring_multigraph\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Represents an aggregation of related sensors, called a multigraph.
 *
 * A multigraph can be read like a sensor info entity, but its result is
 * calculated directly from aggregated sensor info entities.
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
   * The aggregated sensor info entities.
   *
   * @var SensorInfo[]
   */
  public $sensors = array();

  /**
   * The multigraph name.
   *
   * @return string
   *   The name of the Multigraph
   */
  public function getName() {
    return $this->id;
  }

  /**
   * Gets multigraph label.
   *
   * The multigraph label might not be self-explaining enough or unique without
   * the category, the category should always be present when the label is
   * displayed.
   *
   * @return string
   *   Multigraph label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Gets multigraph description.
   *
   * @return string
   *   Sensor description.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Gets the aggregated sensor info entities.
   *
   * @return SensorInfo[]
   *   The aggregated sensors.
   */
  public function getSensors() {
    return $this->sensors;
  }

  /**
   * Sets aggregated sensor info entities.
   *
   * @param SensorInfo[] $sensors
   *   The sensors to be aggregated.
   */
  public function setSensors(array $sensors) {
    $this->sensors = $sensors;
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
