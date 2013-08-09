<?php

namespace Drupal\monitoring_munin;

class MuninMultigraph {
  public $title;
  public $vlabel;

  public function __construct($title = NULL, $vlabel = NULL) {
    $this->title = $title;
    $this->vlabel = $vlabel;
  }
}
