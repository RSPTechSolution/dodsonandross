<?php

namespace Drupal\websocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * Chat class.
 */
class Chat implements MessageComponentInterface {
  protected $clients;

  /**
   * Chat constructor.
   */
  public function __construct() {
    $this->clients = new \SplObjectStorage();
    echo 'Server Started';
  }

  /**
   * {@inheritdoc}
   */
  public function onOpen(ConnectionInterface $conn) {
    $this->clients->attach($conn);
    echo "New connection {$conn->resourceId}\n";
  }

  /**
   * {@inheritdoc}
   */
  public function onMessage(ConnectionInterface $from, $msg) {
    foreach ($this->clients as $client) {
      $client->send($msg);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onClose(ConnectionInterface $conn) {
    $this->clients->detach($conn);

    echo "Connection {$conn->resourceId} has disconnected\n";
  }

  /**
   * {@inheritdoc}
   */
  public function onError(ConnectionInterface $conn, \Exception $e) {
    echo "An error occured {$e->getMessage()}\n";

    $conn->close();
  }

}
