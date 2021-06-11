<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{

    /**
     * @Route("/test-orm", name="app_testOrm")
     *
     * @param UserRepository $userRepository
     * @param VideoRepository $videoRepository
     */
    public function testOrm (UserRepository $userRepository, VideoRepository  $videoRepository)
    {
        $users = $userRepository->findAll();
        $videos = $videoRepository->findAll();

        foreach($users as $user){
            echo "<h1>{$user->getName()} {$user->getSurname()}</h1>";

            foreach($user->getVideos() as $video){
                echo "<p>{$video->getTitle()} - {$video->getUser()->getEmail()}</p>";
            }
        }
        die();
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
