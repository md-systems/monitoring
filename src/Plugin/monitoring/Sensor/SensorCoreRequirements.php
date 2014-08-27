<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\Sensor\SensorCoreRequirements.
 */

namespace Drupal\monitoring\Plugin\monitoring\Sensor;

use Drupal\Component\Utility\String;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\Sensor;
use Drupal\Core\Entity\DependencyTrait;

/**
 * Monitors a specific module hook_requirements.
 *
 * @Sensor(
 *   id = "core_requirements",
 *   label = @Translation("Core Requirements"),
 *   description = @Translation("Monitors a specific module hook_requirements."),
 *   addable = FALSE
 * )
 *
 * @todo Shorten sensor message and add improved verbose output.
 */
class SensorCoreRequirements extends Sensor {

  use DependencyTrait;

  /**
   * Requirements from hook_requirements.
   *
   * @var array
   */
  protected $requirements;

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $requirements = $this->getRequirements($this->info->getSetting('module'));

    // Ignore requirements that were explicitly excluded.
    foreach ($this->info->getSetting('exclude keys', array()) as $exclude_key) {
      if (isset($requirements[$exclude_key])) {
        unset($requirements[$exclude_key]);
      }
    }

    $this->processRequirements($result, $requirements);
  }

  /**
   * Extracts the highest severity from the requirements array.
   *
   * Replacement for drupal_requirements_severity(), which ignores
   * the INFO severity, which results in those messages not being displayed.
   *
   * @param $requirements
   *   An array of requirements, in the same format as is returned by
   *   hook_requirements().
   *
   * @return
   *   The highest severity in the array.
   */
  protected function getHighestSeverity(&$requirements) {
    $severity = REQUIREMENT_INFO;
    foreach ($requirements as $requirement) {
      if (isset($requirement['severity'])) {
        $severity = max($severity, $requirement['severity']);
      }
    }
    return $severity;
  }

  /**
   * Executes the requirements hook of a module and returns the results.
   *
   * @param string $module
   *   Name of the module to return requirements for.
   *
   * @return array
   *   Array of requirements
   *
   * @throws \RuntimeException
   *   Thrown when the given module does not provide a requirements hook.
   */
  protected function getRequirements($module) {
    module_load_install($module);
    $function = $module . '_requirements';

    if (!function_exists($function)) {
      throw new \RuntimeException(String::format('Requirement function @function not found', array('@function' => $function)));
    }

    return (array)$function('runtime');
  }

  /**
   * Sets sensor result status and status messages for the given requirements.
   *
   * @param SensorResultInterface $result
   *   The result object to update.
   * @param array $requirements
   *   Array of requirements to process.
   */
  protected function processRequirements(SensorResultInterface $result, $requirements) {

    $severity = $this->getHighestSeverity($requirements);
    if ($severity == REQUIREMENT_ERROR) {
      $result->setStatus(SensorResultInterface::STATUS_CRITICAL);
    }
    elseif ($severity == REQUIREMENT_WARNING) {
      $result->setStatus(SensorResultInterface::STATUS_WARNING);
    }
    else {
      $result->setStatus(SensorResultInterface::STATUS_OK);
    }

    if (!empty($requirements)) {
      foreach ($requirements as $requirement) {
        // Skip if we do not have the highest requirements severity.
        if (!isset($requirement['severity']) || $requirement['severity'] != $severity) {
          continue;
        }

        if (!empty($requirement['title'])) {
          $result->addStatusMessage($requirement['title']);
        }

        if (!empty($requirement['description'])) {
          $result->addStatusMessage($requirement['description']);
        }

        if (!empty($requirement['value'])) {
          $result->addStatusMessage($requirement['value']);
        }
      }
    }
    // In case no requirements returned, it is assumed that all is okay.
    else {
      $result->addStatusMessage('Requirements check OK');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $module = $this->info->getSetting('module');
    $this->addDependency('module', $module);
    return $this->dependencies;
  }
}
