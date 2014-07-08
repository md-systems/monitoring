<?php

/**
 * @file
 * Contains \Drupal\monitoring_multigraph\MultigraphListBuilder.
 */

namespace Drupal\monitoring_multigraph;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\monitoring_multigraph\Entity\Multigraph;

/**
 * Defines a class to build a listing of monitoring multigraphs.
 *
 * @see \Drupal\monitoring_multigraph\Entity\Multigraph
 */
class MultigraphListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    $header['description'] = t('Description');
    $header['sensors'] = t('Sensors');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var Multigraph $entity */
    $row['label'] = $this->getLabel($entity);
    $row['description'] = $entity->getDescription();

    // Format sensors list.
    foreach ($entity->getSensors() as $sensor) {
      $row['sensors'][] = $sensor->getLabel();
    }
    $row['sensors'] = implode(', ', $row['sensors']);

    return $row + parent::buildRow($entity);
  }
}
