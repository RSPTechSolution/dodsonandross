<?php

namespace Drupal\s3fs;

use Aws\Credentials\CredentialProvider;
use Aws\DoctrineCacheAdapter;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Doctrine\Common\Cache\FilesystemCache;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\s3fs\StreamWrapper\S3fsStream;

/**
 * Defines a S3fsService service.
 */
class S3fsService implements S3fsServiceInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * An object for obtaining the system time.
   *
   * @var \Drupal\Component\DateTime\TimeInterface
   */
  protected $time;

  /**
   * Default 'safe' S3 region.
   *
   * @const
   */
  const DEFAULT_S3_REGION = 'us-east-1';

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs an S3fsService object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The new database connection object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory object.
   * @param \Drupal\Component\DateTime\TimeInterface $time
   *   An object to obtain the system time.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $config_factory, TimeInterface $time, ModuleHandlerInterface $module_handler) {
    $this->connection = $connection;
    $this->configFactory = $config_factory;
    $this->time = $time;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $config) {
    $errors = [];
    if (!class_exists('Aws\S3\S3Client')) {
      $errors[] = $this->t('Cannot load Aws\S3\S3Client class. Please ensure that the aws sdk php library is installed correctly.');
    }

    if (!empty($config['credentials_file'])) {
      if (!is_file($config['credentials_file']) || !is_readable($config['credentials_file'])) {
        $errors[] = $this->t(
          "Unable to read Custom Credentials file. Please verify @file exists
           and permissions are valid.",
          ['@file' => $config['credentials_file']]
        );
      }
    }

    if (empty($config['bucket'])) {
      $errors[] = $this->t('Your AmazonS3 bucket name is not configured.');
    }

    if (!empty($config['use_customhost']) && empty($config['hostname'])) {
      $errors[] = $this->t('You must specify a Hostname to use Custom Host feature.');
    }
    if (!empty($config['use_cname']) && empty($config['domain'])) {
      $errors[] = $this->t('You must specify a CDN Domain Name to use CNAME feature.');
    }

    switch ($config['domain_root']) {
      case 'root':
        if (empty($config['root_folder'])) {
          $errors[] = $this->t('You must specify a Root folder to map the Domain Name to it.');
        }
        break;

      default:
        break;
    }

    try {
      $s3 = $this->getAmazonS3Client($config);
    }
    catch (\Exception $e) {
      $errors[] = $this->t(
        'An unexpected error occurred obtaining S3Client . @message',
        ['@message' => $e->getMessage()]
      );
    }

    // Test the connection to S3, bucket name and WRITE|READ ACL permissions.
    // These actions will trigger descriptive exceptions if the credentials,
    // bucket name, or region are invalid/mismatched.
    $date = date('dmy-Hi');
    $key_path = "s3fs-tests-results";
    if (!empty($config['root_folder'])) {
      $key_path = "{$config['root_folder']}/$key_path";
    }
    $key = "{$key_path}/write-test-{$date}.txt";
    $successPut = FALSE;
    $successDelete = FALSE;
    $exceptionCaught = FALSE;
    try {
      $putOptions = [
        'Body' => 'Example file uploaded successfully.',
        'Bucket' => $config['bucket'],
        'Key' => $key,
      ];
      if (!empty($config['encryption'])) {
        $putOptions['ServerSideEncryption'] = $config['encryption'];
      }

      // Set the Cache-Control header, if the user specified one.
      if (!empty($config['cache_control_header'])) {
        $putOptions['CacheControl'] = $config['cache_control_header'];
      }

      $s3->putObject($putOptions);
      $object = $s3->getObject(['Bucket' => $config['bucket'], 'Key' => $key]);
      if ($object) {
        $successPut = TRUE;
        $s3->deleteObject(['Bucket' => $config['bucket'], 'Key' => $key]);
        $successDelete = TRUE;
      }
    }
    catch (\Exception $e) {
      $exceptionCaught = $e;
    }

    if (!empty($config['read_only']) && ($successPut || $successDelete)) {
      // We were able to upload or delete a file when bucket is
      // tagged read-only.
      $errors[] = $this->t('The provided credentials are not read-only.');
    }
    elseif ($exceptionCaught) {
      // Bucket is read+write but we had an exception above.
      $errors[] = $this->t(
        'An unexpected error occurred. @message',
        ['@message' => $exceptionCaught->getMessage()]);
    }

    if (empty($config['read_only']) && !Settings::get('s3fs.upload_as_private')) {
      try {
        $key = "{$key_path}/public-write-test-{$date}.txt";
        $putOptions = [
          'Body' => 'Example public file uploaded successfully.',
          'Bucket' => $config['bucket'],
          'Key' => $key,
          'ACL' => 'public-read',
        ];
        if (!empty($config['encryption'])) {
          $putOptions['ServerSideEncryption'] = $config['encryption'];
        }
        $s3->putObject($putOptions);
        if ($object = $s3->getObject([
          'Bucket' => $config['bucket'],
          'Key' => $key,
        ])) {
          $s3->deleteObject(['Bucket' => $config['bucket'], 'Key' => $key]);
        }
      }
      catch (S3Exception $e) {
        $errors[] = $this->t(
          "Could not upload file as publicly accessible. If the bucket security
          policy is set to BlockPublicAcl ensure that upload_as_private is enabled
          in your settings.php \$settings['s3fs.upload_as_private'] = TRUE;"
        );
        $errors[] = $this->t('Error message: @message', ['@message' => $e->getMessage()]);
      }
      catch (\Exception $e) {
        $errors[] = $this->t(
          'An unexpected error occurred. @message',
          ['@message' => $e->getMessage()]);
      }
    }

    if (empty($config['disable_version_sync'])) {
      $args = $this->getListObjectVersionArgs($config);
      $args['MaxKeys'] = '1';
      try {
        $s3->listObjectVersions($args);
      }
      catch (\Exception $e) {
        $errors[] = $this->t(
          'Unable to listObjectVersions. Is listObjectVersions supported
           by your bucket? @message',
          ['@message' => $e->getMessage()]);
      }
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   *
   * Sets up the S3Client object.
   * For performance reasons, only one S3Client object will ever be created
   * within a single request.
   *
   * @param array $config
   *   Array of configuration settings from which to configure the client.
   *
   * @return \Aws\S3\S3Client
   *   The fully-configured S3Client object.
   *
   * @throws \Drupal\s3fs\S3fsException
   *   The S3fs Exception.
   */
  public function getAmazonS3Client(array $config) {
    $s3 = &drupal_static(__METHOD__ . '_S3Client');
    $static_config = &drupal_static(__METHOD__ . '_static_config');

    // If the client hasn't been set up yet, or the config given to this call is
    // different from the previous call, (re)build the client.
    if (!isset($s3) || $static_config != $config) {
      $client_config = [];

      $access_key = Settings::get('s3fs.access_key', '');
      $secret_key = Settings::get('s3fs.secret_key', '');

      $noKeyInSettings = (empty($access_key) || empty($secret_key));

      if ($noKeyInSettings && $this->moduleHandler->moduleExists('key')) {
        if (!$access_key && !empty($config['keymodule']['access_key_name'])) {
          /** @var \Drupal\key\KeyInterface|NULL $key */
          $key = \Drupal::service('key.repository')->getKey($config['keymodule']['access_key_name']);
          $key_value = $key ? $key->getKeyValue() : '';
          if (!empty($key_value)) {
            $access_key = $key_value;
          }
        }

        if (!$secret_key && !empty($config['keymodule']['secret_key_name'])) {
          /** @var \Drupal\key\KeyInterface|NULL $key */
          $key = \Drupal::service('key.repository')->getKey($config['keymodule']['secret_key_name']);
          $key_value = $key ? $key->getKeyValue() : '';
          if (!empty($key_value)) {
            $secret_key = $key_value;
          }
        }
      }

      if (!empty($access_key) && !empty($secret_key)) {
        $client_config['credentials'] = [
          'key' => $access_key,
          'secret' => $secret_key,
        ];
      }
      else {
        // Use the defaultProvider to get all paths in home, env, etc.
        $provider = CredentialProvider::defaultProvider();

        // Use a custom ini file before defaultProvider.
        if (!empty($config['credentials_file'])) {
          $iniProvider = CredentialProvider::ini(NULL, $config['credentials_file']);
          $provider = CredentialProvider::chain($iniProvider, $provider);
        }
        // Cache the results in a memoize function to avoid loading and parsing
        // the ini file on every API operation.
        $provider = CredentialProvider::memoize($provider);

        // Allow SDK to cache results of calls to metadata servers.
        $doctrineInstalled = class_exists('\Doctrine\Common\Cache\FilesystemCache');
        if (!empty($config['use_credentials_cache']) && !empty($config['credentials_cache_dir']) && $doctrineInstalled) {
          $cache = new DoctrineCacheAdapter(new FilesystemCache($config['credentials_cache_dir'] . '/s3fscache', '.doctrine.cache', 0017));
          $provider = CredentialProvider::cache($provider, $cache);
        }

        $client_config['credentials'] = $provider;
      }

      if (!empty($config['region'])) {
        $client_config['region'] = $config['region'];
        // Signature v4 is only required in the Beijing and Frankfurt regions.
        // Also, setting it will throw an exception if a region hasn't been set.
        $client_config['signature'] = 'v4';
      }
      if (!empty($config['use_customhost']) && !empty($config['hostname'])) {
        if (preg_match('#http(s)?://#i', $config['hostname']) === 1) {
          $client_config['endpoint'] = $config['hostname'];
        }
        else {
          // Fallback for before we required hostname to include protocol.
          $client_config['endpoint'] = ($config['use_https'] ? 'https://' : 'http://') . $config['hostname'];
        }
      }
      // Use path-style endpoint, if selected.
      if (!empty($config['use_path_style_endpoint'])) {
        $client_config['use_path_style_endpoint'] = TRUE;
      }
      $client_config['version'] = S3fsStream::API_VERSION;
      // Disable SSL/TLS verification.
      if (!empty($config['disable_cert_verify'])) {
        $client_config['http']['verify'] = FALSE;
      }
      // Set use_aws_shared_config_files = false to avoid reading ~/.aws/config.
      // If open_basedir restriction is in effect an exception may be thrown
      // without this enabled.
      if (!empty($config['disable_shared_config_files'])) {
        $client_config['use_aws_shared_config_files'] = FALSE;
      }
      // Create the Aws\S3\S3Client object.
      $s3 = new S3Client($client_config);
      $static_config = $config;
    }
    return $s3;
  }

  /**
   * {@inheritdoc}
   */
  public static function getListObjectVersionArgs(array $config) {
    // Set up the args for list objects.
    $args = ['Bucket' => $config['bucket']];
    if (!empty($config['root_folder'])) {
      // If the root_folder option has been set, retrieve from S3 only those
      // files which reside in the root folder.
      $args['Prefix'] = "{$config['root_folder']}/";
    }

    return $args;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshCache(array $config) {
    $s3 = $this->getAmazonS3Client($config);
    $args = $this->getListObjectVersionArgs($config);
    try {
      $operation = empty($config['disable_version_sync']) ? "ListObjectVersions" : "ListObjectsV2";
      // Create an paginator that will emit iterators of all of the objects
      // matching the key prefix.
      $paginator = $s3->getPaginator($operation, $args);
    }
    catch (\Exception $e) {
      watchdog_exception('S3FS', $e);
      $this->messenger()->addStatus($this->t('Error refreshing cache. Please check the logs for more info.'));
      return;
    }
    $file_metadata_list = [];
    $folders = $this->getExistingFolders();
    $this->setupTempTable();

    try {
      foreach ($paginator as $result) {
        if ($result->hasKey('Versions')) {
          foreach ($result->get('Versions') as $s3_metadata) {
            $this->getObjectMetadata($file_metadata_list, $folders, $s3_metadata, $config);

            // Splits the data into manageable parts for the database.
            if (count($file_metadata_list) >= 10000) {
              $this->writeTemporaryMetadata($file_metadata_list, $folders);
            }
          }
        }
        elseif ($result->hasKey('Contents')) {
          foreach ($result->get('Contents') as $s3_metadata) {
            $this->getObjectMetadata($file_metadata_list, $folders, $s3_metadata, $config);

            // Splits the data into manageable parts for the database.
            if (count($file_metadata_list) >= 10000) {
              $this->writeTemporaryMetadata($file_metadata_list, $folders);
            }
          }
        }
      }
    }

    catch (\Exception $e) {
      watchdog_exception('S3FS', $e);
      $this->messenger()->addStatus($this->t('Error refreshing cache. Please check the logs for more info.'));
      return;
    }

    // The event listener doesn't fire after the last page is done, so we have
    // to write the last page of metadata manually.
    $this->writeTemporaryMetadata($file_metadata_list, $folders);

    // Store the folders in the database.
    $this->writeFolders($folders);

    // Set the final tables.
    $this->setTables();

    // Clear every s3fs entry in the Drupal cache.
    Cache::invalidateTags([S3FS_CACHE_TAG]);

    $this->messenger()->addStatus($this->t('S3 File System cache refreshed.'));

  }

  /**
   * {@inheritdoc}
   */
  public function writeFolders(array $folders) {
    $needsRetry = [];
    // Now that the $folders array contains all the ancestors of every file in
    // the cache, as well as the existing folders from before the refresh,
    // write those folders to the DB.
    if ($folders) {
      // Splits the data into manageable parts for the database.
      $chunks = array_chunk($folders, 10000, TRUE);
      foreach ($chunks as $chunk) {
        $insert_query = \Drupal::database()->insert('s3fs_file_temp')
          ->fields(['uri', 'filesize', 'timestamp', 'dir', 'version']);
        foreach ($chunk as $folder_uri => $ph) {
          $metadata = $this->convertMetadata($folder_uri, []);
          $insert_query->values($metadata);
        }
        // If this throws an integrity constraint violation, then the user's
        // S3 bucket has objects that represent folders using a different
        // scheme than the one we account for above.
        try {
          $insert_query->execute();
        }
        catch (IntegrityConstraintViolationException $e) {
          $this->messenger()->addError(
            $this->t(
              'An Integrity Constraint violation occurred while attempting to
                process folder records. Please see the troubleshooting section
                of the s3fs README.txt for more info.'
            )
          );
          $needsRetry = array_merge($needsRetry, $chunk);
        }
      }

      // We had an IntegrityConstraintViolationException above. Try each
      //record individually and report failures. We keep the existing record
      // and do not insert the folder record to reduce the risk of data loss.
      if (!empty($needsRetry)) {
        foreach ($needsRetry as $folder => $ph) {
          $insert_query = \Drupal::database()->insert('s3fs_file_temp')
            ->fields(['uri', 'filesize', 'timestamp', 'dir', 'version']);
          $metadata = $this->convertMetadata($folder, []);
          $insert_query->values($metadata);
          try {
            $insert_query->execute();
          }
          catch (IntegrityConstraintViolationException $e) {
            $this->messenger()->addError(
              $this->t("Integrity Constraint on folder '%folder'", ['%folder' => $folder])
            );
          }
        }
      }
    }
  }

  /**
   * Cache object meta data.
   *
   * @param array $file_metadata_list
   *   The list of files.
   * @param array $folders
   *   The list of folders.
   * @param array $s3_metadata
   *   The individual list object result.
   * @param array $config
   *   The S3 bucket configuration.
   */
  public function getObjectMetadata(array &$file_metadata_list, array &$folders, array $s3_metadata, array $config) {
    $key = $s3_metadata['Key'];
    // The root folder is an implementation detail that only appears on S3.
    // Files' URIs are not aware of it, so we need to remove it beforehand.
    if (!empty($config['root_folder'])) {
      $key = substr_replace($key, '', 0, strlen($config['root_folder']) + 1);
    }

    // Figure out the scheme based on the key's folder prefix.
    $public_folder_name = !empty($config['public_folder']) ? $config['public_folder'] : 's3fs-public';
    $private_folder_name = !empty($config['private_folder']) ? $config['private_folder'] : 's3fs-private';
    if (strpos($key, "$public_folder_name/") === 0) {
      // Much like the root folder, the public folder name must be removed
      // from URIs.
      $key = substr_replace($key, '', 0, strlen($public_folder_name) + 1);
      $uri = "public://$key";
    }
    elseif (strpos($key, "$private_folder_name/") === 0) {
      $key = substr_replace($key, '', 0, strlen($private_folder_name) + 1);
      $uri = "private://$key";
    }
    else {
      // No special prefix means it's an s3:// file.
      $uri = "s3://$key";
    }

    if (mb_strlen(rtrim($uri, '/')) > S3fsServiceInterface::MAX_URI_LENGTH) {
      return;
    }

    // Prohibit objects with UTF8 4-byte characters due to SQL limits.
    // @see https://www.drupal.org/project/s3fs/issues/3266062
    if (preg_match('/[\x{10000}-\x{10FFFF}]/u', $uri)) {
      return;
    }

    if ($uri[strlen($uri) - 1] == '/') {
      // Treat objects in S3 whose filenames end in a '/' as folders.
      // But don't store the '/' itself as part of the folder's uri.
      $folders[rtrim($uri, '/')] = TRUE;
    }
    else {
      // Only store the metadata for the latest version of the file.
      if (isset($s3_metadata['IsLatest']) && !$s3_metadata['IsLatest']) {
        return;
      }

      // Buckets with Versioning disabled set all files' VersionIds to "null".
      // If we see that, unset VersionId to prevent "null" from being written
      // to the DB.
      if (isset($s3_metadata['VersionId']) && $s3_metadata['VersionId'] == 'null') {
        unset($s3_metadata['VersionId']);
      }
      $file_metadata_list[] = $this->convertMetadata($uri, $s3_metadata);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingFolders() {
    // The $folders array is an associative array keyed by folder paths, which
    // is constructed as each filename is written to the DB. After all the files
    // are written, the folder paths are converted to metadata and written.
    $folders = [];
    // Start by gathering all the existing folders. If we didn't do this, empty
    // folders would be lost, because they'd have no files from which to rebuild
    // themselves.
    $existing_folders = \Drupal::database()->select('s3fs_file', 's')
      ->fields('s', ['uri'])
      ->condition('dir', 1, '=');
    foreach ($existing_folders->execute()->fetchCol(0) as $folder_uri) {
      $folders[rtrim($folder_uri, '/')] = TRUE;
    }
    return $folders;
  }

  /**
   * {@inheritdoc}
   */
  public function setupTempTable() {

    // Create the temp table, into which all the refreshed data will be written.
    // After the full refresh is complete, the temp table will be swapped with
    // the real one.
    module_load_install('s3fs');
    $schema = s3fs_schema();
    try {
      \Drupal::database()->schema()->dropTable('s3fs_file_temp');
      \Drupal::database()->schema()->createTable('s3fs_file_temp', $schema['s3fs_file']);
      // Due to http://drupal.org/node/2193059, the temp table fails to pick up
      // the primary key - fix things up manually.
      s3fs_fix_table_indexes('s3fs_file_temp');
    }
    catch (SchemaObjectExistsException $e) {
      // The table already exists, so we can simply truncate it to start fresh.
      \Drupal::database()->truncate('s3fs_file_temp')->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setTables() {
    // Swap the temp table with the real table.
    \Drupal::database()->schema()->renameTable('s3fs_file', 's3fs_file_old');
    \Drupal::database()->schema()->renameTable('s3fs_file_temp', 's3fs_file');
    \Drupal::database()->schema()->dropTable('s3fs_file_old');
  }

  /**
   * {@inheritdoc}
   *
   * Convert file metadata returned from S3 into a metadata cache array.
   *
   * @param string $uri
   *   The uri of the resource.
   * @param array $s3_metadata
   *   An array containing the collective metadata for the object in S3.
   *   The caller may send an empty array here to indicate that the returned
   *   metadata should represent a directory.
   *
   * @return array
   *   A file metadata cache array.
   */
  public function convertMetadata($uri, array $s3_metadata) {
    // Need to fill in a default value for everything, so that DB calls
    // won't complain about missing fields.
    $metadata = [
      'uri' => $uri,
      'filesize' => 0,
      'timestamp' => $this->time->getRequestTime(),
      'dir' => 0,
      'version' => '',
    ];

    if (empty($s3_metadata)) {
      // The caller wants directory metadata.
      $metadata['dir'] = 1;
    }
    else {
      // The filesize value can come from either the Size or ContentLength
      // attribute, depending on which AWS API call built $s3_metadata.
      if (isset($s3_metadata['ContentLength'])) {
        $metadata['filesize'] = $s3_metadata['ContentLength'];
      }
      else {
        if (isset($s3_metadata['Size'])) {
          $metadata['filesize'] = $s3_metadata['Size'];
        }
      }

      if (isset($s3_metadata['LastModified'])) {
        $metadata['timestamp'] = date('U', strtotime($s3_metadata['LastModified']));
      }

      if (isset($s3_metadata['VersionId']) && $s3_metadata['VersionId'] != 'null') {
        $metadata['version'] = $s3_metadata['VersionId'];
      }
    }
    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function writeTemporaryMetadata(array &$file_metadata_list, array &$folders) {
    if ($file_metadata_list) {
      $insert_query = \Drupal::database()->insert('s3fs_file_temp')
        ->fields(['uri', 'filesize', 'timestamp', 'dir', 'version']);

      foreach ($file_metadata_list as $metadata) {

        // Write the file metadata to the DB.
        $insert_query->values($metadata);

        // Add the ancestor folders of this file to the $folders array.
        $uri = \Drupal::service('file_system')->dirname($metadata['uri']);
        $root = StreamWrapperManager::getScheme($uri) . '://';
        // Loop through each ancestor folder until we get to the root uri.
        // Risk exists that dirname() returns a malformed uri if a
        // StreamWrapper is disabled causing a loop. Use isValidUri to avoid.
        while ($uri != $root && \Drupal::service('stream_wrapper_manager')->isValidUri($uri)) {
          $folders[$uri] = TRUE;
          $uri = \Drupal::service('file_system')->dirname($uri);
        }
      }
      $insert_query->execute();
    }

    // Empty out the file array, so it can be re-filled by the next request.
    $file_metadata_list = [];
  }

}
