<?php

namespace Drupal\webp\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Image\Image;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\system\FileDownloadController;
use Drupal\webp\Webp;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Defines a controller to serve image styles.
 */
class ImageStyleDownloadController extends FileDownloadController {

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * WebP driver.
   *
   * @var \Drupal\webp\Webp
   */
  protected $webp;

  /**
   * Constructs a ImageStyleDownloadController object.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Drupal\webp\Webp $webp
   *   WebP driver.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface|null $stream_wrapper_manager
   *   The stream wrapper manager.
   */
  public function __construct(LockBackendInterface $lock, ImageFactory $image_factory, Webp $webp, StreamWrapperManagerInterface $stream_wrapper_manager = NULL) {
    parent::__construct($stream_wrapper_manager);
    $this->lock = $lock;
    $this->imageFactory = $image_factory;
    $this->logger = $this->getLogger('image');
    $this->webp = $webp;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('lock'),
      $container->get('image.factory'),
      $container->get('webp.webp'),
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * Generates a derivative, given a style and image path.
   *
   * After generating an image, transfer it to the requesting agent.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $scheme
   *   The file scheme, defaults to 'private'.
   * @param \Drupal\image\ImageStyleInterface $image_style
   *   The image style to deliver.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\Response
   *   The transferred file as response or some error response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user does not have access to the file.
   * @throws \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException
   *   Thrown when the file is still being generated.
   */
  public function deliver(Request $request, $scheme, ImageStyleInterface $image_style) {
    $target = $request->query->get('file');
    $image_uri = $scheme . '://' . $target;

    if ($webp_wanted = preg_match('/\.webp$/', $image_uri)) {
      $destination = $this->webp->getWebpDestination($image_uri, '@directory@filename');
      $possible_image_uris = [$destination];

      // Try out the different possible sources for a webp image.
      $extensions = [
        '.jpg',
        '.jpeg',
        '.png',
      ];
      foreach ($extensions as $extension) {
        $possible_image_uris[] = str_replace('.webp', mb_strtoupper($extension), $image_uri);
        $possible_image_uris[] = str_replace('.webp', $extension, $image_uri);
      }

      foreach ($possible_image_uris as $possible_image_uri) {
        if (file_exists($possible_image_uri)) {
          $image_uri = $possible_image_uri;
          break;
        }
      }
    }

    // Don't try to generate file if source is missing.
    if (!file_exists($image_uri)) {
      // If the image style converted the extension, it has been added to the
      // original file, resulting in filenames like image.png.jpeg. So to find
      // the actual source image, we remove the extension and check if that
      // image exists.
      $path_info = pathinfo($this->streamWrapperManager->getTarget($image_uri));
      $converted_image_uri = sprintf('%s://%s%s%s', $this->streamWrapperManager->getScheme($image_uri), $path_info['dirname'], DIRECTORY_SEPARATOR, $path_info['filename']);
      if (!file_exists($converted_image_uri)) {
        $this->logger->notice('Source image at %source_image_path not found while trying to generate derivative image.', ['%source_image_path' => $image_uri]);
        return new Response($this->t('Error generating image, missing source file.'), 404);
      }
      else {
        // The converted file does exist, use it as the source.
        $image_uri = $converted_image_uri;
      }
    }

    // Check that the style is defined, the scheme is valid, and the image
    // derivative token is valid. Sites which require image derivatives to be
    // generated without a token can set the
    // 'image.settings:allow_insecure_derivatives' configuration to TRUE to
    // bypass the latter check, but this will increase the site's vulnerability
    // to denial-of-service attacks. To prevent this variable from leaving the
    // site vulnerable to the most serious attacks, a token is always required
    // when a derivative of a style is requested.
    // The $target variable for a derivative of a style has
    // styles/<style_name>/... as structure, so we check if the $target variable
    // starts with styles/.
    $valid = !empty($image_style) && $this->streamWrapperManager->isValidScheme($scheme);
    if (!$this->config('image.settings')->get('allow_insecure_derivatives') || strpos(ltrim($target, '\/'), 'styles/') === 0) {
      $valid &= hash_equals($request->query->get(IMAGE_DERIVATIVE_TOKEN), $image_style->getPathToken($image_uri));

			// ImageAPI Optimize case: generator searches for a WEBP, but image style
			// returns a non-WEBP (!= tokens). Sanity checks that image_style returns a token.
			if (!$valid && ($image_style->getDerivativeExtension(pathinfo($image_uri)['extension']) != "webp")) {
				$valid = ($image_style->getPathToken($image_uri)) ? true : false;
			}
    }
    if (!$valid) {
      throw new AccessDeniedHttpException();
    }

    $derivative_uri = $image_style->buildUri($image_uri);
    $headers = [];

    // If using the private scheme, let other modules provide headers and
    // control access to the file.
    if ($scheme == 'private') {
      $headers = $this->moduleHandler()->invokeAll('file_download', [$image_uri]);
      if (in_array(-1, $headers) || empty($headers)) {
        throw new AccessDeniedHttpException();
      }
    }

    // Don't start generating the image if the derivative already exists or if
    // generation is in progress in another thread.
    if (!file_exists($derivative_uri)) {
      $lock_name = 'image_style_deliver:' . $image_style->id() . ':' . Crypt::hashBase64($image_uri);
      $lock_acquired = $this->lock->acquire($lock_name);
      if (!$lock_acquired) {
        // Tell client to retry again in 3 seconds. Currently no browsers are
        // known to support Retry-After.
        throw new ServiceUnavailableHttpException(3, $this->t('Image generation in progress. Try again shortly.'));
      }
    }

    // Try to generate the image, unless another thread just did it while we
    // were acquiring the lock.
    $success = file_exists($derivative_uri) || $image_style->createDerivative($image_uri, $derivative_uri);

    if (!empty($lock_acquired)) {
      $this->lock->release($lock_name);
    }

    if ($success) {
      $image = $this->imageFactory->get($derivative_uri);

      if ($webp_wanted && ($webp = $this->webp->createWebpCopy($image->getSource())) && in_array('image/webp', $request->getAcceptableContentTypes())) {
        return $this->webpResponse($webp, $headers, $scheme);
      }
      else {
        return $this->response($image, $headers, $scheme);
      }
    }
    else {
      $this->logger->notice('Unable to generate the derived image located at %path.', ['%path' => $derivative_uri]);
      return new Response($this->t('Error generating image.'), 500);
    }
  }

  /**
   * Returns a WebP image as response.
   *
   * @param string $file
   *   Path to image file.
   * @param array $headers
   *   Response headers.
   * @param string $scheme
   *   The file scheme, defaults to 'private'.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The transferred file as response.
   */
  protected function webpResponse($file, array $headers, $scheme) {
    $headers += [
      'Content-Type' => 'image/webp',
      'Content-Length' => filesize($file),
    ];
    // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
    // sets response as not cacheable if the Cache-Control header is not
    // already modified. We pass in FALSE for non-private schemes for the
    // $public parameter to make sure we don't change the headers.
    return new BinaryFileResponse($file, 200, $headers, $scheme !== 'private');
  }

  /**
   * Returns an image style derivative as response.
   *
   * @param \Drupal\Core\Image\Image $image
   *   The image style derivation.
   * @param array $headers
   *   Response headers.
   * @param string $scheme
   *   The file scheme, defaults to 'private'.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The transferred file as response.
   */
  protected function response(Image $image, array $headers, $scheme) {
    $headers += [
      'Content-Type' => $image->getMimeType(),
      'Content-Length' => $image->getFileSize(),
    ];
    // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
    // sets response as not cacheable if the Cache-Control header is not
    // already modified. We pass in FALSE for non-private schemes for the
    // $public parameter to make sure we don't change the headers.
    return new BinaryFileResponse($image->getSource(), 200, $headers, $scheme !== 'private');
  }

}
