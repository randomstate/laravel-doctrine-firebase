<?php


namespace RandomState\LaravelDoctrineFirebase;


use Carbon\Carbon;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Validation\ValidationData;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use RandomState\LaravelDoctrineFirebase\Http\CacheControlHeader;
use ReflectionClass;
use Symfony\Component\HttpKernel\Client;

class FirebaseUserProvider implements UserProvider
{

    const PUBLIC_KEY_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var ObjectRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $modelType;

    /**
     * @var Parser
     */
    protected $jwtParser;

    /**
     * @var string
     */
    protected $firebaseProjectId;

    /**
     * @var Carbon
     */
    protected $date;

    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @var Client
     */
    protected $client;

    public function __construct(
        ObjectManager $manager,
        Parser $jwtParser,
        $modelType,
        Client $client,
        $firebaseProjectId,
        Carbon $date,
        Repository $cache = null
    ) {
        $this->repository        = $manager->getRepository($modelType);
        $this->modelType         = $modelType;
        $this->jwtParser         = $jwtParser;
        $this->firebaseProjectId = $firebaseProjectId;
        $this->date              = $date;
        $this->cache             = $cache;
        $this->client            = $client;
        $this->objectManager     = $manager;
    }

    public function retrieveById($identifier)
    {
        return $this->repository->find($identifier);
    }

    public function retrieveByToken($identifier, $token)
    {
        return $this->repository->findOneBy([
            $this->getAuthenticatable()->getAuthIdentifierName() => $identifier,
            $this->getAuthenticatable()->getRememberTokenName()  => $token,
        ]);
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $this->objectManager->persist($user);
        $this->objectManager->flush($user);
    }

    /**
     * The retrieveByCredentials method receives the array of credentials passed to the Auth::attempt method when
     * attempting to sign into an application. The method should then "query" the underlying persistent storage for the
     * user matching those credentials. Typically, this method will run a query with a "where" condition on
     * $credentials['username']. The method should then return an implementation of Authenticatable. This method should
     * not attempt to do any password validation or authentication.
     *
     * @param array $credentials
     *
     * @return Authenticatable|null|object
     */
    public function retrieveByCredentials(array $credentials)
    {
        // decode
        // check which uid it is associated with but don't bother about validation
        // find and return the user
        // do not create the user at this point, but do return a non-persisted version (this allows signup on first login)
        $token   = collect($credentials)->first();
        $subject = $this->jwtParser->parse($token)->getClaim('sub');

        $nullUser               = $this->getAuthenticatable();
        $authIdentifierProperty = $nullUser->getAuthIdentifierName();

        $authIdentifier = $this->getAuthenticatableReflection()->getProperty($authIdentifierProperty);
        $authIdentifier->setAccessible(true);
        $authIdentifier->setValue($nullUser, $subject);

        return $this->repository->findOneBy(
                [
                    $this->getAuthenticatable()->getAuthIdentifierName() => $subject,
                ]
            ) ?? $nullUser;
    }

    /**
     * The validateCredentials method should compare the given $user with the $credentials to authenticate the user.
     * For example, this method should probably use Hash::check to compare the value of $user->getAuthPassword() to the
     * value of $credentials['password']. This method should return true or false indicating on whether the password is
     * valid.
     *
     * @param Authenticatable $user
     * @param array $credentials
     *
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $token = collect($credentials)->first();
        $token = $this->jwtParser->parse($token);

        $validator = new ValidationData();
        $validator->setIssuer(sprintf("https://securetoken.google.com/%s", $this->firebaseProjectId));
        $validator->setAudience($this->firebaseProjectId);
        $validator->setCurrentTime($this->date->timestamp);
        $validator->setSubject($user->getAuthIdentifier());

        $signer = new Sha256();

        $isValid    = $token->validate($validator);
        $isVerified = $token->verify($signer, $this->getPublicKey());

        // Save the user to the database on first time
        if ($isValid && $isVerified) {
            $this->objectManager->persist($user);
            $this->objectManager->flush();

            return true;
        }

        return false;
    }

    /**
     * Returns instantiated entity.
     * @return Authenticatable | object
     */
    protected function getAuthenticatable()
    {
        return $this->getAuthenticatableReflection()
                    ->newInstanceWithoutConstructor();
    }

    /**
     * @return ReflectionClass
     */
    protected function getAuthenticatableReflection()
    {
        return new ReflectionClass($this->modelType);
    }

    /**
     * Fetches the public key for Google Firebase from their site.
     * Utilises caching to avoid making repeated calls for high-traffic sites.
     *
     * @return string
     */
    protected function getPublicKey()
    {
        $cacheKey = 'firebase_public_key';

        $key = $this->cache->get($cacheKey) ?? null;

        if ( ! $key) {
            $response = $this->client->get(self::PUBLIC_KEY_URL);
            $key      = collect(json_decode($response->getBody()->getContents(), true))->reduce(function (
                $carry,
                $item
            ) {
                return $carry . $item;
            }, "");

            $lifetime = (new CacheControlHeader($response->getHeaderLine('Cache-Control')))->getMaxAge();

            $this->setKeyInCache($cacheKey, $key, $lifetime);
        }

        return $key;
    }

    protected function getKeyFromCacheIfSet($key)
    {
        if ( ! $this->cache) {
            return null;
        }

        return $this->cache->get($key);
    }

    protected function setKeyInCache($key, $value, $lifetime)
    {
        if ( ! $this->cache) {
            return null;
        }

        $this->cache->put($key, $value, $lifetime);
    }
}