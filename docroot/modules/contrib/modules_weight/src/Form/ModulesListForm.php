<?php

namespace Drupal\modules_weight\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\modules_weight\Utility\FormElement;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\modules_weight\ModulesWeightInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;

/**
 * Builds the form to configure the Modules weight.
 */
class ModulesListForm extends FormBase {

  /**
   * The module weight service.
   *
   * @var \Drupal\modules_weight\ModulesWeightInterface
   */
  protected $modulesWeight;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs a new ModulesWeight object.
   *
   * @param \Drupal\modules_weight\ModulesWeightInterface $modules_weight
   *   The modules weight.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   */
  public function __construct(ModulesWeightInterface $modules_weight, ConfigFactoryInterface $config_factory, ModuleExtensionList $module_extension_list) {
    $this->modulesWeight = $modules_weight;
    $this->configFactory = $config_factory;
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('modules_weight'),
      $container->get('config.factory'),
      $container->get('extension.list.module')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'modules_weight_modules_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // The table header.
    $header = [
      $this->t('Name'),
      [
        'data' => $this->t('Description'),
        // Hidding the description on narrow width devices.
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      $this->t('Weight'),
      [
        'data' => $this->t('Package'),
        // Hidding the description on narrow width devices.
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
    ];

    // The table.
    $form['modules'] = [
      '#type' => 'table',
      '#header' => $header,
      '#sticky' => TRUE,
    ];

    // Getting the config to know if we should show or not the core modules.
    $show_core_modules = $this->configFactory->get('modules_weight.settings')->get('show_system_modules');
    // Getting the module list.
    $modules = $this->modulesWeight->getModulesList($show_core_modules);
    // Iterate over each module.
    foreach ($modules as $filename => $module) {
      // The rows info.
      // Module name.
      $form['modules'][$filename]['name'] = [
        '#markup' => $module['name'],
      ];
      // Module description.
      $form['modules'][$filename]['description'] = [
        '#markup' => $module['description'],
      ];
      // Module weight.
      $form['modules'][$filename]['weight'] = [
        '#type' => 'weight',
        '#default_value' => $module['weight'],
        '#delta' => FormElement::getMaxDelta($module['weight']),
      ];
      // Module package.
      $form['modules'][$filename]['package'] = [
        '#markup' => $module['package'],
      ];
    }
    // The form button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Changes'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The modules information.
    $modules_info = $form_state->getValue('modules');
    // Doing the array unidimensional.
    $new_weight_value = array_combine(array_keys($modules_info), array_column($modules_info, 'weight'));

    // Getting the config to know if we should show or not the core modules.
    $show_core_modules = $this->configFactory->get('modules_weight.settings')->get('show_system_modules');
    // The old values information.
    $old_modules_info = $this->modulesWeight->getModulesList($show_core_modules);
    // Doing the array unidimensional.
    $old_weight_value = array_combine(array_keys($old_modules_info), array_column($old_modules_info, 'weight'));

    if ($new_weight_value == $old_weight_value) {
      // Printing message if there are not module that has changed.
      $this->messenger()->addWarning($this->t("You don't have changed the weight for any module."));
    }
    else {
      // Getting the changed modules.
      $changed = array_diff_assoc($new_weight_value, $old_weight_value);

      // Printing message because we have changes in the weights.
      $this->messenger()->addMessage($this->t('The modules weight was updated.'));
      // Updating weights.
      foreach ($changed as $module_name => $weight) {
        // Setting the new weight.
        module_set_weight($module_name, $weight);

        $variables = [
          '@module_name' => $this->moduleExtensionList->getExtensionInfo($module_name)['name'],
          '@weight' => $weight,
        ];
        // Printing information about the modules weight.
        $this->messenger()->addMessage($this->t('@module_name have now as weight: @weight', $variables));
      }
    }
  }

}
