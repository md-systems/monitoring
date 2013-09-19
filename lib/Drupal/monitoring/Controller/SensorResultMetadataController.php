<?php

/**
 * @file
 * Monitoring sensor call metadata controller.
 */

namespace Drupal\monitoring\Controller;

/**
 * Controller class for the Entity Metadata.
 */
class SensorResultMetadataController extends \EntityDefaultMetadataController {

  /**
   * {@inheritdoc}
   */
  public function entityPropertyInfo() {
    // Loading property information and make em better usable in here.
    $info = parent::entityPropertyInfo();
    $prop = &$info[$this->type]['properties'];

    // The timestamp should be rendered/shown as a date.
    $prop['timestamp']['type'] = 'date';

    return $info;
  }
}
