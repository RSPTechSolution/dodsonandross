<?php

namespace Drupal\imageapi_optimize_webp\Controller;

use Drupal\image\Controller\ImageStyleDownloadController as CoreImageStyleDownloadController;
use Drupal\image\ImageStyleInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines a controller to serve image styles.
 */
class ImageStyleDownloadController extends CoreImageStyleDownloadController {

  /**
   * Lookup potential source files based on webp uri.
   *
   * @param string $image_uri
   *   The webp image uri.
   *
   * @return mixed|null
   *   The source image uri.
   */
  public function lookupSourceImage($image_uri) {
    $source_image = substr($image_uri, 0, strrpos($image_uri, "."));
    if($source_image . '.webp' === $image_uri) {
      return $source_image;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deliver(Request $request, $scheme, ImageStyleInterface $image_style) {
    $target = $request->query->get('file');
    $path_info = pathinfo($target);
    // If .webp file, look for image to derive from.
    if ($path_info['extension'] == 'webp') {
      $image_uri = $scheme . '://' . $target;
      // Continue processing if source found, else throw NotFoundHttpException.
      if ($source_uri = $this->lookupSourceImage($image_uri)) {
        // Replace webp image with source image and call parent:deliver().
        $request->query->set('file', str_replace($scheme . '://', '', $source_uri));
        $source_response = parent::deliver($request, $scheme, $image_style);
        $derivative_uri = $image_style->buildUri($image_uri);
        // If parent:deliver() returns BinaryFileResponse, we'll replace
        // the BinaryFileResponse with one containing the .webp image
        // so long as it exists.
        if ($source_response instanceof BinaryFileResponse) {
          if (file_exists($derivative_uri)) {
            $image = $this->imageFactory->get($derivative_uri);
            $uri = $image->getSource();
            $headers = [
              'Content-Type' => 'image/webp',
              'Content-Length' => $image->getFileSize(),
            ];
            return new BinaryFileResponse($uri, 200, $headers, $scheme !== 'private');
          }
          // If the derivative does not exist, return a failed reponse.
          return new Response($this->t('Error generating image.'), 500);
        }
        // If we get any response other than BinaryFileResponse,
        // then return the response unchanged.
        return $source_response;
      }
      throw new NotFoundHttpException();
    }
    else {
      return parent::deliver($request, $scheme, $image_style);
    }
  }

}
