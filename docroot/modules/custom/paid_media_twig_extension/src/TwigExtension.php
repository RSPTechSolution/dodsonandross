<?php

namespace Drupal\paid_media_twig_extension;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
class TwigExtension extends \Twig_Extension {


  public function getName() {
    return 'paid_media_twig_extension';
  }

  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('get_paid_media', [$this, 'getProductVariation']),
    ];
  }

  public function getProductVariation($id,$style, $parameters = [], $options = [], $langcode = '') {

    $style = ImageStyle::load($style);
    if (!empty($id)) {
      $photo_file = File::load($id);
      $image_url = $style->buildUrl($photo_file->uri->value);
    }
    return $image_url; 
  }  
}