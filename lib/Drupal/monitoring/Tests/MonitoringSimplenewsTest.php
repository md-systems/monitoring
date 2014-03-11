<?php
/**
 * @file
 * Contains \MonitoringSimplenewsTest.
 */

namespace Drupal\monitoring\Tests;

/**
 * Tests for simplenews sensor.
 */
class MonitoringSimplenewsTest extends MonitoringTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('simplenews');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Monitoring simplenews',
      'description' => 'Simplenews sensor tests.',
      'group' => 'Monitoring',
      'dependencies' => array('simplenews'),
    );
  }

  /**
   * Tests individual sensors.
   *
   * @todo - enable once we have simplenews D8.
   */
  function dtestSensors() {

    // No spool items - status OK.
    $result = $this->runSensor('simplenews_pending');
    $this->assertEqual($result->getValue(), 0);

    // Crate a spool item in state pending.
    simplenews_save_spool(array(
      'mail' => 'mail@example.com',
      'nid' => 1,
      'tid' => 1,
      'snid' => 1,
      'data' => array('data' => 'data'),
    ));
    $result = $this->runSensor('simplenews_pending');
    $this->assertEqual($result->getValue(), 1);

  }

}
