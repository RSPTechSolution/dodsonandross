<?php

namespace Drupal\s3fs\Traits;

use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\s3fs\Exceptions\CrossSchemeAccessException;

/**
 * S3fs path helper functions.
 *
 * @ingroup s3fs
 */
trait S3fsPathsTrait {

  /**
   * Resolve a (possibly) relative path to its non-relative form.
   *
   * Based on vfsStreamWrapper::resolvePath().
   *
   * @param string $path
   *   The path to resolve.
   *
   * @return string
   *   The resolved path.
   */
  protected function resolvePath(string $path): string {
    $scheme = StreamWrapperManager::getScheme($path);
    $target = StreamWrapperManager::getTarget($path);
    $new_path = [];
    foreach (explode('/', $target) as $target_part) {
      if ('.' !== $target_part) {
        if ('..' !== $target_part) {
          $new_path[] = $target_part;
        } elseif (count($new_path) > 0) {
          array_pop($new_path);
        }
      }
    }

    $imploded_path = implode('/', $new_path);
    $resolved_path = $scheme . '://' . $imploded_path;

    $this->preventCrossSchemeAccess($resolved_path);

    return $resolved_path;
  }

  /**
   * Prevent cross scheme access attempts.
   *
   * @param string $uri
   *   Uri attempt to access.
   *
   * @throws \Drupal\s3fs\Exceptions\CrossSchemeAccessException
   *   Cross scheme access attempt has been rejected.
   */
  protected function preventCrossSchemeAccess(string $uri) {
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    $uri = $stream_wrapper_manager->normalizeUri($uri);

    $public_folder = !empty($this->config['public_folder']) ? $this->config['public_folder'] : 's3fs-public';
    $public_folder = trim($public_folder, '/');
    $private_folder = !empty($this->config['private_folder']) ? $this->config['private_folder'] : 's3fs-private';
    $private_folder = trim($private_folder, '/');

    $scheme = StreamWrapperManager::getScheme($uri);

    switch ($scheme) {
      case 's3':
        // Attempt to use s3:// to access public:// or private:// path.
        if (mb_strpos($uri, 's3://' . $public_folder) === 0 || mb_strpos($uri, 's3://' . $private_folder) === 0) {
          throw new CrossSchemeAccessException("Cross scheme access attempt blocked");
        }
        break;

      case 'public':
        // Private folder may be nested under public folder.
        if (mb_strpos($private_folder, $public_folder) === 0) {
          $public_search = '#^' . $public_folder . '#';
          $prefix = preg_replace($public_search, '', $private_folder);
          $prefix = trim($prefix, '/');

          if (mb_strpos($uri, 'public://' . $prefix . '/') === 0) {
            throw new CrossSchemeAccessException("Cross scheme access attempt blocked");
          }
        }
        break;

      case 'private':
        // Public folder may be nested under private folder.
        if (mb_strpos($public_folder, $private_folder) === 0) {
          $private_search = '#^' . $private_folder . '#';
          $prefix = preg_replace($private_search, '', $public_folder);
          $prefix = trim($prefix, '/');
          if (mb_strpos($uri, 'private://' . $prefix . '/') === 0) {
            throw new CrossSchemeAccessException("Cross scheme access attempt blocked");
          }
        }
    }

  }

}