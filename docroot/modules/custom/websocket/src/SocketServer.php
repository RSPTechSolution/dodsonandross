<?php

namespace Drupal\websocket;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

/**
 * Class SocketServer.
 *
 * @package Drupal\websocket
 */
class SocketServer {

  /**
   * Starts websocket server.
   */
  public static function run() {
    $server = IoServer::factory(
      new HttpServer(
        new WsServer(
          new Chat()
        )
      ),
      3000
    );

    $server->run();

  }

}
