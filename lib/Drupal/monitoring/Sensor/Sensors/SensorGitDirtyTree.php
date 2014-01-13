<?php
/**
 * @file
 * Contains \Drupal\monitoring\Sensor\Sensors\SensorGitDirtyTree
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\SensorConfigurable;
use Drupal\monitoring\Sensor\SensorExtendedInfoInterface;

/**
 * Monitors the repository for dirty files.
 */
class SensorGitDirtyTree extends SensorConfigurable implements SensorExtendedInfoInterface {

  protected $cmdOutput;

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $this->cmdOutput = trim(shell_exec($this->buildCMD()));
    $result->setSensorExpectedValue(0);

    if (!empty($this->cmdOutput)) {
      $result->setSensorValue(count(explode("\n", $this->cmdOutput)));
      $result->addSensorStatusMessage('Files in unexpected state');
      $result->setSensorStatus(SensorResultInterface::STATUS_CRITICAL);
    }
    else {
      $result->setSensorValue(0);
      $result->addSensorStatusMessage('Git repository clean');
      $result->setSensorStatus(SensorResultInterface::STATUS_OK);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    $output = 'CMD: ' . $this->buildCMD();;
    $output .= "\n\n" . $this->cmdOutput;
    return $output;
  }

  /**
   * Helper to build the command to be passed into shell_exec().
   *
   * @return string
   *   Shell command.
   */
  protected function buildCMD() {
    $repo_path = DRUPAL_ROOT . '/' . $this->info->getSetting('repo_path');
    $cmd = $this->info->getSetting('cmd');
    return "cd $repo_path\n$cmd  2>&1";
  }
}
