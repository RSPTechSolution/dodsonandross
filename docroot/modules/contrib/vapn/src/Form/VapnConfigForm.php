<?php

namespace Drupal\vapn\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Class VapnConfigForm.
 */
class VapnConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeBundleInfoInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;
  /**
   * Constructs a new VapnConfigForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
      EntityTypeBundleInfoInterface $entity_type_bundle_info
    ) {
    parent::__construct($config_factory);
        $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
            $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vapn.vapnconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vapn_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vapn.vapnconfig');


    //grabbing list of node's bundles

    $bundle_list= $this->entityTypeBundleInfo->getBundleInfo('node');

    $options= [];
    foreach ($bundle_list as $name=>$bundle ){
      $options[$name]= $bundle['label'];
    }

    $saved_values=$config->get('vapn_node_list');
    $default_value= [];
    if(is_null($saved_values)){
      $saved_values = [];
    }
    foreach (array_keys($bundle_list) as $bundle_name){
      $default_value[$bundle_name]= in_array($bundle_name, $saved_values) ? $bundle_name: 0;
    }


    $form['vapn_node_list'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types'),
      '#description' => $this->t('Select content types that should have the view permission on each node.'),
      '#default_value' => $default_value,
      '#options'=> $options
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $saved= [];
    foreach ($form_state->getValue('vapn_node_list') as $bundle_name=> $value ){
      if($value) $saved[]= $value;
    }




    $this->config('vapn.vapnconfig')
      ->set('vapn_node_list', $saved)
      ->save();
  }

}
