<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\JwtAuth;
use DateInterval;
use DateTime;
use Exception;
use PhpParser\Node\Stmt\If_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

//use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validation;


/**
 * @Route("/auth")
 */
class AuthController extends AbstractController
{

    /**
     * Serialize response
     *
     * @param $data
     * @return Response
     */
    private function resJson($data): Response
    {
        // Serializar datos con servicio serializer
        $json = $this->get('serializer')->serialize($data, 'json');

        // Response con http-foundation
        $response = new Response();

        // Asignar contenido a la respuesta
        $response->setContent($json);

        // Indicar formato de respuesta
        $response->headers->set('Content-Type', 'application/json');

        // Devolver la respuesta
        return $response;
    }

    /**
     * @Route("/register", methods={"POST"}, name="app_register")
     *
     * @param Request $request
     * @param MailerInterface $mailer
     * @param UserRepository $userRepository
     * @return Response
     * @throws TransportExceptionInterface
     */
    public function register(
        Request $request,
        MailerInterface $mailer,
        UserRepository $userRepository
    ): Response {

        // default data response.
        $data = [];

        //Check if arrive data from request
        if (empty($request->getContent())) {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "API cant received request parameters";

            return $this->resJson($data);
        }
        // Si recibimos desde un x-www-form-urlencoded
        /*
        $name = $request->get('name', null);
        $surname = $request->get('surname', null);
        $email = $request->get('email',null);
        $password = $request->get('password',null);
        */

        // Desde un raw json
        // $content = $request->getContent(); // return "name=pepe&surname=lotas&email=pepe%40lotas.es&password=123456"
        // If the request body is a JSON string, it can be accessed using toArray()
        //$params = $request->toArray();


        // form data key->obj
        $json = $request->get('json', null);

        // default data response.
        $data = [
            'status'  => 'error',
            'message' => 'Error something wrong in user registration. Try again.',
        ];

        if ($json === null) {
            $data['code']  = 400;
            $data['error'] = 'Api cannot receive request data.';
            return $this->resJson($data);
        }

        // Decode json
        try {
            $params = json_decode($json);
        } catch (Exception $e) {
            $data['code']  = 400;
            $data['error'] = 'Api cannot decode json data';
            return $this->resJson($data);
        }

        // Check empty fields
        $name     = (!empty($params->name)) ? trim($params->name) : null;
        $surname  = (!empty($params->surname)) ? trim($params->surname) : null;
        $email    = (!empty($params->email)) ? trim($params->email) : null;
        $password = (!empty($params->password)) ? trim($params->password) : null;

        if ($name === null || $surname === null || $email === null || $password === null) {
            $data['code']  = 400;
            $data['error'] = 'Any field from registration form is empty';
            return $this->resJson($data);
        }

        //Validate form fields
        $validator = Validation::createValidator();

        // name
        $validateName = $validator->validate($name, [
            new NotBlank(),
            new Length([
                'min' => 2,
                'max' => 100,
            ]),
        ]);
        if (count($validateName) == !0) {
            $data['code']  = 400;
            $data['error'] = 'name field is not valid';
            return $this->resJson($data);
        }

        $validateSurName = $validator->validate($surname, [
            new NotBlank(),
            new Length([
                'min' => 2,
                'max' => 100,
            ]),
        ]);
        if (count($validateSurName) == !0) {
            $data['code']  = 400;
            $data['error'] = 'surname field is not valid';
            return $this->resJson($data);
        }

        $validateEmail = $validator->validate($email, [
            new Regex('/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix')
            //new Email(),
        ]);
        if (count($validateEmail) == !0) {
            $data['code']  = 400;
            $data['error'] = 'email field is not valid';
            return $this->resJson($data);
        }

        /*
            password requirements:
            Must be a minimum of 6 characters
            Must contain at least 1 number
            Must contain at least one uppercase character
            Must contain at least one lowercase character
        */
        $validatePassword = $validator->validate($password, [
            new Regex('/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z*_\d]{6,}$/'),
        ]);
        if (count($validatePassword) == !0) {
            $data['code']  = 400;
            $data['error'] = 'password field is not valid. Must be a minimum of 6 characters, 
                              contain at least 1 number, contain at least one uppercase character,
                              contain at least one lowercase character';

            return $this->resJson($data);
        }

        // Check if email exists in DB
        $issetUser = $userRepository->findBy([
            'email' => $email,
        ]);

        if (count($issetUser) >= 1) {
            $data['code']  = 400;
            $data['error'] = 'You cannot use this email address. This email already in use';
            return $this->json($data);
        }

        // Prepare obj user to save in db
        // Hash password
        $options = [
            'cost' => 12,
        ];
        // TODO USAR HASHPASSWORDINTERFACE
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, $options);

