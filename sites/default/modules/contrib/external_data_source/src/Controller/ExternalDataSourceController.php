<?php

namespace Drupal\external_data_source\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\external_data_source\Plugin\ExternalDataSourceManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Masterminds\HTML5\Parser\UTF8Utils;

/**
 * Class ExternalDataSourceController.
 */
class ExternalDataSourceController extends ControllerBase {

  /**
   * Drupal\external_data_source\Plugin\ExternalDataSourceManager definition.
   *
   * @var \Drupal\external_data_source\Plugin\ExternalDataSourceManager
   */
  protected $pluginManagerExternalWsSource;

  /**
   * {@inheritdoc}
   */
  public function __construct(ExternalDataSourceManager $plugin_manager_external_ws_source) {
    $this->pluginManagerExternalWsSource = $plugin_manager_external_ws_source;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.external_data_source')
    );
  }

  /**
   * Autocomplete.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse $response
   */
  public function autocomplete(Request $request) {
    $requestedPlugin = $request->query->get('plugin_name');
    $type = \Drupal::service('plugin.manager.external_data_source');
    $plugin_definitions = $type->getDefinitions();
    $plugins = [];
    if (count($plugin_definitions)) {
      foreach ($plugin_definitions as $plugin) {
        $plugins[$plugin['id']] = $plugin['name']->__toString()
          . ' - ' . $plugin['description']->__toString();
      }
    }
    if (!array_key_exists($requestedPlugin, $plugins)) {
      throw new NotFoundHttpException();
    }
    $pluginInstance = new $plugin_definitions[$requestedPlugin]['class']();
    $pluginInstance->setRequest($request);
    return new JsonResponse($pluginInstance->getResponse());
  }

  /**
   * OptionsForSelect.
   *
   * @param Drupal\external_data_source\Plugin\ExternalDataSourceBase $pluginInstance
   *   This controller Method will return a formatted array to be used as
   *   options inside a checkbox or select field.
   *
   * @return array
   *
   * @throws \Masterminds\HTML5\Exception
   */
  public function optionsForSelect($pluginInstance) {
    $response = $pluginInstance->getResponse();
    $stringCleaner = new UTF8Utils();
    $options = [];
    foreach ($response as $key => $value) {
      $options[$stringCleaner::convertToUTF8((string) $value['value'])] = $stringCleaner::convertToUTF8((string) $value['label']);
    }
    return $options;
  }

}
