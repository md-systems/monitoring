<?php

namespace Drupal\monitoring\Tests;

use Drupal;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\monitoring_munin\MuninMultigraph;
use Drupal\monitoring_munin\MuninMultigraphController;
use Drupal\Tests\UnitTestCase;

/**
 * @group Monitoring
 */
class MonitoringMuninUnitTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Monitoring Munin Unit Tests',
      'description' => 'Monitoring Munin Unit Tests.',
      'group' => 'Monitoring'
    );
  }

  protected function setUp() {
    $container = new ContainerBuilder();
    $config_factory = $this->getConfigFactoryStub(array());
    $container->set('config.factory', $config_factory);
    \Drupal::setContainer($container);
  }

  protected function tearDown() {
    // Passes in an empty container.
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
  }

  public function testMultigraphAPI() {
    $multigraph = new MuninMultigraph('test title', 'test units label');
    $config = \Drupal::config('monitoring.munin');
    $controller = new MuninMultigraphController($config);
    $controller->save($multigraph);

    $multigraphs = $controller->load();
    $this->assertTrue(isset($multigraphs['test title']));
    $this->assertEquals('test title', $multigraphs['test title']->title);
    $this->assertEquals('test units label', $multigraphs['test title']->vlabel);
  }
}
