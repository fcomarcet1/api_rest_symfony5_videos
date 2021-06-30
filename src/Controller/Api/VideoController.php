<?php

namespace App\Controller\Api;

use App\Repository\VideoRepository;
use App\Services\JwtAuth;
use App\Services\Utils\CheckRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/video")
 */
class VideoController extends AbstractController
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
     * Lists all videos.
     *
     * @Route("/", methods={"GET"}, name="app_video_show")
     * @param VideoRepository $videoRepository
     * @return response
     */
    public function index(VideoRepository $videoRepository): Response
    {
        $videos = $videoRepository->findAll();
        if (count($videos) <= 0) {
            return $this->resJson([
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Ops nothing to see. Create a new video',
            ]);
        }

        return $this->resJson([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Videos list',
            'videos'  => $videos,
        ]);
    }

    /**
     * Create new video
     *
     * @Route("/create", methods={"POST"}, name="app_video_create")
     * @param Request $request
     * @param VideoRepository $videoRepository
     * @param JwtAuth $jwtAuth
     * @param CheckRequest $checkRequest
     * @return Response
     */
    public function create(
        Request $request,
        VideoRepository $videoRepository,
        JwtAuth $jwtAuth,
        CheckRequest $checkRequest
    ): Response {

        // default response
        $data = [
            'message' => 'test videoController create video ',
        ];

        return $this->resJson($data);


    }
}
