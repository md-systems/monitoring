<?php

namespace Drupal\monitoring_munin\Pages;

class MuninConfig {


  public function content() {
    $conf_path = explode('/', conf_path());

    return array(
      '#theme' => 'monitoring_config_box',
      '#title' => t('Command definition to collect sensor data'),
      '#description' => t('Create file /etc/munin/plugins/@site_id with following code, make it executable and restart munin-node service.',
        array('@site_id' => str_replace('.', '_', monitoring_host()))),
      '#code' => monitoring_config_code('monitoring_munin', 'command', array(
        '@name' => monitoring_host(),
        '@root' => DRUPAL_ROOT,
        '@uri' => array_pop($conf_path),
      )),
      '#code_height' => '240',
    );
  }
}
