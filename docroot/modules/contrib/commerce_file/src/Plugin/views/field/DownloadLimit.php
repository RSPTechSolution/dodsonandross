<?php

namespace Drupal\commerce_file\Plugin\views\field;

use Drupal\commerce_file\DownloadLoggerInterface;
use Drupal\commerce_file\LicenseFileManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the download limit for a licensed file.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("download_limit")
 */
class DownloadLimit extends FieldPluginBase {

  /**
   * The file download logger.
   *
   * @var \Drupal\commerce_file\DownloadLoggerInterface
   */
  protected $downloadLogger;

  /**
   * The license file manager.
   *
   * @var \Drupal\commerce_file\LicenseFileManagerInterface
   */
  protected $licenseFileManager;

  /**
   * EntityTypeManager class.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a DownloadLimit object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_file\DownloadLoggerInterface $download_logger
   *   The file download logger.
   * @param \Drupal\commerce_file\LicenseFileManagerInterface $license_file_manager
   *   The license file manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DownloadLoggerInterface $download_logger, LicenseFileManagerInterface $license_file_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->downloadLogger = $download_logger;
    $this->licenseFileManager = $license_file_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_file.download_logger'),
      $container->get('commerce_file.license_file_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // The file field is required in order to display the download limit.
    if (!isset($this->view->field['commerce_file'], $values->{$this->view->field['commerce_file']->aliases['commerce_file_target_id']})) {
      return '';
    }
    /** @var \Drupal\commerce_license\Entity\LicenseInterface $license */
    $license = $this->getEntity($values);
    $purchased_entity = $license->getPurchasedEntity();
    if ($purchased_entity->get('commerce_file')->isEmpty()) {
      return '';
    }
    $download_limit = $this->licenseFileManager->getDownloadLimit($license);
    if (!$download_limit) {
      return $this->t('Unlimited');
    }
    $file_id = $values->{$this->view->field['commerce_file']->aliases['commerce_file_target_id']};
    $counts = $this->downloadLogger->getDownloadCounts($license);

    // A count of 0 should be returned if the file was never downloaded, so
    // this shouldn't happen.
    if (!isset($counts[$file_id])) {
      return '';
    }

    return $counts[$file_id] . ' / ' . $download_limit;
  }

}
