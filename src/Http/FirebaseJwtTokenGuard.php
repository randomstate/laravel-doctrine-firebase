<?php


namespace RandomState\LaravelDoctrineFirebase\Http;


use Illuminate\Auth\TokenGuard;

class FirebaseJwtTokenGuard extends TokenGuard
{

    public function user()
    {
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        if ( ! is_null($this->user)) {
            return $this->user;
        }

        $user = null;

        $token = $this->getTokenForRequest();

        if ( ! empty($token)) {
            $user = $this->provider->retrieveByCredentials(
                $credentials = [$this->storageKey => $token]
            );

            if ($this->provider->validateCredentials($user, $credentials)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
     *
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        $this->user = $user = $this->provider->retrieveByCredentials($credentials);

        return ! is_null($user) && $this->provider->validateCredentials($user, $credentials);
    }
}