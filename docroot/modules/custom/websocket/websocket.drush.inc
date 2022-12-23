<?php

/**
 * @file
 * Drush command to start server.
 */

use Drupal\websocket\SocketServer;

/**
 * Implements hook_drush_command().
 */
function websocket_drush_command() {
  $items = [];

  $items['start-websocket-server'] = [
    'description' => 'Start Websocket server',
  ];

  return $items;
}

/**
 * Callback function drush_websocket_start_websocket_server().
 */
function drush_websocket_start_websocket_server() {
  SocketServer::run();
}