        // create obj user
        $user = new User($name, $surname, $email, $passwordHash);

        // Create  email validation token
        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $emailToken      = substr(str_shuffle($permitted_chars), 0, 10);
        $user->setEmailToken($emailToken);

        // EmailTokenExpires 1h
        $dt                = new DateTime('now');
        $emailTokenExpires = $dt->modify('+ 1 hour');
        $user->setEmailTokenExpires($emailTokenExpires);


        //save in db
        $doctrine = $this->getDoctrine();
        $em       = $doctrine->getManager();
        $em->persist($user);
        $em->flush();


        // email body
        $body = "<!DOCTYPE> 
            <html lang='es'>
              <body>
                <h2>Hello from Symfony.</h2> 
                <h3>Nice to meet you {$user->getName()}! ❤</h3>
                <p>Your authentication code is : <strong>{$user->getEmailToken()}</strong></p> 
                <p>Thank you for subscribing. Please confirm your email by clicking on the following link and insert your authentication code</p>
                <a href=https://localhost:8000/auth/activate> Click here</a>
                <p>If link doesnt work use this link href=https://localhost:8000/auth/activate</p>  
                <p>If you have not requested this code please do not reply to this email</p>
              </body>
            </html>";

        // TODO: enviar email
        $email = (new Email())
            ->from('backend-symfony@example.com')
            ->to($user->getEmail())
            ->subject('Welcome to the Space Bar!')
            //->text('Sending emails is fun again!')
            ->html($body);


        $mailer->send($email);

        // Return response
        $data = [
            'status'  => 'success',
            'code'    => 201,
            'message' => 'User registration successfully. Check your email address for activate your account.',
            'user'    => $user,
        ];

