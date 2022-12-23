<?php

namespace Drupal\block_ajax;

use Drupal\block\BlockListBuilder;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of block entities.
 *
 * @see \Drupal\block\Entity\Block
 */
class AjaxBlockListBuilder extends BlockListBuilder {

  /**
   * Builds the main "Blocks" portion of the form.
   *
   * @return array
   *   Returns form array.
   */
  protected function buildBlocksForm() {
    // Build blocks first for each region.
    $blocks = [];
    $entities = $this->load();
    /** @var \Drupal\block\BlockInterface[] $entities */
    foreach ($entities as $entity_id => $entity) {
      $definition = $entity->getPlugin()->getPluginDefinition();
      $label = $entity->label();
      if ($config = $entity->getPlugin()->getConfiguration()) {
        if (!empty($config['block_ajax']['is_ajax'])) {
          $label .= ' (Ajax loaded)';
        }
      }
      $blocks[$entity->getRegion()][$entity_id] = [
        'label' => $label,
        'entity_id' => $entity_id,
        'weight' => $entity->getWeight(),
        'entity' => $entity,
        'category' => $definition['category'],
        'status' => $entity->status(),
      ];
    }

    $form = [
      '#type' => 'table',
      '#header' => [
        $this->t('Block'),
        $this->t('Category'),
        $this->t('Region'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#attributes' => [
        'id' => 'blocks',
      ],
    ];

    // Weights range from -delta to +delta, so delta should be at least half
    // of the amount of blocks present. This makes sure all blocks in the same
    // region get a unique weight.
    $weight_delta = round(count($entities) / 2);

    $placement = FALSE;
    if ($this->request->query->has('block-placement')) {
      $placement = $this->request->query->get('block-placement');
      $form['#attached']['drupalSettings']['blockPlacement'] = $placement;
      // Remove the block placement from the current request so that it is not
      // passed on to any redirect destinations.
      $this->request->query->remove('block-placement');
    }

    // Loop over each region and build blocks.
    $regions = $this->systemRegionList($this->getThemeName(), REGIONS_VISIBLE);
    foreach ($regions as $region => $title) {
      $form['#tabledrag'][] = [
        'action' => 'match',
        'relationship' => 'sibling',
        'group' => 'block-region-select',
        'subgroup' => 'block-region-' . $region,
        'hidden' => FALSE,
      ];
      $form['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'block-weight',
        'subgroup' => 'block-weight-' . $region,
      ];

      $form['region-' . $region] = [
        '#attributes' => [
          'class' => ['region-title', 'region-title-' . $region],
          'no_striping' => TRUE,
        ],
      ];
      $form['region-' . $region]['title'] = [
        '#theme_wrappers' => [
          'container' => [
            '#attributes' => ['class' => 'region-title__action'],
          ],
        ],
        '#prefix' => $title,
        '#type' => 'link',
        '#title' => $this->t('Place block <span class="visually-hidden">in the %region region</span>', ['%region' => $title]),
        '#url' => Url::fromRoute('block.admin_library', ['theme' => $this->getThemeName()], ['query' => ['region' => $region]]),
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
        '#attributes' => [
          'class' => ['use-ajax', 'button', 'button--small'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ];

      $form['region-' . $region . '-message'] = [
        '#attributes' => [
          'class' => [
            'region-message',
            'region-' . $region . '-message',
            empty($blocks[$region]) ? 'region-empty' : 'region-populated',
          ],
        ],
      ];
      $form['region-' . $region . '-message']['message'] = [
        '#markup' => '<em>' . $this->t('No blocks in this region') . '</em>',
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
      ];

      if (isset($blocks[$region])) {
        foreach ($blocks[$region] as $info) {
          $entity_id = $info['entity_id'];

          $form[$entity_id] = [
            '#attributes' => [
              'class' => ['draggable'],
            ],
          ];
          $form[$entity_id]['#attributes']['class'][] = $info['status'] ? 'block-enabled' : 'block-disabled';
          if ($placement && $placement == Html::getClass($entity_id)) {
            $form[$entity_id]['#attributes']['class'][] = 'color-success';
            $form[$entity_id]['#attributes']['class'][] = 'js-block-placed';
          }
          $form[$entity_id]['info'] = [
            '#plain_text' => $info['status'] ? $info['label'] : $this->t('@label (disabled)', ['@label' => $info['label']]),
            '#wrapper_attributes' => [
              'class' => ['block'],
            ],
          ];
          $form[$entity_id]['type'] = [
            '#markup' => $info['category'],
          ];
          $form[$entity_id]['region-theme']['region'] = [
            '#type' => 'select',
            '#default_value' => $region,
            '#required' => TRUE,
            '#title' => $this->t('Region for @block block', ['@block' => $info['label']]),
            '#title_display' => 'invisible',
            '#options' => $regions,
            '#attributes' => [
              'class' => ['block-region-select', 'block-region-' . $region],
            ],
            '#parents' => ['blocks', $entity_id, 'region'],
          ];
          $form[$entity_id]['region-theme']['theme'] = [
            '#type' => 'hidden',
            '#value' => $this->getThemeName(),
            '#parents' => ['blocks', $entity_id, 'theme'],
          ];
          $form[$entity_id]['weight'] = [
            '#type' => 'weight',
            '#default_value' => $info['weight'],
            '#delta' => $weight_delta,
            '#title' => $this->t('Weight for @block block', ['@block' => $info['label']]),
            '#title_display' => 'invisible',
            '#attributes' => [
              'class' => ['block-weight', 'block-weight-' . $region],
            ],
          ];
          $form[$entity_id]['operations'] = $this->buildOperations($info['entity']);
        }
      }
    }

    // Do not allow disabling the main system content block when it is present.
    if (isset($form['system_main']['region'])) {
      $form['system_main']['region']['#required'] = TRUE;
    }
    return $form;
  }

}
