<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/video")
 */
class VideoController extends AbstractController
{
    /**
     * @Route("/list", name="app_video_list")
     */
    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new video list controller!',
            'path' => 'src/Controller/VideoController.php',
        ]);
    }
}
