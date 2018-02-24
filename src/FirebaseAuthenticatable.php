<?php


namespace RandomState\LaravelDoctrineFirebase;


trait FirebaseAuthenticatable
{
    /**
     * @var string
     */
    protected $firebaseUid;

    /**
     * @var string
     */
    protected $rememberToken;

    /**
     * @return string
     */
    public function getFirebaseUid()
    {
        return $this->firebaseUid;
    }

    /**
     * @param string $firebaseUid
     *
     * @return $this
     */
    public function setFirebaseUid($firebaseUid)
    {
        $this->firebaseUid = $firebaseUid;

        return $this;
    }

    public function getAuthIdentifierName()
    {
        return 'firebaseUid';
    }

    public function getAuthIdentifier()
    {
        return $this->firebaseUid;
    }

    public function getAuthPassword()
    {
        return null;
    }

    public function getRememberToken()
    {
        return $this->rememberToken;
    }

    public function setRememberToken($value)
    {
        $this->rememberToken = $value;

        return $this;
    }

    public function getRememberTokenName()
    {
        return 'rememberToken';
    }
}