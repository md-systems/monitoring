<?php

namespace Drupal\monitoring_munin;

use Drupal\Core\Config\Config;
use Drupal;

class MuninMultigraphController {

  protected $multigraphs;
  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  public function __construct(Config $config) {
    $this->config = $config;
  }

  public function save(MuninMultigraph $multigraph) {
    $config = $this->getConfig();
    $config[$multigraph->title] = array('title' => $multigraph->title, 'vlabel' => $multigraph->vlabel);
    $this->setConfig($config);
  }

  public function delete($title) {
    $config = $this->getConfig();
    if (isset($config[$title])) {
      unset($config[$title]);
    }
    $this->setConfig($config);
  }

  public function load() {
    if (empty($this->multigraphs)) {
      foreach ($this->getConfig() as $info) {
        $this->multigraphs[$info['title']] = new MuninMultigraph($info['title'], $info['vlabel']);
      }
    }

    return $this->multigraphs;
  }

  protected function getConfig() {
    $config = $this->config->get('multigraphs');
    if (!is_array($config)) {
      $config = array();
    }

    return $config;
  }

  protected function setConfig($config) {
    $this->config->set('multiraphs', $config)->save();
  }
}
