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
     * @Route("/register", name="app_register")
     * @return Response
     */
    public function register(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new register controller!',
            'path' => 'src/Controller/AuthController.php',
        ]);
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
