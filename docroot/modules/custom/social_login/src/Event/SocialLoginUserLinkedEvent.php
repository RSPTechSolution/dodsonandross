<?php

namespace Drupal\social_login\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class SocialLoginUserLinkedEvent.
 */
class SocialLoginUserLinkedEvent extends Event
{
    // The name of this event.
    const EVENT_NAME = 'social_login_user_linked';

    // The Drupal user account that was logged in.
    public $account;

    // The retrieved social network profile data.
    public $social_network_profile_data;

    /**
     * Constructor.
     */
    public function __construct(\Drupal\user\Entity\User $account, $social_network_profile_data)
    {
        $this->set_account($account);
        $this->set_social_network_profile_data($social_network_profile_data);
    }

    /**
     * Sets the social network profile data.
     */
    public function set_social_network_profile_data($social_network_profile_data)
    {
        $this->social_network_profile_data = $social_network_profile_data;
    }

    /**
     * Returns the social network profile data.
     */
    public function get_social_network_profile_data()
    {
        return $this->social_network_profile_data;
    }

    /**
     * Sets the user account.
     */
    public function set_account(\Drupal\user\Entity\User $account)
    {
        $this->account = $account;
    }

    /**
     * Returns the user account.
     */
    public function get_account()
    {
        return $this->account;
    }

}