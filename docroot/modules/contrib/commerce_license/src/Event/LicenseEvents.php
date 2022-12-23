<?php

namespace Drupal\commerce_license\Event;

final class LicenseEvents {

  /**
   * Name of the event fired after loading a license.
   *
   * @Event
   *
   * @see \Drupal\commerce_license\Event\LicenseEvent
   */
  const LICENSE_LOAD = 'commerce_license.commerce_license.load';

  /**
   * Name of the event fired after creating a new license.
   *
   * Fired before the license is saved.
   *
   * @Event
   *
   * @see  \Drupal\commerce_license\Event\LicenseEvent
   */
  const LICENSE_CREATE = 'commerce_license.commerce_license.create';

  /**
   * Name of the event fired before saving a license.
   *
   * @Event
   *
   * @see \Drupal\commerce_license\Event\LicenseEvent
   */
  const LICENSE_PRESAVE = 'commerce_license.commerce_license.presave';

  /**
   * Name of the event fired after saving a new license.
   *
   * @Event
   *
   * @see \Drupal\commerce_license\Event\LicenseEvent
   */
  const LICENSE_INSERT = 'commerce_license.commerce_license.insert';

  /**
   * Name of the event fired after saving an existing license.
   *
   * @Event
   *
   * @see \Drupal\commerce_license\Event\LicenseEvent
   */
  const LICENSE_UPDATE = 'commerce_license.commerce_license.update';

  /**
   * Name of the event fired before deleting a license.
   *
   * @Event
   *
   * @see \Drupal\commerce_license\Event\LicenseEvent
   */
  const LICENSE_PREDELETE = 'commerce_license.commerce_license.predelete';

  /**
   * Name of the event fired after deleting a license.
   *
   * @Event
   *
   * @see \Drupal\commerce_license\Event\LicenseEvent
   */
  const LICENSE_DELETE = 'commerce_license.commerce_license.delete';

}
