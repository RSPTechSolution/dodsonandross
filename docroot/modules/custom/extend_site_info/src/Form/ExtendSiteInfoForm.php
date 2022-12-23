<?php

namespace Drupal\extend_site_info\Form;

// Classes referenced in this class:
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

// This is the form we are extending
use Drupal\system\Form\SiteInformationForm;

/**
 * Configure site information settings for this site.
 */
class ExtendSiteInfoForm extends SiteInformationForm
{
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state)
	{
		// Retrieve the system.site configuration
		$site_config = $this->config('system.site');

		// Get the original form from the class we are extending
		$form = parent::buildForm($form, $form_state);

    // Add form fields
		$form['site_information']['site_title'] = [
		  '#type' => 'textfield',
		  '#title' => t('Title'),
		  '#default_value' => $site_config->get('title'),
		  '#title' => $this->t('Title'),
    ];


		$form['site_information']['site_description'] = [
		  '#type' => 'textarea',
		  '#title' => t('Description'),
		  '#default_value' => $site_config->get('description'),
		  '#description' => $this->t('Description'),
    ];

		$form['site_information']['site_google_id'] = [
		  '#type' => 'textfield',
		  '#title' => t('Google ID'),
		  '#default_value' => $site_config->get('google_id'),
		  '#google_id' => $this->t('Google ID'),
    ];

    $form['site_information']['site_youtube_id'] = [
		  '#type' => 'textfield',
		  '#title' => t('Youtube ID'),
		  '#default_value' => $site_config->get('youtube_id'),
		  '#youtube_id' => $this->t('Youtube ID'),
    ];

		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state)
	{
		// Now we need to save the new description to the
		// system.site.description configuration.
		$this->config('system.site')
			// The site_description is retrieved from the submitted form values
			// and saved to the 'description' element of the system.site configuration
      ->set('description', $form_state->getValue('site_description'))
      ->set('title', $form_state->getValue('site_title'))
			// Make sure to save the configuration
			->save();

		// Pass the remaining values off to the original form that we have extended,
		// so that they are also saved
		parent::submitForm($form, $form_state);
	}
}
