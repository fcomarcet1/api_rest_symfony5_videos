<?php

namespace App\Services;

use App\Entity\User;
use App\Repository\UserRepository;
use DomainException;
use Firebase\JWT\JWT;
use Exception;
use http\Client\Request;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Class JwtAuth
 *
 * @package App\Services
 */
class JwtAuth
{

    public array $data;
    public $manager;
    public UserRepository $userRepository;
    private string $jwtSecret;

    /**
     * JwtAuth constructor.
     *
     * @param $manager . Injected manager service  for access ORM in service
     */
    public function __construct($manager, UserRepository $userRepository)
    {
        // TODO: meter clave jwt secret en var .env
        $this->jwtSecret      = "hola_que_tal_esto_es_una_clave_secretagtfino8h2035267600xd";
        $this->manager        = $manager;
        $this->userRepository = $userRepository;

    }

    public function testJwtAuthService(): string
    {
        return "Lerele from JwtService -> testService()";
    }

    /**
     * Create Auth Token, return token or array with error
     *
     * @param $user
     * @return string|array
     */
    public function createToken($user)
    {
        try {
            $payload = [
                'tokenId' => Uuid::v4(),
                'sub'     => $user->getId(),
                'role'    => $user->getRoles(),
                'iss'     => "www.server-dns.com",
                'aud'     => "www.my-domain.com",
                'iat'     => time(),
                'exp'     => time() + (7 * 24 * 60 * 60),
            ];

            $token = JWT::encode($payload, $this->jwtSecret, 'HS512');

        } catch (Exception $error) {
            return [
                'status'  => 'error',
                'error'   => true,
                'message' => "Error. Cannot create Auth Token: " . $error,
            ];
        }

        return $token;
    }

    /**
     * Check that the credentials entered are valid.
     *
     * @param string $email
     * @param string $password
     * @return array
     */
    public function checkCredentials(string $email, string $password): array
    {
        $data           = [];
        $userRepository = $this->userRepository;
        $user           = $userRepository->findOneBy(['email' => $email]);

        /*
         * $user = $this->manager->getRepository(User::class)->findOneBy(['email' => $email,]);
        */

        // Check email
        if (!is_object($user) || empty($user)) {
            $data['status']  = 'error';
            $data['error']   = true;
            $data['message'] = 'Invalid credentials. The email does not match any registered user';

            return $data;
        }

        // Check password
        $checkPassword = $this->verifyPassword($password, $user->getPassword());

        if (!$checkPassword) {
            $data['status']  = 'error';
            $data['error']   = true;
            $data['message'] = 'Invalid credentials. Wrong password';

            return $data;
        }

        //Check if account is active (email notification)
        if ($user->getActive() === false || $user->getActive() === 0) {
            $data['status']  = 'error';
            $data['error']   = true;
            $data['message'] = "Error. This account is not validated. please check your email and activate your account";

            return $data;
        }

        // Return data
        $data['status']  = 'success';
        $data['message'] = 'Check credentials successfully';
        $data['user']    = $user;

        return $data;
    }

    /**
     * jwt Service for checkCredentials + create token +
     *
     * @param string $email
     * @param string $password
     * @param null $getToken
     * @return array
     */
    public function jwtSignIn(string $email, string $password, $getToken = null): array
    {
        $data           = [];
        $userRepository = $this->userRepository;
        $user           = $userRepository->findOneBy(['email' => $email]);
        //$user         = $this->manager->getRepository(User::class)->findOneBy(['email' => $email,]);

        // Check credentials
        $checkCredentials = $this->checkCredentials($email, $password);
        if ($checkCredentials['status'] === 'error' && $checkCredentials['error']) {
            $data['status']  = 'error';
            $data['error']   = true;
            $data['message'] = 'Invalid credentials:' . $checkCredentials['error'];

            return $data;
        }

        // create token
        $authToken = $this->createToken($user);
        //dump($authToken); die();
        if (isset($authToken['status']) && isset($authToken['error']) && $authToken['status'] == 'error') {
            $data['status']  = 'error';
            $data['error']   = true;
            $data['message'] = 'Error creating authentication token:' . $authToken['message'];

            return $data;
        }

        // return data
        $data['status']    = 'success';
        $data['authToken'] = $authToken;

        return $data;
    }

    /**
     * @param string $plainPassword
     * @param string $hashedPassword
     * @return bool
     */
    public function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        $passwordVerify = password_verify($plainPassword, $hashedPassword);

        if (!$passwordVerify) {
            return false;
        }
        return true;
    }

    /**
     * Get identity from user
     *
     * @param string $token
     * @return array|object
     */
    public function getCredentials(string $token)
    {
        $credentials = [];

        try {
            $jwtDecoded = JWT::decode($token, $this->jwtSecret, ['HS512']);
            $userId     = $jwtDecoded->sub;

            // Find user in DB
            $userRepository = $this->userRepository;
            $user           = $userRepository->findOneBy(['id' => $userId]);

            //$credentials['identity'] = $user;

        } catch (UnexpectedValueException | DomainException  $error) {
            return [
                'status'  => 'error',
                'error'   => true,
                'message' => "Error. Cannot decode AuthToken: " . $error,
            ];

        }

        return $user;
    }

    /**
     * Check if authToken is valid and return obj user when flag $identity = true
     *
     * @param string $token
     * @param null $identity
     * @return array|object
     */
    public function checkAuthToken(string $token, $identity = null)
    {
        try {

            // Remove spaces and ""
            $trimToken = trim($token);
            $authToken = str_replace('"', '', $token);

            $jwtDecoded = JWT::decode($authToken, $this->jwtSecret, ['HS512']);
            $userId     = $jwtDecoded->sub;

            // Access userRepository(injected in constructor).
            $userRepository = $this->userRepository;

            // Check if exist authToken in DB
            $user = $userRepository->findOneBy(['id' => $userId, 'accessToken' => $authToken]);

            if ($user === null || !is_object($user) || empty($user)) {
                return [
                    'status'  => 'error',
                    'error'   => true,
                    'message' => 'Forbidden access. Wrong authToken',
                ];
            }

            //if token has expired delete in db.
            $todayDate      = time();
            $expirationDate = $jwtDecoded->exp;

            /* if ($expirationDate > $todayDate) {//valid token} else {//token expired} */
            if ($expirationDate < $todayDate) {
                // token expired
                return [
                    'status'  => 'error',
                    'error'   => true,
                    'message' => 'Forbidden access. Wrong authToken(expired)',
                ];

            }
            $userRepository = $this->userRepository;
            $userId         = $jwtDecoded->sub;
            $user           = $userRepository->findOneBy(['id' => $userId]);

            // Return true or identity if exist flag getIdentity
            if (isset($identity) || $identity !== null) {
                return $user;
            }
            else {
                return ['status' => 'success',];
            }

            //return true;

        } catch (UnexpectedValueException | DomainException  $e) {
            return [
                'status'  => 'error',
                'error'   => true,
                'message' => "Error. Cannot checkAuthToken: " . $e,
            ];

        }

        //return true;
    }


}

