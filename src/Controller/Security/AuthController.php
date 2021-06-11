<?php

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/register", name="app_register")
     * @return Response
     */
    public function register(): Response
    {
        $data = [
            'message' => 'Welcome to your new register controller!',
            'path' => 'src/Controller/AuthController.php',
        ];

        return $this->resJson($data);
    }

    /**
     * @Route("/login", name="app_login")
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
