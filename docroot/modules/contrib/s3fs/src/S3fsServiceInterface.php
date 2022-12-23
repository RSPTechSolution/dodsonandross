<?php

namespace Drupal\s3fs;

/**
 * S3fs service interface.
 */
interface S3fsServiceInterface {

  /**
   * Max file URI length.
   *
   * Max limit that a file including its root StreamWrapper handler.
   *  This must match the size of the URI database field.
   *
   * @const
   */
  const MAX_URI_LENGTH = 255;

  /**
   * Validate the S3fs config.
   *
   * @param array $config
   *   Array of configuration settings from which to configure the client.
   *
   * @return array
   *   Empty array if configuration is valid, errors array otherwise.
   */
  public function validate(array $config);

  /**
   * Sets up the S3Client object.
   *
   * @param array $config
   *   Array of configuration settings from which to configure the client.
   *
   * @return \Aws\S3\S3Client
   *   The fully-configured S3Client object.
   *
   * @throws \Drupal\s3fs\S3fsException
   *   Exception when a known error occurs.
   */
  public function getAmazonS3Client(array $config);

  /**
   * Refreshes the metadata cache.
   *
   * Iterates over the full list of objects in the s3fs_root_folder within S3
   * bucket (or the entire bucket, if no root folder has been set), caching
   * their metadata in the database.
   *
   * It then caches the ancestor folders for those files, since folders are not
   * normally stored as actual objects in S3.
   *
   * @param array $config
   *   An s3fs configuration array.
   */
  public function refreshCache(array $config);

  /**
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
  public function convertMetadata($uri, array $s3_metadata);

  /**
   * Get existing folders stored in the cached meta data.
   */
  public function getExistingFolders();

  /**
   * Setup the temporary table.
   */
  public function setupTempTable();

  /**
   * Writes metadata to the temp table in the database.
   *
   * @param array $file_metadata_list
   *   An array passed by reference, which contains the current page of file
   *   metadata. This function empties out $file_metadata_list at the end.
   * @param array $folders
   *   An associative array keyed by folder name, which is populated with the
   *   ancestor folders of each file in $file_metadata_list.
   */
  public function writeTemporaryMetadata(array &$file_metadata_list, array &$folders);

  /**
   * Write the folders list to the databsae.
   *
   * @param array $folders
   *   The complete list of folders.
   *
   * @throws \Exception
   */
  public function writeFolders(array $folders);

  /**
   * Set up the final tables from the temp tables.
   */
  public function setTables();

  /**
   * Return arguments for use in listObjectVersions.
   *
   * @param array $config
   *   The S3 bucket configuration.
   *
   * @return array
   *   An array of arguments.
   */
  public static function getListObjectVersionArgs(array $config);

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
  public function getObjectMetadata(array &$file_metadata_list, array &$folders, array $s3_metadata, array $config);

}
