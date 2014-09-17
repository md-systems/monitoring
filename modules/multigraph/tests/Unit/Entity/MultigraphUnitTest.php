<?php
/**
 * @file
 * Contains \tests\Unit\MultigraphUnitTest.
 */

namespace tests\Unit\Entity;

use Drupal\monitoring_multigraph\Entity\Multigraph;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\monitoring_multigraph\Entity\Multigraph
 *
 * @group monitoring
 */
class MultigraphUnitTest extends UnitTestCase {

  /**
   * A mock entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    // Mock a couple of sensors with dependencies.
    $sensor1_id = $this->getRandomGenerator()->word(16);
    $sensor1 = $this->getMockSensor(array('module' => array('foo', 'bar')));

    $sensor2_id = $this->getRandomGenerator()->word(16);
    $sensor2 = $this->getMockSensor(array('module' => array('foo', 'baz')));

    // Create a Multigraph containing the sensors.
    $multigraph = new Multigraph(array(
      'sensors' => array(
        $sensor1_id => array('weight' => 0, 'label' => ''),
        $sensor2_id => array('weight' => 1, 'label' => ''),
      ),
    ), 'monitoring_multigraph');

    // Mock whatever is used in calculateDependencies().
    $sensor_storage = $this->getMock('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $sensor_storage->expects($this->any())
      ->method('load')
      ->willReturnMap(array(
        array($sensor1_id, $sensor1),
        array($sensor2_id, $sensor2),
      ));

    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->with('monitoring_sensor')
      ->willReturn($sensor_storage);

    // Assert dependencies are calculated correctly for the Multigraph.
    $dependencies = $multigraph->calculateDependencies();
    $this->assertContains('foo', $dependencies['module']);
    $this->assertContains('bar', $dependencies['module']);
    $this->assertContains('baz', $dependencies['module']);
  }

  /**
   * Returns a mock SensorInfo entity.
   *
   * @param array $dependencies
   *   An array that calls to calculateDependencies() should return.
   *
   * @return \Drupal\monitoring\Entity\SensorInfo|\PHPUnit_Framework_MockObject_MockObject
   *   The mock sensor object.
   */
  protected function getMockSensor($dependencies) {
    $sensor1 = $this->getMock('\Drupal\monitoring\Entity\SensorInfo', array(), array(array(), 'monitoring_sensor'));
    $sensor1->expects($this->any())
      ->method('calculateDependencies')
      ->willReturn($dependencies);
    return $sensor1;
  }

}
