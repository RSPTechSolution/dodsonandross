<?php
$ret = require_once '/var/acquia/drupal/helpers.php';
if ($ret !== true) {
  if (empty(getenv('ACQUIA_HOSTING_DRUPAL_LOG_EARLY')) && function_exists('drupal_register_shutdown_function')) {
    drupal_register_shutdown_function(
      'acquia_hosting_drupal_log',
      getenv('REQUEST_TIME_FLOAT') ? getenv('REQUEST_TIME_FLOAT') : microtime(TRUE),
      TRUE
    );
  }
  else {
    acquia_hosting_drupal_log(getenv('REQUEST_TIME_FLOAT') ? getenv('REQUEST_TIME_FLOAT') : microtime(TRUE), FALSE);
  }
}

if (defined('Drupal::VERSION')) {
  if (empty($settings['acquia_cloud_config_included'])) {
    $ah_filename = '/mnt/env-config/default.settings.inc';
    require '/var/acquia/drupal/require_with_retries.php';
    if (!defined('AH_NO_MEMCACHE_INCLUDE')) {
      require '/var/acquia/drupal/cloud-memcache-d8+.php';
    }
    $settings['acquia_cloud_config_included'] = true;
  }
  include_once '/var/acquia/drupal/acquia_hosting_db_choose_active.php';
} elseif (defined('VERSION')) {
  if (version_compare(VERSION, '7', '>=')) {
    if (empty($conf['acquia_cloud_config_included'])) {
      $ah_filename = '/mnt/env-config/default.d7-settings.inc';
      require '/var/acquia/drupal/require_with_retries.php';
      if (!defined('AH_NO_MEMCACHE_INCLUDE')) {
        require '/var/acquia/drupal/cloud-memcache-d7.php';
      }
      $conf['acquia_cloud_config_included'] = true;
    }
    include_once '/var/acquia/drupal/acquia_hosting_db_choose_active-d7.php';
  }
}

# Reduce error reporting in production to reduce noise in drupal-watchdog
if (isset($_ENV["AH_PRODUCTION"]) && $_ENV["AH_PRODUCTION"] == 1) {
  error_reporting(\E_ALL & ~\E_NOTICE & ~\E_STRICT & ~\E_DEPRECATED);
} else {
  error_reporting(\E_ALL);
}
putenv('acquia_hosting_db_choose_active_enabled=true');
// Set the default $databases if available.
if (
    (
			!isset($conf['acquia_hosting_settings_autoconnect']) || 
			$conf['acquia_hosting_settings_autoconnect'] == TRUE
		) && 
		!empty($conf['acquia_hosting_site_info']['db'])
	) {
  acquia_hosting_db_choose_active($conf['acquia_hosting_site_info']['db'], 'default', $databases, $conf);
}
