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
   * String that is appended to values for testing.
   *
   * @var string
   */
  protected $appendString = ' (test)';

  /**
   * Label of pre-installed multigraph.
   *
   * @var string
   */
  protected $preinstalledMultigraphLabel = 'Watchdog severe entries';

  /**
   * Description of pre-installed multigraph.
   *
   * @var string
   */
  protected $preinstalledMultigraphDescription = 'Watchdog entries with severity Warning or higher';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('dblog', 'node', 'monitoring', 'monitoring_multigraph');

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
    $this->doTestMultigraphEdit();
    $this->doTestMultigraphDelete();
  }

  /**
   * Create multigraph.
   */
  public function doTestMultigraphAdd() {
    // Add a few sensors.
    $values = array(
      'label' => $this->label,
      'id' => $this->id,
      'description' => $this->description,
      'sensor_add_select' => 'dblog_404',
    );
    $this->drupalPostForm('admin/config/system/monitoring/multigraphs/add', $values, t('Add sensor'));
    $this->assertText(t('Page not found errors logged by watchdog'));

    $this->drupalPostForm(NULL, array(
      'sensor_add_select' => 'maillog_records_count',
    ), t('Add sensor'));
    $this->assertText(t('Maillog records count'));

    $this->drupalPostForm(NULL, array(
      'sensor_add_select' => 'user_successful_logins',
    ), t('Add sensor'));
    $this->assertText(t('Successful user logins'));

    // And last but not least, change all sensor label values and save form.
    $this->drupalPostForm(NULL, array(
      'sensors[dblog_404][label]' => 'Page not found errors' . $this->appendString,
      'sensors[maillog_records_count][label]' => 'Maillog records count' . $this->appendString,
      'sensors[user_successful_logins][label]' => 'Successful user logins' . $this->appendString,
    ), t('Save'));
    $this->assertText(t('Multigraph settings saved.'));
    $this->assertText(t('Page not found errors' . $this->appendString . ', Maillog records count' . $this->appendString . ', Successful user logins' . $this->appendString));
  }

  /**
   * Edit multigraph (tests all changeable input fields).
   */
  public function doTestMultigraphEdit() {
    // Go to multigraph overview and test editing pre-installed multigraph.
    $this->drupalGet('admin/config/system/monitoring/multigraphs');
    // Check label, description and sensors (before editing).
    $this->assertText($this->preinstalledMultigraphLabel);
    $this->assertText($this->preinstalledMultigraphDescription);
    $this->assertText('404, Alert, Critical, Emergency, Error');

    // Edit.
    $this->drupalGet('admin/config/system/monitoring/multigraphs/watchdog_severe_entries');
    $this->assertText('Edit Multigraph');

    // Change label, description and add a sensor.
    $values = array(
      'label' => $this->preinstalledMultigraphLabel . $this->appendString,
      'description' => $this->preinstalledMultigraphDescription . $this->appendString,
      'sensor_add_select' => 'user_successful_logins',
    );
    $this->drupalPostForm(NULL, $values, t('Add sensor'));
    $this->assertText('Successful user logins by Watchdog');

    // Remove a sensor.
    // (drupalPostAjaxForm() lets us target the button precisely.)
    $this->drupalPostAjaxForm(NULL, array(), array('remove_dblog_404' => t('Remove')));
    $this->assertNoText(t('Page not found errors logged by watchdog'));
    $this->drupalPostForm(NULL, array(), t('Save'));

    // Change weights and save form.
    $this->drupalPostForm('admin/config/system/monitoring/multigraphs/watchdog_severe_entries', array(
      'sensors[user_successful_logins][weight]' => -2,
      'sensors[dblog_event_severity_error][weight]' => -1,
      'sensors[dblog_event_severity_critical][weight]' => 0,
      'sensors[dblog_event_severity_emergency][weight]' => 1,
      'sensors[dblog_event_severity_alert][weight]' => 2,
    ), t('Save'));
    $this->assertText(t('Multigraph settings saved.'));

    // Go back to multigraph overview and check changed values.
    $this->drupalGet('admin/config/system/monitoring/multigraphs');
    $this->assertText($this->preinstalledMultigraphLabel . $this->appendString);
    $this->assertText($this->preinstalledMultigraphDescription . $this->appendString);
    $this->assertText('Successful user logins, Error, Critical, Emergency, Alert');
  }

  /**
   * Delete multigraph.
   */
  public function doTestMultigraphDelete() {
    // Go to multigraph overview and check for pre-installed multigraph.
    $this->drupalGet('admin/config/system/monitoring/multigraphs');
    // Check label and description (before deleting).
    $this->assertText($this->preinstalledMultigraphLabel);
    $this->assertText($this->preinstalledMultigraphDescription);

    // Delete.
    $this->drupalPostForm('admin/config/system/monitoring/multigraphs/watchdog_severe_entries/delete', array(), t('Delete'));
    $this->assertText('The ' . $this->preinstalledMultigraphLabel . $this->appendString . ' multigraph has been deleted');

    // Go back to multigraph overview and check that multigraph is deleted.
    $this->drupalGet('admin/config/system/monitoring/multigraphs');
    $this->assertNoText($this->preinstalledMultigraphLabel);
    $this->assertNoText($this->preinstalledMultigraphDescription);
  }
}