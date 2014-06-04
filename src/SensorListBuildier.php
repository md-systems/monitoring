<?php

/**
 * @file
 * Contains \Drupal\monitoring\MonitoringListBuilder.
 */

namespace Drupal\monitoring;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of monitoring entities.
 *
 * @see \Drupal\monitoring\Entity\SensorInfo
 */
class MonitoringListBuilder extends ConfigEntityListBuilder {

  /*
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['category'] = t('Category');
    $header['label'] = t('Label');
    $header['description'] = t('Description');
    $header['status'] = t('Status');
    $header['actions'] = t('Actions');
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
      $row['status'] = 'Enabled';
    }
    else {
      $row['status'] = 'Disabled';
    }
    return $row + parent::buildRow($entity);
  }
}