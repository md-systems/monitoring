<?php
/**
 * @file
 * Contains \Drupal\monitoring_multigraph\Tests\MonitoringServicesTest.
 */

namespace Drupal\monitoring_multigraph\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\monitoring_multigraph\Entity\Multigraph;
use Drupal\rest\Tests\RESTTestBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Tests for monitoring multigraph.
 */
class MonitoringMultigraphServicesTest extends RESTTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = array(
    'dblog',
    'hal',
    'rest',
    'monitoring',
    'monitoring_multigraph',
  );

  /**
   * User account to make REST requests.
   *
   * @var AccountInterface
   */
  protected $servicesAccount;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Monitoring multigraph services',
      'description' => 'Tests multigraph services.',
      'group' => 'Monitoring',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Enable REST API for monitoring resources.
    $config = \Drupal::config('rest.settings');
    $settings = array(
      'monitoring-multigraph' => array(
        'GET' => array(
          'supported_formats' => array($this->defaultFormat),
          'supported_auth' => $this->defaultAuth,
        ),
      ),
    );
    $config->set('resources', $settings);
    $config->save();
    $this->rebuildCache();

    $this->servicesAccount = $this->drupalCreateUser(array(
      'restful get monitoring-multigraph',
    ));
  }

  /**
   * Test multigraph API calls.
   */
  public function testMultigraph() {
    $this->drupalLogin($this->servicesAccount);

    $response_data = $this->doRequest('monitoring-multigraph');
    $this->assertResponse(200);

    /** @var Multigraph[] $multigraphs */
    $multigraphs = \Drupal::entityManager()
      ->getStorage('monitoring_multigraph')
      ->loadMultiple();

    // Test the list of multigraphs.
    foreach ($multigraphs as $name => $multigraph) {
      $this->assertEqual($response_data[$name]['id'], $multigraph->getName());
      $this->assertEqual($response_data[$name]['label'], $multigraph->getLabel());
      $this->assertEqual($response_data[$name]['description'], $multigraph->getDescription());
      $this->assertEqual($response_data[$name]['sensors'], $multigraph->sensors);
      $this->assertEqual($response_data[$name]['uri'], url('monitoring-multigraph/' . $multigraph->getName(), array('absolute' => TRUE)));
    }

    // Test response for non-existing multigraph.
    $name = 'multigraph_that_does_not_exist';
    $this->doRequest('monitoring-sensor-info/' . $name);
    $this->assertResponse(404);

    // Test the predefined multigraph.
    $name = 'watchdog_severe_entries';
    $response_data = $this->doRequest('monitoring-multigraph/' . $name);
    $this->assertResponse(200);
    $multigraph = $multigraphs[$name];
    $this->assertEqual($response_data['id'], $multigraph->getName());
    $this->assertEqual($response_data['label'], $multigraph->getLabel());
    $this->assertEqual($response_data['description'], $multigraph->getDescription());
    $this->assertEqual($response_data['sensors'], $multigraph->sensors);
    $this->assertEqual($response_data['uri'], url('monitoring-multigraph/' . $multigraph->getName(), array('absolute' => TRUE)));
  }

  /**
   * Do the request.
   *
   * @param string $action
   *   Action to perform.
   * @param array $query
   *   Path query key - value pairs.
   *
   * @return array
   *   Decoded json object.
   */
  protected function doRequest($action, $query = array()) {
    $url = url($action, array('absolute' => TRUE, 'query' => $query));
    $result = $this->httpRequest($url, 'GET', NULL, $this->defaultMimeType);
    return Json::decode($result);
  }

}
