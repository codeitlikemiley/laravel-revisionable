<?php

namespace JohannesSchobel\Revisionable\Adapters;

use JohannesSchobel\Revisionable\Interfaces\UserProvider;
use Cartalyst\Sentry\Sentry as SentryProvider;

class Sentry implements UserProvider
{
    /**
     * Auth provider instance.
     *
     * @var \Cartalyst\Sentry\Sentry
     */
    protected $provider;

    /**
     * Field from the user to be saved as author of the action.
     *
     * @var string
     */
    protected $field;

    /**
     * Create adapter instance for Sentry.
     *
     * @param SentryProvider $provider
     * @param string         $field
     */
    public function __construct(SentryProvider $provider, $field = null)
    {
        $this->provider = $provider;
        $this->field = $field;
    }

    /**
     * Get identifier of the currently logged in user.
     *
     * @return string|null
     */
    public function getUser()
    {
        if ($user = $this->provider->getUser()) {
            return ($field = $this->field) ? (string) $user->{$field} : $user->getLogin();
        }
    }

    /**
     * Get id of the currently logged in user.
     *
     * @return integer|null
     */
    public function getUserId()
    {
        if ($user = $this->provider->getUser()) {
            return $user->getKey();
        }
    }
}
