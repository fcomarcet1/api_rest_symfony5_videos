<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use DateInterval;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
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
     * @param Request $request
     * @param MailerInterface $mailer
     * @param UserRepository $userRepository
     * @return Response
     * @throws TransportExceptionInterface
     */
    public function register(Request $request, MailerInterface $mailer, UserRepository $userRepository): Response
    {
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

        $data = [
            'status' => 'error',
            'message' => 'Error something wrong in user registration. Try again.'
        ];

        if ($json === null){
            $data['code'] = 400;
            $data['error'] = 'Api cannot receive request data.';
            return $this->resJson($data);
        }

        // Decode json
        try {
            $params = json_decode($json);
        }catch (Exception $e){
            $data['code'] = 400;
            $data['error'] = 'Api cannot decode json data';
            return $this->resJson($data);
        }

        // Check empty fields
        $name = (!empty($params->name)) ? trim($params->name) : null;
        $surname = (!empty($params->surname)) ? trim($params->surname) : null;
        $email = (!empty($params->email)) ? trim($params->email) : null;
        $password = (!empty($params->password)) ? trim($params->password) : null;

        if ($name === null || $surname === null || $email === null || $password === null){
            $data['code'] = 400;
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
                'max' => 100
            ]),
        ]);
        if (count($validateName) ==! 0 ){
            $data['code'] = 400;
            $data['error'] = 'name field is not valid';
            return $this->resJson($data);
        }

        $validateSurName = $validator->validate($surname, [
            new NotBlank(),
            new Length([
                'min' => 2,
                'max' => 100
            ]),
        ]);
        if (count($validateSurName) ==! 0 ){
            $data['code'] = 400;
            $data['error'] = 'surname field is not valid';
            return $this->resJson($data);
        }

        $validateEmail = $validator->validate($email, [
            new Regex('/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix')
            //new Email(),
        ]);
        if (count($validateEmail) ==! 0 ){
            $data['code'] = 400;
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
            new Regex('/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z*_\d]{6,}$/')
        ]);
        if (count($validatePassword) ==! 0 ){
            $data['code'] = 400;
            $data['error'] = 'password field is not valid. Must be a minimum of 6 characters, 
                              contain at least 1 number, contain at least one uppercase character,
                              contain at least one lowercase character';

            return $this->resJson($data);
        }
        // TODO: revisar no funciona
        // Check if email exists in DB
        $issetUser = $userRepository->findBy([
            'email' => $email,
        ]);

        if (count($issetUser) >= 1){
            $data['code'] = 400;
            $data['error'] = 'You cannot use this email address. This email already in use';
            return $this->json($data);
        }

        //die();
        // Prepare obj user to save in db
        // Hash password
        $options = [
            'cost' => 12,
        ];
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, $options);

        // create obj user
        $user = new User($name, $surname, $email, $passwordHash);

        // Create  email validation token
        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $emailToken = substr(str_shuffle($permitted_chars), 0, 10);
        $user->setEmailToken($emailToken);

        // EmailTokenExpires 1h
        $dt = new DateTime('now');
        $emailTokenExpires = $dt->modify('+ 1 hour');
        $user->setEmailTokenExpires($emailTokenExpires);


        //save in db
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();
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
            ->html($body );


        $mailer->send($email);

        // Return response
        $data = [
            'status' => 'success',
            'code' => 201,
            'message' => 'User registration successfully. Check your email address for activate your account.',
            'user' => $user,
        ];

        return $this->json($data);
        //return $this->resJson($data);
    }

    /**
     * Register using FormType
     * @param Request $request
     * @param UserRepository $userRepository
     */
    public function registerForm (Request $request, UserRepository $userRepository)
    {

    }

    /**
     * @Route("/activate", methods={"POST","PATCH","PUT"} ,name="app-activate-account")
     * @param Request $request
     * @param UserRepository $userRepository
     * @return Response
     */
    public function activateAccount (
        Request $request,
        UserRepository $userRepository
    ): response {

        return $this->json([
            'message' => 'Welcome to your activateAccount controller!',
            'path' => 'src/Controller/AuthController.php',
        ]);
    }

    /**
     * @Route("/login", methods={"POST"}, name="app_login")
     * @return Response
     */
    public function login(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new login controller!',
            'path' => 'src/Controller/AuthController.php',
        ]);
    }
}
