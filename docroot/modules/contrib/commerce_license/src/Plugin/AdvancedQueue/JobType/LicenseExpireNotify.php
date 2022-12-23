<?php

namespace Drupal\commerce_license\Plugin\AdvancedQueue\JobType;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the job type to send an email notification of license expiry.
 *
 * @AdvancedQueueJobType(
 *   id = "commerce_license_expire_notify",
 *   label = @Translation("Notify license owners of expiry"),
 * )
 */
class LicenseExpireNotify extends JobTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Mail Manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $pluginManagerMail;

  /**
   * Creates a CommerceLicenseExpireNotify instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Renderer service.
   * @param \Drupal\Core\Mail\MailManagerInterface $plugin_manager_mail
   *   The Mail Manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer,
    MailManagerInterface $plugin_manager_mail
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->pluginManagerMail = $plugin_manager_mail;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job) {
    $license_id = $job->getPayload()['license_id'];
    $license_storage = $this->entityTypeManager->getStorage('commerce_license');
    /** @var \Drupal\commerce_license\Entity\License $license */
    $license = $license_storage->load($license_id);
    if (!$license) {
      return JobResult::failure('License not found.');
    }

    $owner = $license->getOwner();
    if ($owner->isAnonymous()) {
      return JobResult::failure('License owner not found.');
    }
    $to = $owner->getEmail();

    // @todo get the email address from the store that sold the product.
    // for which we'd a method on the license entity that queries for the
    // order item that refers to it.
    // this is quick temporary hack.
    $from = \Drupal::config('system.site')->get('mail');

    $params = [
      'headers' => [
        'Content-Type' => 'text/html; charset=UTF-8;',
        'Content-Transfer-Encoding' => '8Bit',
      ],
      'from' => $from,
      'subject' => $this->t('Your purchase of @license-label has now expired', [
        '@license-label' => $license->label(),
      ]),
      'license' => $license,
    ];

    $build = [
      '#theme' => 'commerce_license_expire',
      '#license_entity' => $license,
    ];

    // Allow for the purchased entity to have been deleted.
    if ($purchased_entity = $license->getPurchasedEntity()) {
      $build += [
        '#purchased_entity' => $purchased_entity,
        '#purchased_entity_url' => $purchased_entity->toUrl()->setAbsolute(),
      ];
    }

    $params['body'] = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($build) {
      return $this->renderer->render($build);
    });

    $langcode = $owner->getPreferredLangcode();

    $message = $this->pluginManagerMail->mail('commerce_license', 'license_expire', $to, $langcode, $params);

    if ($message['result']) {
      return JobResult::success();
    }
    else {
      return JobResult::failure('Unable to send expiry notification mail.');
    }
  }

}
