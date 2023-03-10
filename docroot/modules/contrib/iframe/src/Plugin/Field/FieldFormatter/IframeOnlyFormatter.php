<?php

namespace Drupal\iframe\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Markup;

/**
 * Class IframeOnlyFormatter.
 *
 * @FieldFormatter(
 *  id = "iframe_only",
 *  label = @Translation("Iframe without title"),
 *  field_types = {"iframe"}
 * )
 */
class IframeOnlyFormatter extends IframeDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    // settings from type
    $settings = $this->getSettings();
    // field_settings on concrete field
    $field_settings = $this->getFieldSettings();

    $allow_attributes = [ 'url', 'width', 'height', 'title' ];
    foreach ($items as $delta => $item) {
      if (empty($item->url)) {
        continue;
      }
      if (!isset($item->title)) {
        $item->title = '';
      }
      foreach($field_settings as $field_key => $field_val) {
        if (in_array($field_key, $allow_attributes)) {
          continue;
        }
        $item->{$field_key} = $field_val;
      }
      // KEEP title-attribute in item->title for Accessibility title-attribute in iframe tag //$item->title = '';
      // no visible header, but title-attr in item as options
      $elements[$delta] = self::iframeIframe('', $item->url, $item);
      // Tokens can be dynamic, so its not cacheable.
      if (isset($settings['tokensupport']) && $settings['tokensupport']) {
        $elements[$delta]['cache'] = ['max-age' => 0];
      }
    }
    return $elements;
  }

}
