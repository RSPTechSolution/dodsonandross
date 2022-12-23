<?php

/**
 * @file
 * Entity Clone hooks and events.
 */

/**
 * Event subscribers for Entity Clone.
 *
 * Service definition for my_module.services.yml:
 * <code>
 * ```yaml
 *  my_module.my_event_subscriber:
 *    class: Drupal\my_module\EventSubscriber\MyEntityCloneEventSubscriber
 *    tags:
 *     - { name: event_subscriber }
 * ```
 * </code>
 *
 * Code for src/EventSubscriber/MyEntityCloneEventSubscriber.php
 * <code>
 * <?php
 * namespace Drupal\my_module\EventSubscriber;
 * ?>
 * </code>
 */
class MyEntityCloneEventSubscriber implements \Symfony\Component\EventDispatcher\EventSubscriberInterface {

  /**
   * An example event subscriber.
   *
   * Dispatched before an entity is cloned and saved.
   *
   * @see \Drupal\entity_clone\Event\EntityCloneEvents::PRE_CLONE
   */
  public function myPreClone(\Drupal\entity_clone\Event\EntityCloneEvent $event): void {
    $original = $event->getEntity();
    $newEntity = $event->getClonedEntity();
  }

  /**
   * An example event subscriber.
   *
   * Dispatched after an entity is cloned and saved.
   *
   * @see \Drupal\entity_clone\Event\EntityCloneEvents::POST_CLONE
   */
  public function myPostClone(\Drupal\entity_clone\Event\EntityCloneEvent $event): void {
    $original = $event->getEntity();
    $newEntity = $event->getClonedEntity();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[\Drupal\entity_clone\Event\EntityCloneEvents::PRE_CLONE][] = ['myPreClone'];
    $events[\Drupal\entity_clone\Event\EntityCloneEvents::POST_CLONE][] = ['myPostClone'];
    return $events;
  }

}
