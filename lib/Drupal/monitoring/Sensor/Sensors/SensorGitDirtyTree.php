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
    $result->setExpectedValue(0);

    if (!empty($this->cmdOutput)) {
      $result->setValue(count(explode("\n", $this->cmdOutput)));
      $result->addStatusMessage('Files in unexpected state');
      $result->setStatus(SensorResultInterface::STATUS_CRITICAL);
    }
    else {
      $result->setValue(0);
      $result->addStatusMessage('Git repository clean');
      $result->setStatus(SensorResultInterface::STATUS_OK);
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
