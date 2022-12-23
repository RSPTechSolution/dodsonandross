<?php

namespace Drupal\s3fs;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\Exception\DirectoryNotReadyException;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\FileExistsException;
use Drupal\Core\File\Exception\FileNotExistsException;
use Drupal\Core\File\Exception\FileWriteException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\s3fs\Traits\S3fsPathsTrait;
use Psr\Log\LoggerInterface;

/**
 * Provides helpers to operate on files and stream wrappers.
 *
 * PHP convience functions copy(),rename(), move_uploaded_file(), etc do not
 * check that the write buffer is successfully flushed. As such we need to
 * handle the writes ourself so we can return when an error.
 *
 * Additionally by calling putObject and copyObject we avoid the
 * StreamWrapper creating a buffer copy of the source file in.
 *
 * @see https://www.drupal.org/project/s3fs/issues/2972161
 * @see https://www.drupal.org/project/s3fs/issues/3204635
 */
class S3fsFileService implements FileSystemInterface {

  use S3fsPathsTrait;

  /**
   * The inner service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $decorated;

  /**
   * The file logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;


  /**
   * S3fs service.
   *
   * @var \Drupal\s3fs\S3fsServiceInterface
   */
  protected $s3fs;

  /**
   * The module_handler service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module_handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  /**
   * Mime Type Guessing Service.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $mimeGuesser;

  /**
   * S3fsFileService constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $decorated
   *   FileSystem Service being decorated.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   StreamWrapper manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logging service.
   * @param \Drupal\s3fs\S3fsServiceInterface $s3fs
   *   S3fs Service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config Factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module Handler service.
   * @param object $mimeGuesser
   *   Mime type guesser service.
   *   Expected to implement
   *   \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   *   or \Symfony\Component\Mime\MimeTypeGuesserInterface.
   */
  public function __construct(FileSystemInterface $decorated, StreamWrapperManagerInterface $stream_wrapper_manager, LoggerInterface $logger, S3fsServiceInterface $s3fs, ConfigFactoryInterface $configFactory, ModuleHandlerInterface $moduleHandler, $mimeGuesser) {
    $this->decorated = $decorated;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->logger = $logger;
    $this->s3fs = $s3fs;
    $this->moduleHandler = $moduleHandler;
    $this->mimeGuesser = $mimeGuesser;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function moveUploadedFile($filename, $uri) {
    $wrapper = $this->streamWrapperManager->getViaUri($uri);
    if (is_a($wrapper, 'Drupal\s3fs\StreamWrapper\S3fsStream')) {
      return $this->putObject($filename, $uri);
    }
    else {
      return $this->decorated->moveUploadedFile($filename, $uri);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function chmod($uri, $mode = NULL) {
    return $this->decorated->chmod($uri, $mode);
  }

  /**
   * {@inheritdoc}
   */
  public function unlink($uri, $context = NULL) {
    return $this->decorated->unlink($uri, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function realpath($uri) {
    return $this->decorated->realpath($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri) {
    return $this->decorated->dirname($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function basename($uri, $suffix = NULL) {
    return $this->decorated->basename($uri, $suffix);
  }

  /**
   * {@inheritdoc}
   */
  public function mkdir($uri, $mode = NULL, $recursive = FALSE, $context = NULL) {
    return $this->decorated->mkdir($uri, $mode, $recursive, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function rmdir($uri, $context = NULL) {
    return $this->decorated->rmdir($uri, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function tempnam($directory, $prefix) {
    return $this->decorated->tempnam($directory, $prefix);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove when Drupal 8.9 support ends.
   */
  public function uriScheme($uri) {
    if (method_exists($this->decorated, 'uriScheme')) {
      return $this->decorated->uriScheme($uri);
    }
    else {
      trigger_error('S3FS: FileSystem::uriScheme() has been removed in core. Use \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface::getScheme() instead. See https://www.drupal.org/node/3035273', E_USER_ERROR);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove when Drupal 8.9 support ends.
   */
  public function validScheme($scheme) {
    if (method_exists($this->decorated, 'validScheme')) {
      return $this->decorated->validScheme($scheme);
    }
    else {
      trigger_error('S3FS: FileSystem::validScheme() Has been removed in core. Use \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface::isValidScheme() instead. See https://www.drupal.org/node/3035273', E_USER_ERROR);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function copy($source, $destination, $replace = self::EXISTS_RENAME) {
    $wrapper = $this->streamWrapperManager->getViaUri($destination);
    if (is_a($wrapper, 'Drupal\s3fs\StreamWrapper\S3fsStream')) {

      $this->prepareDestination($source, $destination, $replace);
      $srcScheme = $this->streamWrapperManager->getScheme($source);
      $dstScheme = $this->streamWrapperManager->getScheme($destination);

      if ($srcScheme == $dstScheme) {
        $result = $this->copyObject($source, $destination);
      }
      else {
        $result = $this->putObject($source, $destination);
      }

      if (!$result) {
        $this->logger->error("The specified file '%source' could not be copied to '%destination'.",
          [
            '%source' => $source,
            '%destination' => $destination,
          ]);
        throw new FileWriteException("The specified file '$source' could not be copied to '$destination'.");
      }

      return $destination;
    }
    else {
      return $this->decorated->copy($source, $destination, $replace);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($path) {
    return $this->decorated->delete($path);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRecursive($path, callable $callback = NULL) {
    return $this->decorated->deleteRecursive($path, $callback);
  }

  /**
   * {@inheritdoc}
   */
  public function move($source, $destination, $replace = self::EXISTS_RENAME) {
    $wrapper = $this->streamWrapperManager->getViaUri($destination);
    if (is_a($wrapper, 'Drupal\s3fs\StreamWrapper\S3fsStream')) {
      $this->prepareDestination($source, $destination, $replace);

      // Ensure compatibility with Windows.
      // @see \Drupal\Core\File\FileSystemInterface::unlink().
      if (!$this->streamWrapperManager->isValidUri($source) && (substr(PHP_OS, 0, 3) == 'WIN')) {
        chmod($source, 0600);
      }

      // Attempt to resolve the URIs. This is necessary in certain
      // configurations (see above) and can also permit fast moves across local
      // schemes.
      $real_source = $this->realpath($source) ?: $source;

      $srcScheme = $this->streamWrapperManager->getScheme($real_source);
      $dstScheme = $this->streamWrapperManager->getScheme($destination);

      if ($srcScheme == $dstScheme) {
        $result = $this->copyObject($real_source, $destination);
      }
      else {
        // Both sources are not on the same StreamWrapper.
        // Fall back to slow copy and unlink procedure.
        $result = $this->putObject($real_source, $destination);
      }

      if (!$result) {
        $this->logger->error("The specified file '%source' could not be moved to '%destination'.", [
          '%source' => $source,
          '%destination' => $destination,
        ]);
        throw new FileWriteException("The specified file '$source' could not be moved to '$destination'.");
      }
      else {
        if (!@unlink($real_source)) {
          $this->logger->error("The source file '%source' could not be unlinked after copying to '%destination'.", [
            '%source' => $source,
            '%destination' => $destination,
          ]);
          throw new FileException("The source file '$source' could not be unlinked after copying to '$destination'.");
        }
      }

      return $destination;
    }
    else {
      return $this->decorated->move($source, $destination, $replace);
    }
  }

  /**
   * Prepares the destination for a file copy or move operation.
   *
   * - Checks if $source and $destination are valid and readable/writable.
   * - Checks that $source is not equal to $destination; if they are an error
   *   is reported.
   * - If file already exists in $destination either the call will error out,
   *   replace the file or rename the file based on the $replace parameter.
   *
   * @param string $source
   *   A string specifying the filepath or URI of the source file.
   * @param string|null $destination
   *   A URI containing the destination that $source should be moved/copied to.
   *   The URI may be a bare filepath (without a scheme) and in that case the
   *   default scheme (file://) will be used.
   * @param int $replace
   *   Replace behavior when the destination file already exists:
   *   - FileSystemInterface::EXISTS_REPLACE - Replace the existing file.
   *   - FileSystemInterface::EXISTS_RENAME - Append _{incrementing number}
   *     until the filename is unique.
   *   - FileSystemInterface::EXISTS_ERROR - Do nothing and return FALSE.
   *
   * @see \Drupal\Core\File\FileSystemInterface::copy()
   * @see \Drupal\Core\File\FileSystemInterface::move()
   */
  protected function prepareDestination($source, &$destination, $replace) {
    $original_source = $source;

    if (!file_exists($source)) {
      if (($realpath = $this->realpath($original_source)) !== FALSE) {
        $this->logger->error("File '%original_source' ('%realpath') could not be copied because it does not exist.", [
          '%original_source' => $original_source,
          '%realpath' => $realpath,
        ]);
        throw new FileNotExistsException("File '$original_source' ('$realpath') could not be copied because it does not exist.");
      }
      else {
        $this->logger->error("File '%original_source' could not be copied because it does not exist.", [
          '%original_source' => $original_source,
        ]);
        throw new FileNotExistsException("File '$original_source' could not be copied because it does not exist.");
      }
    }

    // Prepare the destination directory.
    if ($this->prepareDirectory($destination)) {
      // The destination is already a directory, so append the source basename.
      $destination = $this->streamWrapperManager->normalizeUri($destination . '/' . $this->basename($source));
    }
    else {
      // Perhaps $destination is a dir/file?
      $dirname = $this->dirname($destination);
      if (!$this->prepareDirectory($dirname)) {
        $this->logger->error("The specified file '%original_source' could not be copied because the destination directory '%destination_directory' is not properly configured. This may be caused by a problem with file or directory permissions.", [
          '%original_source' => $original_source,
          '%destination_directory' => $dirname,
        ]);
        throw new DirectoryNotReadyException("The specified file '$original_source' could not be copied because the destination directory '$dirname' is not properly configured. This may be caused by a problem with file or directory permissions.");
      }
    }

    // Determine whether we can perform this operation based on overwrite rules.
    $destination = $this->getDestinationFilename($destination, $replace);
    if ($destination === FALSE) {
      $this->logger->error("File '%original_source' could not be copied because a file by that name already exists in the destination directory ('%destination').", [
        '%original_source' => $original_source,
        '%destination' => $destination,
      ]);
      throw new FileExistsException("File '$original_source' could not be copied because a file by that name already exists in the destination directory ('$destination').");
    }

    // Assert that the source and destination filenames are not the same.
    $real_source = $this->realpath($source);
    $real_destination = $this->realpath($destination);
    if ($source == $destination || ($real_source !== FALSE) && ($real_source == $real_destination)) {
      $this->logger->error("File '%source' could not be copied because it would overwrite itself.", [
        '%source' => $source,
      ]);
      throw new FileException("File '$source' could not be copied because it would overwrite itself.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function saveData($data, $destination, $replace = self::EXISTS_RENAME) {
    // Write the data to a temporary file.
    $temp_name = $this->tempnam('temporary://', 'file');
    if (file_put_contents($temp_name, $data) === FALSE) {
      $this->logger->error("Temporary file '%temp_name' could not be created.", ['%temp_name' => $temp_name]);
      throw new FileWriteException("Temporary file '$temp_name' could not be created.");
    }

    // Move the file to its final destination.
    return $this->move($temp_name, $destination, $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareDirectory(&$directory, $options = self::MODIFY_PERMISSIONS) {
    return $this->decorated->prepareDirectory($directory, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationFilename($destination, $replace) {
    return $this->decorated->getDestinationFilename($destination, $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function createFilename($basename, $directory) {
    return $this->decorated->createFilename($basename, $directory);
  }

  /**
   * {@inheritdoc}
   */
  public function getTempDirectory() {
    return $this->decorated->getTempDirectory();
  }

  /**
   * {@inheritdoc}
   */
  public function scanDirectory($dir, $mask, array $options = []) {
    return $this->decorated->scanDirectory($dir, $mask, $options);
  }

  /**
   * Upload a file that is not in the bucket.
   *
   * @param string $source
   *   Source file to be copied.
   * @param string $destination
   *   Destination path in bucket.
   *
   * @return bool
   *   True if successful, else FALSE.
   */
  protected function putObject($source, $destination) {
    // We only need to convert relative path for storing on the bucket.
    $destination = $this->resolvePath($destination);

    $this->preventCrossSchemeAccess($destination);

    if (mb_strlen($destination) > S3fsServiceInterface::MAX_URI_LENGTH) {
      $this->logger->error("The specified file '%destination' exceeds max URI length limit.",
        [
          '%destination' => $destination,
        ]);
      return FALSE;
    }

    // Prohibit objects with UTF8 4-byte characters due to SQL limits.
    // @see https://www.drupal.org/project/s3fs/issues/3266062
    if (preg_match('/[\x{10000}-\x{10FFFF}]/u', $destination)) {
      $this->logger->error("The specified file '%destination' contains UTF8 4-byte characters.",
        [
          '%destination' => $destination,
        ]);
      return FALSE;
    }

    $config = $this->configFactory->get('s3fs.settings')->get();
    $wrapper = $this->streamWrapperManager->getViaUri($destination);

    $scheme = $this->streamWrapperManager->getScheme($destination);
    $key_path = $this->streamWrapperManager->getTarget($destination);

    if ($scheme === 'public') {
      $target_folder = !empty($config['public_folder']) ? $config['public_folder'] . '/' : 's3fs-public/';
      $key_path = $target_folder . $key_path;
    }
    elseif ($scheme === 'private') {
      $target_folder = !empty($config['private_folder']) ? $config['private_folder'] . '/' : 's3fs-private/';
      $key_path = $target_folder . $key_path;
    }

    if (!empty($config['root_folder'])) {
      $key_path = $config['root_folder'] . '/' . $key_path;
    }

    if (method_exists($this->mimeGuesser, 'guessMimeType')) {
      $contentType = $this->mimeGuesser->guessMimeType($key_path);
    }
    else {
      $contentType = $this->mimeGuesser->guess($key_path);
    }

    $uploadParams = [
      'Bucket' => $config['bucket'],
      'Key' => $key_path,
      'SourceFile' => $source,
      'ContentType' => $contentType,
    ];

    if (!empty($config['encryption'])) {
      $uploadParams['ServerSideEncryption'] = $config['encryption'];
    }

    // Set the Cache-Control header, if the user specified one.
    if (!empty($config['cache_control_header'])) {
      $uploadParams['CacheControl'] = $config['cache_control_header'];
    }

    $uploadAsPrivate = Settings::get('s3fs.upload_as_private');

    if ($scheme !== 'private' && !$uploadAsPrivate) {
      $uploadParams['ACL'] = 'public-read';
    }

    $this->moduleHandler->alter('s3fs_upload_params', $uploadParams);

    $s3 = $this->s3fs->getAmazonS3Client($config);
    try {
      $s3->putObject($uploadParams);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    $wrapper->writeUriToCache($destination);
    return TRUE;
  }

  /**
   * Copy a file that is already in the the bucket.
   *
   * @param string $source
   *   Source file to be copied.
   * @param string $destination
   *   Destination path in bucket.
   *
   * @return bool
   *   True if successful, else FALSE.
   */
  protected function copyObject($source, $destination) {
    $source = $this->resolvePath($source);
    $destination = $this->resolvePath($destination);

    $this->preventCrossSchemeAccess($source);
    $this->preventCrossSchemeAccess($destination);

    if (mb_strlen($destination) > S3fsServiceInterface::MAX_URI_LENGTH) {
      $this->logger->error("The specified file '%destination' exceeds max URI length limit.",
        [
          '%destination' => $destination,
        ]);
      return FALSE;
    }

    // Prohibit objects with UTF8 4-byte characters due to SQL limits.
    // @see https://www.drupal.org/project/s3fs/issues/3266062
    if (preg_match('/[\x{10000}-\x{10FFFF}]/u', $destination)) {
      $this->logger->error("The specified file '%destination' contains UTF8 4-byte characters.",
        [
          '%destination' => $destination,
        ]);
      return FALSE;
    }

    $config = $this->configFactory->get('s3fs.settings')->get();

    $wrapper = $this->streamWrapperManager->getViaUri($destination);

    $scheme = $this->streamWrapperManager->getScheme($destination);
    $key_path = $this->streamWrapperManager->getTarget($destination);
    $src_key_path = $this->streamWrapperManager->getTarget($source);

    if ($scheme === 'public') {
      $target_folder = !empty($config['public_folder']) ? $config['public_folder'] . '/' : 's3fs-public/';
      $key_path = $target_folder . $key_path;
      $src_key_path = $target_folder . $src_key_path;
    }
    elseif ($scheme === 'private') {
      $target_folder = !empty($config['private_folder']) ? $config['private_folder'] . '/' : 's3fs-private/';
      $key_path = $target_folder . $key_path;
      $src_key_path = $target_folder . $src_key_path;
    }

    if (!empty($config['root_folder'])) {
      $key_path = $config['root_folder'] . '/' . $key_path;
      $src_key_path = $config['root_folder'] . '/' . $src_key_path;
    }

    if (method_exists($this->mimeGuesser, 'guessMimeType')) {
      $contentType = $this->mimeGuesser->guessMimeType($key_path);
    }
    else {
      $contentType = $this->mimeGuesser->guess($key_path);
    }

    $s3 = $this->s3fs->getAmazonS3Client($config);

    $copyParams = [
      'Bucket' => $config['bucket'],
      'Key' => $key_path,
      'CopySource' => $s3::encodeKey($config['bucket'] . '/' . $src_key_path),
      'ContentType' => $contentType,
      'MetadataDirective' => 'REPLACE',
    ];

    if (!empty($config['encryption'])) {
      $copyParams['ServerSideEncryption'] = $config['encryption'];
    }

    // Set the Cache-Control header, if the user specified one.
    if (!empty($config['cache_control_header'])) {
      $copyParams['CacheControl'] = $config['cache_control_header'];
    }

    $uploadAsPrivate = Settings::get('s3fs.upload_as_private');

    if ($scheme !== 'private' && !$uploadAsPrivate) {
      $copyParams['ACL'] = 'public-read';
    }

    $this->moduleHandler->alter('s3fs_copy_params_alter', $copyParams);

    try {
      $s3->copyObject($copyParams);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    $wrapper->writeUriToCache($destination);
    return TRUE;
  }

}