        return $this->json($data);
        //return $this->resJson($data);
    }

    /**
     *  TODO: Register using FormType
     *
     * @param Request $request
     * @param UserRepository $userRepository
     */
    public function registerForm(Request $request, UserRepository $userRepository)
    {

    }

    /**
     * TODO: ACABAR activateAccount
     *
     * @Route("/activate", methods={"POST","PATCH","PUT"} ,name="app-activate-account")
     * @param Request $request
     * @param UserRepository $userRepository
     * @return Response
     */
    public function activateAccount(
        Request $request,
        UserRepository $userRepository
    ): response {

        // default data response.
        $data = [];

        //Check if arrive data from request
        if (empty($request->getContent())) {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "API cant recieved request parameters";

            return $this->resJson($data);
        }

        //check request
        //dump($request->headers->get('content-type'));
        //dump($content = $request->getContent());

        // Raw json
        $params = $request->toArray();
        $data   = json_decode($request->getContent(), true);
        $data2  = json_decode($request->getContent());
        /*dump($params);
        dump($data);
        dump(($data2));
        dump(($data2->code));
        die();*/

        // Get code from request
        // x-www-url-encoded (poco probable)
        /*$data2 = json_decode($request->getContent()); // null
        $activationCode = $request->get('code', null); // string||json
        // si llega un json
        $dataDecoded = json_decode($activationCode);
        dump($data2);
        dump($activationCode);
        dump($dataDecoded);
        dump($dataDecoded->code);
        die();*/

        // form data key={"attr":"value"}
        //dump($request);
        $data = $request->get('code', null);
        dump($data);
        die();
        $params = json_decode($data);
        dump($params);
        $code  = $params->code;
        $email = $params->email;

        // raw json
        //$params1 = $request->toArray();
        //dump($params1);


        // check if token is valid
        $user = $userRepository->findOneBy([
            'email'      => $email,
            'emailToken' => $code,
        ]);

        // check if token has expired.
        $actualTime  = new DateTime ('now');
        $expiredTime = $user->getEmailTokenExpires();
        //if ()

        // Set active = true emailToken = null
        $user->setActive(true);
        $user->setEmailToken(null);

        // return response
        die();
        return $this->json([
            'message' => 'Welcome to your activateAccount controller!',
            'path'    => 'src/Controller/AuthController.php',
        ]);
    }

    /**
     * @Route("/login", methods={"POST"}, name="app_login")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param JwtAuth $jwtAuth
     * @return Response
     */
    public function login(Request $request, UserRepository $userRepository, JwtAuth $jwtAuth): Response
    {
        // default data response.
        $data = [
            'message' => 'Error. Something wrong in user login. Try again.',
        ];

        //Check if arrive data from request
        if (empty($request->getContent())) {
            $data['status'] = "error";
            $data['code']   = 400;
            $data['error']  = "API cant received request parameters";

            return $this->resJson($data);
        }

        try {
            // Get email, password from request
            $params = $request->getContent();

            // decode to json string && trim
            $paramsDecoded = json_decode($params);

            // Check if email is null
            if (!isset($paramsDecoded->email) || $paramsDecoded->email === null) {
                $data['status'] = "error";
                $data['code']   = 400;
                $data['error']  = "Email field from login form not received";

                return $this->resJson($data);
            }

            // check if email is empty
            if (empty($paramsDecoded->email)) {
                $data['status'] = "error";
                $data['code']   = 400;
                $data['error']  = "Email field from login is empty";

                return $this->resJson($data);
            }

            $email = trim($paramsDecoded->email);

            if (!isset($paramsDecoded->password) || $paramsDecoded->password === null) {
                $data['status'] = "error";
                $data['code']   = 400;
                $data['error']  = "Password field from login form not received";

                return $this->resJson($data);
            }
            // check if password is empty
            if (empty($paramsDecoded->password)) {
                $data['status'] = "error";
                $data['code']   = 400;
                $data['error']  = "Password field from login is empty";

                return $this->resJson($data);
            }

            $plainPassword = trim($paramsDecoded->password);

            // if arrives $getToken
            $getToken = (!empty($paramsDecoded->getToken)) ? $paramsDecoded->getToken : null;

        } catch (Exception $error) {
            $data['status'] = "error";
            $data['code']   = 400;
            $data['error']  = $error->getMessage();

            return $this->resJson($data);
        }

        //Validate fields
        $validator = Validation::createValidator();

        // email
        $validateEmail = $validator->validate($email, [
            new Regex('/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix')
            //new Email(),
        ]);

        if (count($validateEmail) !== 0) {
            $data['status'] = 'error';
            $data['code']   = 400;
            $data['error']  = 'Email field is not valid';

            return $this->resJson($data);
        }

        // TODO: ¿Validate password?

        // Get user from DB
        $user = $userRepository->findOneBy(['email' => $email,]);

        // Meter todos services(createToken, checkCredential en jwtSignIn)
        $checkCredentials = $jwtAuth->checkCredentials($email, $plainPassword);

        if ($checkCredentials['status'] === 'error' && $checkCredentials['error']) {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "Error. Something wrong in user login. Try again.";
            $data['error']   = $checkCredentials['message'];

            return $this->resJson($data);
        }

        // jwtSignIn() = checkCredentials + createToken
        $jwtSignIn = $jwtAuth->jwtSignIn($email, $plainPassword); // return ['status','error', 'message', 'token']
        if (isset($jwtSignIn['status']) && isset($jwtSignIn['error']) && $jwtSignIn['status'] = 'error') {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "Error. Something wrong in user login. Try again.";
            $data['error']   = $jwtSignIn['message'];

            return $this->resJson($data);
        }

        if ($jwtSignIn['status'] === 'success') {
            // get token from jwtSignIn service
            $authToken =$jwtSignIn['authToken'];
            $tokenSaved = $user->getAccessToken();

            // set null accessToken


            // TODO: implementar servicio save in db
            // Save authToken in DB
            $user->setAccessToken($jwtSignIn['authToken']);

            $doctrine = $this->getDoctrine();
            $em       = $doctrine->getManager();
            $em->persist($user);
            $em->flush();

            $userSaved = $userRepository->findOneBy(['id' => $user->getId()]);
            $updatedToken = $userSaved->getAccessToken();


            // if arrives flag getToken = true -> return token else only identity
            if ($getToken) {
                // Return response
                $data = [
                    'status'    => 'success',
                    'code'      => 200,
                    'message'   => 'User login successfully.',
                    'AuthToken' => $authToken,
                    //'userSaved' => $userSaved,//
                ];
            }
            else {
                // TODO: change return getCredential -> obj
                $identity = $jwtAuth->getCredentials($authToken);
                if (!is_object($identity)) {
                    $data['status']  = "error";
                    $data['code']    = 400;
                    $data['message'] = "Error. Something wrong in user login. Try again.";
                    $data['error']   = $identity['message'];

                    return $this->resJson($data);
                }

                /*if (isset($identity['status']) && isset($identity['error']) && $identity['status'] === 'error') {
                    $data['status']  = "error";
                    $data['code']    = 400;
                    $data['message'] = "Error. Something wrong in user login. Try again.";
                    $data['error']   = $identity['message'];

                    return $this->resJson($data);
                }*/

                // Return response
                $data = [
                    'status'   => 'success',
                    'code'     => 200,
                    'message'  => 'User login successfully.',
                    'identity' => $identity,
                    //'AuthToken' => $authToken,//
                ];
            }

        }

        return $this->resJson($data);

    }
}
