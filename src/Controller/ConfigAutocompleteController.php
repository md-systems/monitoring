<?php

/**
 * @file
 * Contains \Drupal\monitoring\Controller\ConfigAutocompleteController.
 */

namespace Drupal\monitoring\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns autocomplete responses for config.
 */
class ConfigAutocompleteController implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Constructs a new ConfigAutocompleteController.
   *
   */
  public function __construct() {
  }

  /**
   * Retrieves suggestions for config autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing autocomplete suggestions.
   */
  public function autocomplete(Request $request) {
    $typed_category = $request->query->get('q');
    $matches = array();
    $prefixMatches = array_slice(\Drupal::service('config.factory')->listAll($request->query->get('q')), 0, 10);
    foreach ($prefixMatches as $config) {
      $matches[] = array('value' => $config, 'label' => String::checkPlain($config));
    }
    return new JsonResponse($matches);
  }

}
