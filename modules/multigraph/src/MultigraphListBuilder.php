<?php

/**
 * @file
 * Contains \Drupal\monitoring\SensorListBuilder.
 */

namespace Drupal\monitoring_multigraph;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of monitoring entities.
 *
 * @see \Drupal\monitoring\Entity\SensorInfo
 */
class MultigraphListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    $header['description'] = t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['description'] = $entity->getDescription();
    return $row + parent::buildRow($entity);
  }
}
