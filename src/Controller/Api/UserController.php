<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{

    /**
     * Serialize response
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
    public function testOrm (UserRepository $userRepository, VideoRepository  $videoRepository): response
    {
        $users = $userRepository->findAll();
        $videos = $videoRepository->findAll();

        /*foreach($users as $user){
            echo "<h1>{$user->getName()} {$user->getSurname()}</h1>";

            foreach($user->getVideos() as $video){
                echo "<p>{$video->getTitle()} - {$video->getUser()->getEmail()}</p>";
            }
        }
        die();*/

        return $this->json([
            'users' => $users,
            'videos' => $videos,
        ]);
    }


    /**
     * @Route("/list", name="app_user_list")
     */
    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new user list controller!',
            'path' => 'src/Controller/UserController.php',
        ]);
    }
}
