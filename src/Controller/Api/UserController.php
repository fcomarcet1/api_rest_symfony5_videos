<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\Services\JwtAuth;
use App\Services\Utils\CheckRequest;
use DateTime;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validation;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{

    /**
     * Serialize response
     *
     * @param $data
     * @return Response
     */
    private function resJson($data): Response
    {
        //$serializer = $this->get('serializer');

        // Serializar datos con servicio serializer
        $json = $this->get('serializer')->serialize($data, 'json', [
            ObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);

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
     * @Route("/test-orm", name="app_testOrm")
     *
     * @param UserRepository $userRepository
     * @param VideoRepository $videoRepository
     * @return Response
     */
    public function testOrm(UserRepository $userRepository, VideoRepository $videoRepository): response
    {
        $users  = $userRepository->findAll();
        $videos = $videoRepository->findAll();

        /*foreach($users as $user){
            echo "<h1>{$user->getName()} {$user->getSurname()}</h1>";

            foreach($user->getVideos() as $video){
                echo "<p>{$video->getTitle()} - {$video->getUser()->getEmail()}</p>";
            }
        }
        die();*/

        return $this->json([
            'users'  => $users,
            'videos' => $videos,
        ]);
    }

    /**
     * @Route("/list", methods={"GET"}, name="app_user_list")
     */
    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new user list controller!',
            'path'    => 'src/Controller/UserController.php',
        ]);
    }

    /**
     * @Route("/update", methods={"POST","PUT","PATCH"}, name="app_user_update")
     * @param Request $request
     * @param JwtAuth $jwtAuth
     * @param UserRepository $userRepository
     * @param CheckRequest $checkRequest . Service for check request.
     * @return JsonResponse|Response
     * @throws \Exception
     */
    public function update(
        Request $request,
        JwtAuth $jwtAuth,
        UserRepository $userRepository,
        CheckRequest $checkRequest
    ) {
        // check request with checkRequest service
        $validateRequest = $checkRequest->validateRequest($request);
        if (!$validateRequest) {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "API cant received request parameters";

            return $this->resJson($data);
        }

        // Default response
        $data = [];

        // Check data from request
        // Get auth headers(token)
        $authToken = $request->headers->get('Authorization');
        if (!isset($authToken) || empty($authToken)) {
            $data['status']  = 'error';
            $data['code']    = 400;
            $data['message'] = "Forbidden access. API cannot received authorization token";

            return $this->resJson($data);
        }

        // Make service checkAuthToken
        $checkAuthToken = $jwtAuth->checkAuthToken($authToken, $identity = true);
        // return obj($identity = true) | array,

        if (!is_object($checkAuthToken) && $checkAuthToken['status'] === 'error') {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "Error. Something wrong in user update. Try again.";
            $data['error']   = $checkAuthToken['message'];

            return $this->resJson($data);
        }

        // Get data from request (Raw-json)
        // $params = json_decode($request->getContent(), true);

        try {
            $params  = json_decode($request->getContent());
            $name    = (!empty($params->name)) ? trim($params->name) : null;
            $surname = (!empty($params->surname)) ? trim($params->surname) : null;
            $email   = (!empty($params->email)) ? trim($params->email) : null;

        } catch (Exception $error) {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "Error. Something wrong in user update. Try again.";
            $data['error']   = $error;

            return $this->resJson($data);
        }

        if ($name === null || $surname === null || $email === null) {
            $data['status'] = "error";
            $data['code']   = 400;
            $data['error']  = 'Any field from registration form is empty';

            return $this->resJson($data);
        }

        // Validate data
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

        // Get data from logged user
        $identity = $checkAuthToken;

        // Get user from DB for update
        $user = $userRepository->findOneBy([
            'id' => $identity->getId(),
        ]);

        // Check unique email
        $email = strtolower($email);
        $issetEmail = $userRepository->findOneBy([
            'email' => $email,
        ]);

        if (is_object($issetEmail) && ($identity->getId() != $issetEmail->getId())) {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "Error. This email is already taken, please choose another email address.";

            return $this->resJson($data);

        }

        // set data in user obj
        $user->setName($name);
        $user->setSurname($surname);
        $user->setEmail($email);
        $user->setUpdatedAt(new DateTime('now'));

        // update data
        $doctrine = $this->getDoctrine();
        $em       = $doctrine->getManager();

        $em->persist($user);
        $em->flush();


        // return response
        $data = [
            'status'  => "success",
            'code'    => 201,
            'message' => "User updated successfully.",
            'user'    => $user,
        ];

        return $this->resJson($data);

    }

    /**
     * Delete user account
     *
     * @Route("/delete/{id}", methods={"DELETE"} ,name="app_user_delete")
     *
     * @param Request $request
     * @param JwtAuth $jwtAuth JwtAuth service.
     * @param CheckRequest $checkRequest
     * @param UserRepository $userRepository
     * @param null $id
     * @return JsonResponse|Response
     */
    public function delete(
        Request $request,
        JwtAuth $jwtAuth,
        CheckRequest $checkRequest,
        UserRepository $userRepository,
        $id = null
    ) {
        // check request with checkRequest service
        $validateRequest = $checkRequest->validateRequest($request);
        if (!$validateRequest) {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "API cant received request parameters";

            return $this->resJson($data);
        }

        $data = [];
        // Check data from request
        // Get auth headers(token)
        $authToken = $request->headers->get('Authorization');
        if (!isset($authToken) || empty($authToken)) {
            $data['status']  = 'error';
            $data['code']    = 400;
            $data['message'] = "Forbidden access. API cannot received authorization token";

            return $this->resJson($data);
        }

        // Make service checkAuthToken
        $checkAuthToken = $jwtAuth->checkAuthToken($authToken, $identity = true);
        // return obj($identity = true) | array,

        if (!is_object($checkAuthToken) && $checkAuthToken['status'] === 'error') {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "Error. Something wrong in user update. Try again.";
            $data['error']   = $checkAuthToken['message'];

            return $this->resJson($data);
        }
        // en el front meter password para eliminar cuenta usuario
        $userId = $checkAuthToken->sub;

        // $doctrine = $this->getDoctrine();
        // $em = $doctrine->getManager();
        // $video = $doctrine->getRepository(Video::class)->findOneBy(['id'=>$id]);
        $user = $userRepository->findOneBy(['id' => $userId]);
        // $em->remove($video);
        // $em->flush();

        return $this->json([
            'message' => 'Welcome to your new user delete controller!',
            'path'    => 'src/Controller/UserController.php',
        ]);
    }
}
