<?php
/**
 * @file
 * Contains Drupal\monitoring_multigraph\Tests\MultigraphSimpleTest
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
  public static $modules = array('node', 'monitoring', 'monitoring_multigraph');

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
    // Add a few sensors.
    $edit0 = array(
      'label' => $this->label,
      'id' => $this->id,
      'description' => $this->description,
      'sensor_add_select' => 'dblog_404',
    );
    $this->drupalPostForm('admin/config/system/monitoring/multigraphs/add', $edit0, t('Add sensor'));
    $this->assertText(t('Page not found errors'));

    $this->drupalPostForm('admin/config/system/monitoring/multigraphs/add', array(
      'sensor_add_select' => 'node_new_page',
    ), t('Add sensor'));
    $this->assertText(t('New Basic page nodes'));

    $this->drupalPostForm('admin/config/system/monitoring/multigraphs/add', array(
      'sensor_add_select' => 'user_successful_logins',
    ), t('Add sensor'));
    $this->assertText(t('Successful user logins'));

    // And last but not least, change all sensor label values and save form.
    $this->drupalPostForm('admin/config/system/monitoring/multigraphs/add', array(
      'sensors[dblog_404][label][data]' => 'Page not found errors (test)',
      'sensors[node_new_article][label][data]' => 'New Article nodes (test)',
      'sensors[user_successful_logins][label][data]' => 'Successful user logins (test)',
    ), t('Save'));
    $this->assertText(t('Multigraph settings saved.'));
    $this->assertText(t('New Article nodes (test), Page not found errors (test), Successful user logins (test)'));

    // Check for fieldset titles.
    $this->assertText(t('Add Multigraph'));
  }
}