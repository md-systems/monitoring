<?php
/**
 * @file
 * @todo
 */

namespace Drupal\monitoring_multigraph\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

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
 *     "list_builder" = "\Drupal\Core\Config\Entity\ConfigEntityListBuilder"
 *   },
 *   admin_permission = "administer sensors",
 *   config_prefix = "sensor.multigraph",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
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
   * The multigraph category.
   *
   * @var string
   */
  public $category = 'Other';

  /**
   * The aggregated sensor info entities.
   *
   * @var SensorInfo[]
   */
  public $sensors = array();

  /**
   * The multigraph id.
   *
   * @return string
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
   * Gets multigraph result class.
   *
   * @return string
   *   Result class.
   */
  public function getResultClass() {
    return '\Drupal\monitoring\Result\SensorResult';
  }

  /**
   * Gets multigraph categories.
   *
   * @return string
   *   Categories.
   */
  public function getCategory() {
    return $this->category;
  }
}
