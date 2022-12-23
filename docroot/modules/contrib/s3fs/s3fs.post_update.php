<?php

/**
 * @file
 * Post update functions for s3fs.
 */

/**
 * Remove obsolete no_rewrite_cssjs config entry (removed in alpha17).
 */
function s3fs_post_update_delete_no_rewrite_cssjs_setting() {
  \Drupal::configFactory()->getEditable('s3fs.settings')
    ->clear('no_rewrite_cssjs')
    ->save();
}

/**
 * Migrate Instance Profile settings.
 */
function s3fs_post_update_migrate_instance_profile_settings() {
  $config = \Drupal::configFactory()
    ->getEditable('s3fs.settings');

  if (empty($config->get('use_instance_profile') && !empty($config->get('credentials_file')))) {
    $config->set('credentials_file', '');
  }

  $config->clear('use_instance_profile')
    ->save();
}

/**
 * Add default config settings for credentials caching.
 */
function s3fs_post_update_add_credentials_cache_default() {
  $config = \Drupal::configFactory()
    ->getEditable('s3fs.settings');

  $config->set('use_credentials_cache', FALSE)
    ->set('credentials_cache_dir', '')
    ->save();
}

/**
 * Set new config property "disable_version_sync" to default value.
 */
function s3fs_post_update_add_disable_version_sync_default() {
  \Drupal::configFactory()
    ->getEditable('s3fs.settings')
    ->set('disable_version_sync', FALSE)
    ->save(TRUE);
}

/**
 * Set new config property default value for "read_only".
 */
function s3fs_post_update_add_read_only_default() {
  \Drupal::configFactory()
    ->getEditable('s3fs.settings')
    ->set('read_only', 0)
    ->save();
}

/**
 * Migrate custom S3 bucket hostname format.
 */
function s3fs_post_update_migrate_hostname_setting_format() {
  $config = \Drupal::configFactory()
    ->getEditable('s3fs.settings');

  $useHttps = !empty($config->get('use_https'));
  $hostname = $config->get('hostname');
  if (!empty($hostname) && $useHttps) {
    $config->set('hostname', 'https://' . $hostname)->save();
  }
  elseif (!empty($hostname)) {
    $config->set('hostname', 'http://' . $hostname)->save();
  }

}

/**
 * Remove access_key and secret_key from the config table.
 */
function s3fs_post_update_delete_access_config() {
  $config = \Drupal::configFactory()
    ->getEditable('s3fs.settings');

  $config->clear('access_key')
    ->clear('secret_key')
    ->save();
}

/**
 * Set new config property default value for "disable_cert_verify".
 */
function s3fs_post_update_add_default_disable_cert_verify() {
  \Drupal::configFactory()
    ->getEditable('s3fs.settings')
    ->set('disable_cert_verify', FALSE)
    ->save(TRUE);
}

/**
 * Set new config property default value for "domain_root".
 */
function s3fs_post_update_add_default_domain_root() {
  \Drupal::configFactory()
    ->getEditable('s3fs.settings')
    ->set('domain_root', 'none')
    ->save(TRUE);
}

/**
 * Add default config entries for key module support.
 */
function s3fs_post_update_keymodule_config() {
  $config = \Drupal::configFactory()
    ->getEditable('s3fs.settings');

  $config->set('keymodule.access_key_name', '')
    ->set('keymodule.secret_key_name', '')
    ->save();
}

/**
 * Add default config entries disable_shared_config_files.
 */
function s3fs_post_update_add_disable_shared_config_files() {
  $config = \Drupal::configFactory()
    ->getEditable('s3fs.settings');

  $config->set('disable_shared_config_files', TRUE)
    ->save(TRUE);
}
