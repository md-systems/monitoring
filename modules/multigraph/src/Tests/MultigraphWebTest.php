<?php
/**
 * @file
 * Contains \MultigraphSimpleTest
 */

namespace Drupal\monitoring_multigraph\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Multigraph forms (add/edit/delete).
 */
class MultigraphWebTest extends WebTestBase {

  /**
   * @var string
   */
  protected $label = 'Multigraph 123';

  /**
   * @var string
   */
  protected $id = 'multigraph_123';

  /**
   * @var string
   */
  protected $description = 'Lorem Ipsum Dolor Sit Ameth..';

  /**
   * User object.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('monitoring', 'monitoring_multigraph');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Sensor multigraph form/s',
      'description' => 'Tests then sensor multigraph forms.',
      'group' => 'Monitoring',
    );
  }

  /**
   * Configures test base and executes test cases.
   */
  public function testMultigraphForm() {
    // Create and log in our user.
    $this->adminUser = $this->drupalCreateUser(array(
      'administer monitoring',
    ));

    $this->drupalLogin($this->adminUser);

    $this->doTestMultigraphAdd();
  }

  /**
   * Flag creation.
   */
  public function doTestMultigraphAdd() {
    $edit = array(
      'label' => $this->label,
      'id' => $this->id,
      'description' => $this->description,
    );
    $this->drupalPostForm('admin/config/system/monitoring/multigraphs/add', $edit, t('Save'));
    // Check for fieldset titles.
    $this->assertText(t('Add Multigraph'));
  }
}