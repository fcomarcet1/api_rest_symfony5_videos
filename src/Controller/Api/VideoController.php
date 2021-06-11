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
