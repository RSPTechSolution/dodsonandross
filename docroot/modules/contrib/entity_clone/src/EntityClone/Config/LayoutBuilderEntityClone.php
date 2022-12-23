<?php

namespace Drupal\entity_clone\EntityClone\Config;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\Section;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LayoutBuilderEntityView.
 */
class LayoutBuilderEntityClone extends ConfigEntityCloneBase {

  /**
   * Uuid generator service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * LayoutBuilderEntityClone constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, $entity_type_id, UuidInterface $uuid) {
    parent::__construct($entity_type_manager, $entity_type_id);
    $this->uuidGenerator = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $entity_type->id(),
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function cloneEntity(EntityInterface $entity, EntityInterface $cloned_entity, array $properties = []) {
    /** @var $cloned_entity \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface */
    /** @var $entity \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface */
    // We need to create an entity, save it, then adjust layout builder settings
    // and save it again, because for new entities layout_builder module stacks
    // all fields into display.
    // @see \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay::preSave()
    $cloned_entity = parent::cloneEntity($entity, $cloned_entity, $properties);
    if ($cloned_entity->isLayoutBuilderEnabled()) {
      $cloned_entity->removeAllSections();
      foreach ($entity->getSections() as $section) {
        $cloned_components = [];
        foreach ($section->getComponents() as $section_component) {
          $plugin = $section_component->getPlugin();
          $component_array = $section_component->toArray();
          $deriver_id = $plugin->getPluginDefinition()['id'];
          switch ($deriver_id) {
            case 'field_block':
            case 'extra_field_block':
              $full_id = explode(PluginBase::DERIVATIVE_SEPARATOR, $plugin->getPluginId());
              $field_name = end($full_id);
              $derivative_id = $cloned_entity->getTargetEntityTypeId() . PluginBase::DERIVATIVE_SEPARATOR . $cloned_entity->getTargetBundle() . PluginBase::DERIVATIVE_SEPARATOR . $field_name;
              break;

            case 'inline_block':
              $derivative_id = $plugin->getDerivativeId();
              break;

            default:
              if ($plugin instanceof DerivativeInspectionInterface) {
                $derivative_id = $plugin->getDerivativeId();
              }
              else {
                $derivative_id = '';
              }
              break;
          }
          $cloned_plugin_id = $deriver_id . (!empty($derivative_id) ? PluginBase::DERIVATIVE_SEPARATOR . $derivative_id : '');
          $component_array['uuid'] = $this->uuidGenerator->generate();
          $component_array['configuration']['id'] = $cloned_plugin_id;
          $cloned_components[] = SectionComponent::fromArray($component_array);
        }
        // We don't expect here third-party settings, but just in case.
        $third_party_settings = [];
        foreach ($section->getThirdPartyProviders() as $third_party_provider) {
          $third_party_settings[$third_party_provider] = $section->getThirdPartySettings($third_party_provider);
        }
        $cloned_section = new Section($section->getLayoutId(), $section->getLayoutSettings(), $cloned_components, $third_party_settings);
        $cloned_entity->appendSection($cloned_section);
      }
      $cloned_entity->save();
    }
    return $cloned_entity;
  }

}
