<?php

namespace Drupal\s3fs\EventSubscriber;

use Drupal\advagg\Asset\AssetOptimizationEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\s3fs\Asset\S3fsCssOptimizer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to asset optimization events and update assets urls.
 */
class S3fsAdvAggSubscriber implements EventSubscriberInterface {

  /**
   * Drupal ConfigFactory Service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Base path to use for URI rewrite.
   *
   * @var string
   */
  protected $rewriteFileURIBasePath;

  /**
   * The file_url_generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface|null
   */
  protected $fileUrlGenerator = NULL;

  /**
   * Construct the optimizer instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The optimizer.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, FileUrlGeneratorInterface $file_url_generator = NULL) {
    $this->configFactory = $configFactory;
    if ($file_url_generator !== NULL) {
      $this->fileUrlGenerator = $file_url_generator;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [AssetOptimizationEvent::CSS => ['updateUrls', 0]];
  }

  /**
   * Update asset urls to access static files that they aren't in S3 bucket.
   *
   * @param \Drupal\advagg\Asset\AssetOptimizationEvent $asset
   *   The asset optimization event.
   */
  public function updateUrls(AssetOptimizationEvent $asset) {
    $content = $this->processAssetContent($asset);
    $asset->setContent($content);
  }

  /**
   * Process asset content for make urls compatible.
   *
   * @param \Drupal\advagg\Asset\AssetOptimizationEvent $asset
   *   Asset to be processed.
   *
   * @return mixed
   *   preg_replace_callback() formated return.
   *
   * @see \Drupal\Core\Asset\CssOptimizer::processFile()
   */
  public function processAssetContent(AssetOptimizationEvent $asset) {
    $content = $asset->getContent();
    $css_asset = $asset->getAsset();
    // Get the parent directory of this file, relative to the Drupal root.
    $css_base_path = substr($css_asset['data'], 0, strrpos($css_asset['data'], '/'));
    // Store base path.
    $this->rewriteFileURIBasePath = $css_base_path . '/';
    // Restore asset urls.
    $content = str_replace('/' . $this->rewriteFileURIBasePath, "", $content);

    return preg_replace_callback(
      '/url\(\s*[\'"]?(?![a-z]+:|\/+)([^\'")]+)[\'"]?\s*\)/i',
      [$this, 'rewriteFileURI'],
      $content
    );
  }

  /**
   * Return absolute urls to access static files that aren't in S3 bucket.
   *
   * @param array $matches
   *   An array of matches by a preg_replace_callback() call that scans for
   *   url() references in CSS files, except for external or absolute ones.
   *
   * @return string
   *   The file path.
   */
// phpcs:disable
  public function rewriteFileURI($matches) {
// phpcs:enable
    if ($this->fileUrlGenerator !== NULL) {
      $reWriter = new S3fsCssOptimizer($this->configFactory, $this->fileUrlGenerator);
    }
    else {
      $reWriter = new S3fsCssOptimizer($this->configFactory, NULL);
    }
    $reWriter->rewriteFileURIBasePath = $this->rewriteFileURIBasePath;
    return $reWriter->rewriteFileURI($matches);
  }

}
