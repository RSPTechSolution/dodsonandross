<?php

namespace Drupal\commerce_license;

use Drupal\commerce\CommerceEntityViewsData;

/**
 * Provides views data for the License entity type.
 */
class LicenseViewsData extends CommerceEntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $base_table = $this->entityType->getBaseTable() ?: $this->entityType->id();

    $data[$base_table]['label'] = [
      'title' => $this->t('Label'),
      'help' => $this->t('The label of the license.'),
      'real field' => 'license_id',
      'field' => [
        'id' => 'commerce_license__entity_label',
      ],
    ];

    // Workaround for core shortcoming.
    // @todo remove once https://www.drupal.org/node/2337515 is fixed.
    $data[$base_table]['state']['filter']['id'] = 'state_machine_state';

    return $data;
  }

}
