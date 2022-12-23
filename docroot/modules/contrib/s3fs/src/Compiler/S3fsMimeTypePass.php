<?php

namespace Drupal\s3fs\Compiler;

use Composer\Semver\Semver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds @s3fs_mime_type_guesser tagged services.
 *
 * Handles forward/backwards compatability of MimeTypeGuesser.
 *
 * To be removed when D10 is our minimally supported version.
 *
 * @internal
 *
 * @see https://www.drupal.org/node/3133341
 */
class S3fsMimeTypePass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $consumer = $container->getDefinition('s3fs.mime_type.guesser');

    $tag = 's3fs_mime_type_guesser';
    $interface = '\Symfony\Component\Mime\MimeTypeGuesserInterface';
    $deprecated_interface = '\Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface';
    $uses_new_interfaces = Semver::satisfies(\Drupal::VERSION, '>=9.1');
    $uses_deprecated_interface = Semver::satisfies(\Drupal::VERSION, '<=9.1');

    // Find all tagged handlers.
    $handlers = [];
    foreach ($container->findTaggedServiceIds($tag) as $id => $attributes) {
      // Validate the interface.
      $handler = $container->getDefinition($id);
      if (!is_subclass_of($handler->getClass(), $interface)) {
        // Special handling for $deprecated_interface.
        if (!is_subclass_of($handler->getClass(), $deprecated_interface) || !$uses_deprecated_interface) {
          throw new LogicException("Service '$id' does not implement $interface.");
        }
      }
      $handlers[$id] = $attributes[0]['priority'] ?? 0;
      $interfaces[$id] = $handler->getClass();
    }
    if (empty($handlers)) {
      throw new LogicException(sprintf("At least one service tagged with '%s' is required.", $tag));
    }

    // Sort all handlers by priority.
    arsort($handlers, SORT_NUMERIC);

    // Add a method call for each handler to the consumer service
    // definition.
    foreach ($handlers as $id => $priority) {
      $arguments = [new Reference($id), $priority];
      if ($uses_new_interfaces && is_subclass_of($interfaces[$id], $interface)) {
        $consumer->addMethodCall('addMimeTypeGuesser', $arguments);
      }
      elseif ($uses_deprecated_interface) {
        $consumer->addMethodCall('addGuesser', $arguments);
      }
    }
  }

}
