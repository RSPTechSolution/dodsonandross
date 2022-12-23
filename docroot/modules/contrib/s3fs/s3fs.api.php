<?php

/**
 * @file
 * This file contains no working PHP code.
 *
 * It exists to provide additional documentation for doxygen as well as to
 * document hooks in the standard Drupal manner.
 */

/**
 * @defgroup s3fs_hooks S3 File System hooks
 * Hooks that can be implemented by other modules to extend S3 File System.
 */

/**
 * Alters the format and options used when creating an external URL.
 *
 * For example the URL can be a URL directly to the file, or can be a URL to a
 * torrent. In addition, it can be authenticated (time limited), and in that
 * case a save-as can be forced.
 *
 * @param array $url_settings
 *   Associative array of URL settings:
 *     - 'torrent': (boolean) Should the file should be sent via BitTorrent?
 *     - 'presigned_url': (boolean) Triggers use of an authenticated URL.
 *     - 'timeout': (int) Time in seconds before a pre-signed URL times out.
 *     - 'api_args': array of additional arguments to the getObject() function:
 *       http://docs.aws.amazon.com/aws-sdk-php/latest/class-Aws.S3.S3Client.html#_getObject
 *     - 'custom_GET_args': (array) Implementing this hook allows you to add
 *       your own set of custom GET arguments to the S3 URLs of your files.
 *       If your custom args' keys start with "x-", S3 will ignore them, but
 *       still log them:
 *       http://docs.aws.amazon.com/AmazonS3/latest/dev/LogFormat.html#LogFormatCustom.
 * @param string $s3_file_path
 *   The path to the file within your S3 bucket. This includes the prefixes
 *   which might be added (e.g. s3fs-public/ for public:// files, or the
 *   S3FS Root Folder setting).
 */
function hook_s3fs_url_settings_alter(array &$url_settings, $s3_file_path) {
  // An example of what you might want to do with this hook.
  if ($s3_file_path == 'myfile.jpg') {
    $url_settings['presigned_url'] = TRUE;
    $url_settings['timeout'] = 10;
  }

  // An example of adding a custom GET argument to all S3 URLs that
  // records the name of the currently logged in user.
  $account = Drupal::currentUser();
  $url_settings['custom_GET_args']['x-user'] = $account->getAccountName();
}

/**
 * Alters the S3 file parameters when stream is opened.
 *
 * @param array $stream_params
 *   Associative array of upload settings.
 * @param string $s3_file_path
 *   The path to the file within your S3 bucket. This includes the prefixes
 *   which might be added (e.g. s3fs-public/ for public:// files, or the
 *   S3FS Root Folder setting).
 *
 * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#getobject
 */
function hook_s3fs_stream_open_params_alter(array &$stream_params, string $s3_file_path) {
  if (strpos($s3_file_path, 'private/') !== FALSE) {
    $stream_params['SSECustomerAlgorithm'] = 'AES256';
    $stream_params['SSECustomerKey'] = 'MySecureKey';
  }
}

/**
 * Alters the S3 file parameters when uploading an object.
 *
 * @param array $upload_params
 *   Associative array of upload settings.
 *
 * @see http://docs.aws.amazon.com/aws-sdk-php/latest/class-Aws.S3.S3Client.html#_putObject
 */
function hook_s3fs_upload_params_alter(array &$upload_params) {
  if (strpos($upload_params['Key'], 'private/') !== FALSE) {
    $upload_params['ACL'] = 'private';
    $upload_params['SSECustomerAlgorithm'] = 'AES256';
    $upload_params['SSECustomerKey'] = 'MySecureSecureKey';
  }
}

/**
 * Alters the S3 parameters when copying/renaming.
 *
 * @param array $copy_params
 *   Associative array of copy settings.
 * @param array $s3_key_paths
 *   Associative array of key paths inside S3 bucket including any
 *   prefixes which might be added (e.g. s3fs-public/ for public:// files,
 *   or the S3FS Root Folder setting).
 *     - 'from_key': Key path for source file.
 *     - 'to_key': Key path for destination file.
 *
 * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#copyobject
 */
function hook_s3fs_copy_params_alter(array &$copy_params, array $s3_key_paths) {
  if (strpos($s3_key_paths['from_key'], 'private/') !== FALSE) {
    // Allow the source to be decrypted.
    $copy_params['CopySourceSSECustomerAlgorithm'] = 'AES256';
    $copy_params['CopySourceSSECustomerKey'] = 'MySecureKey';
  }
  if (strpos($s3_key_paths['to_key'], 'private/') !== FALSE) {
    // We should encrypt the destination file.
    $copy_params['SSECustomerAlgorithm'] = 'AES256';
    $copy_params['SSECustomerKey'] = 'MySecureSecureKey';
  }
}

/**
 * Alters the S3 parameters returned by getCommandParams().
 *
 * This impacts calls such obtaining metadata.
 *
 * @param array $command_params
 *   Associative array of upload settings.
 *
 * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#headobject
 */
function hook_s3fs_command_params_alter(array &$command_params) {
  if (strpos($command_params['Key'], 'private/') !== FALSE) {
    $command_params['SSECustomerAlgorithm'] = 'AES256';
    $command_params['SSECustomerKey'] = 'MySecureSecureKey';
  }
}
