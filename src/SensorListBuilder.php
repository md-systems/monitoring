<?php

/**
 * @file
 * Contains \Drupal\monitoring\SensorListBuilder.
 */

namespace Drupal\monitoring;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of monitoring entities.
 *
 * @see \Drupal\monitoring\Entity\SensorInfo
 */
class SensorListBuilder extends ConfigEntityListBuilder {

  /*
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['category'] = t('Category');
    $header['label'] = t('Label');
    $header['description'] = t('Description');
    $header['status'] = t('Status');
    return $header + parent::buildHeader();
  }
  
  /*
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['category'] = $entity->getCategory();
    $row['description'] = $entity->getDescription();
    if($entity->isEnabled()) {
      $row['status'] = t('Enabled');
    }
    else {
      $row['status'] = t('Disabled');
    }
    return $row + parent::buildRow($entity);
  }
}
