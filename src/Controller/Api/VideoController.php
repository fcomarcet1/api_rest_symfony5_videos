<?php

namespace App\Controller\Api;

use App\Entity\Video;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\Services\JwtAuth;
use App\Services\Utils\CheckRequest;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

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
     * Lists all videos paginated.
     *
     * @Route("/", methods={"GET"}, name="app_video_list")
     *
     * @param Request $request
     * @param CheckRequest $checkRequest
     * @param JwtAuth $jwtAuth
     * @param VideoRepository $videoRepository
     * @param EntityManagerInterface $em
     * @param PaginatorInterface $paginator
     * @return response
     */
    public function index(
        Request $request,
        CheckRequest $checkRequest,
        JwtAuth $jwtAuth,
        VideoRepository $videoRepository,
        EntityManagerInterface $em,
        PaginatorInterface $paginator
    ): Response {

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

        // Get credentials from user logged(save in $checkAuthToken)
        $userId = $checkAuthToken->getId();

        // dql query  $dql = "SELECT v FROM App\Entity\Video v WHERE v.user = {$identity->sub} ORDER BY v.id DESC";
        $dql   = "SELECT v FROM App\Entity\Video v ORDER BY v.id DESC";
        $query = $em->createQuery($dql);

        // Get page parameter from url
        $page           = $request->query->getInt('page', 1);
        $items_per_page = 6;

        // Invoke pagination
        $pagination = $paginator->paginate($query, $page, $items_per_page);
        $total      = $pagination->getTotalItemCount();

        if (count($pagination) === 0) {
            $data = [
                'status'  => 'success',
                'code'    => 200,
                'message' => 'No hay videos que mostrar actualmente',
            ];

            return $this->resJson($data);
        }

        // Return response
        $data = [
            'status'            => 'success',
            'code'              => 200,
            'total_items_count' => $total,
            'page_actual'       => $page,
            'items_per_page'    => $items_per_page,
            'total_pages'       => ceil($total / $items_per_page),
            'videos'            => $pagination,
            'user_id'           => $userId,
        ];

        return $this->resJson([$data]);


    }

    /**
     * All videos by user(My videos)
     *
     * @Route("/my-videos", methods={"GET"}, name="app_video_my_videos")
     *
     * @param Request $request
     * @param CheckRequest $checkRequest
     * @param VideoRepository $videoRepository
     * @param JwtAuth $jwtAuth
     * @param EntityManagerInterface $em
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function getVideosByUser(
        Request $request,
        CheckRequest $checkRequest,
        VideoRepository $videoRepository,
        JwtAuth $jwtAuth,
        EntityManagerInterface $em,
        PaginatorInterface $paginator
    ): Response {

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
        // return obj User($identity = true) | array,

        if (!is_object($checkAuthToken) && $checkAuthToken['status'] === 'error') {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "Error. Something wrong in user update. Try again.";
            $data['error']   = $checkAuthToken['message'];

            return $this->resJson($data);
        }

        // Get credentials from user logged(save in $checkAuthToken)
        $userId = $checkAuthToken->getId();

        // dql query  $dql = "SELECT v FROM App\Entity\Video v WHERE v.user = {$identity->sub} ORDER BY v.id DESC";
        $dql   = "SELECT v FROM App\Entity\Video v WHERE v.user = " . $userId . " ORDER BY v.id DESC";
        $query = $em->createQuery($dql);

        // Get page parameter from url
        $page           = $request->query->getInt('page', 1);
        $items_per_page = 6;

        // Invoke pagination
        $pagination = $paginator->paginate($query, $page, $items_per_page);
        $total      = $pagination->getTotalItemCount();

        // Return response
        $data = [
            'status'            => 'success',
            'code'              => 200,
            'total_items_count' => $total,
            'page_actual'       => $page,
            'items_per_page'    => $items_per_page,
            'total_pages'       => ceil($total / $items_per_page),
            'videos'            => $pagination,
            'user_id'           => $userId,
        ];

        return $this->resJson([$data]);
    }

    /**
     * Create new video
     *
     * @Route("/create", methods={"POST"}, name="app_video_create")
     * @param Request $request
     * @param VideoRepository $videoRepository
     * @param UserRepository $userRepository
     * @param JwtAuth $jwtAuth
     * @param CheckRequest $checkRequest
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function create(
        Request $request,
        VideoRepository $videoRepository,
        UserRepository $userRepository,
        JwtAuth $jwtAuth,
        CheckRequest $checkRequest,
        EntityManagerInterface $em
    ): Response {

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
            $data['message'] = "Error. Something wrong in video create. Try again.";
            $data['error']   = $checkAuthToken['message'];

            return $this->resJson($data);
        }

        try {
            // Get data from request
            $params = json_decode($request->getContent());

        } catch (Exception $error) {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "Error. Something wrong in video create. Try again.";
            $data['error']   = $error;

            return $this->resJson($data);
        }

        $title       = (!empty($params->title)) ? trim($params->title) : null;
        $description = (!empty($params->description)) ? trim($params->description) : null;
        $url         = (!empty($params->url)) ? trim($params->url) : null;

        if (is_null($title) || is_null($description) || is_null($url)) {
            return $this->resJson([
                'status'  => "error",
                'code'    => 400,
                'message' => "Any field from create video form is empty.",
            ]);
        }

        // validate data
        //Validate form fields
        $validator = Validation::createValidator();

        // title
        $validateTitle = $validator->validate($title, [
            new NotBlank(),
            new Length([
                'min' => 2,
                'max' => 100,
            ]),
        ]);
        if (count($validateTitle) == !0) {
            $data['code']  = 400;
            $data['error'] = 'title field is not valid';

            return $this->resJson($data);
        }

        // description
        $validateDescription = $validator->validate($description, [
            new NotBlank(),

        ]);
        if (count($validateDescription) == !0) {
            $data['code']  = 400;
            $data['error'] = 'description field is not valid';

            return $this->resJson($data);
        }

        // description
        $validateUrl = $validator->validate($url, [
            new Url(),
        ]);
        if (count($validateUrl) == !0) {
            $data['code']  = 400;
            $data['error'] = 'url field is not valid';

            return $this->resJson($data);
        }

        // get userId from logged user
        $userId = $checkAuthToken->getId();

        // create new obj video
        $user = $userRepository->findOneBy([
            'id' => $userId,
        ]);

        $video = new Video($title, $description, $url, $user);
        $video->setStatus('normal');

        // save in db
        //$doctrine = $this->getDoctrine();
        //$em       = $doctrine->getManager();
        $em->persist($video);
        $em->flush();


        // return response
        return $this->resJson([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'The video was recorded successfully!!',
            'video'   => $video,
        ]);


    }

    /**
     * Video detail
     *
     * @Route("/detail/{id}", methods={"GET"}, name="app_video_detail")
     * @param Request $request
     * @param CheckRequest $checkRequest
     * @param JwtAuth $jwtAuth
     * @param EntityManagerInterface $em
     * @param null $id
     * @return Response
     */
    public function show(
        Request $request,
        CheckRequest $checkRequest,
        JwtAuth $jwtAuth,
        EntityManagerInterface $em,
        $id = null
    ): Response {


        return $this->resJson([
            'message'  => ' video detail',
            'video_id' => (int)$id,
        ]);
    }


    /**
     * Update video
     *
     * @Route("/update/{id}", methods={"PUT","PATCH","POST"}, name="app_video_update")
     * @param Request $request
     * @param CheckRequest $checkRequest
     * @param JwtAuth $jwtAuth
     * @param UserRepository $userRepository
     * @param VideoRepository $videoRepository
     * @param EntityManagerInterface $em
     * @param null $id
     * @return Response
     */
    public function update(
        Request $request,
        CheckRequest $checkRequest,
        JwtAuth $jwtAuth,
        UserRepository $userRepository,
        VideoRepository $videoRepository,
        EntityManagerInterface $em,
        $id = null
    ): Response {

        // check request with checkRequest service.
        $validateRequest = $checkRequest->validateRequest($request);
        if (!$validateRequest) {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "API cant received request parameters";

            return $this->resJson($data);
        }

        // Default response.
        $data = [];

        // Check data from request.
        // Get auth headers(token).
        $authToken = $request->headers->get('Authorization');
        if (!isset($authToken) || empty($authToken)) {
            $data['status']  = 'error';
            $data['code']    = 400;
            $data['message'] = "Forbidden access. API cannot received authorization token";

            return $this->resJson($data);
        }

        // Make service checkAuthToken.
        $checkAuthToken = $jwtAuth->checkAuthToken($authToken, $identity = true);
        // return obj($identity = true) | array-> false,

        if (!is_object($checkAuthToken) && $checkAuthToken['status'] === 'error') {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "Error. Something wrong in video update. Try again.";
            $data['error']   = $checkAuthToken['message'];

            return $this->resJson($data);
        }

        // Check if exists video .
        $issetVideo = $videoRepository->findOneBy(['id' => $id,]);
        if (!is_object($issetVideo) || is_null($issetVideo)) {
            $data['status']  = "error";
            $data['code']    = 404;
            $data['message'] = "Error. The video you are trying to update does not exist.";

            return $this->resJson($data);
        }

        // check if the user is owner video.
        $video = $videoRepository->findOneBy([
            'id' => $id,
            'user' => $checkAuthToken,
        ]);


        if (!is_object($video) || is_null($video)) {
            $data['status']  = "error";
            $data['code']    = 404;
            $data['message'] = "Error. You cannot update this video.";

            return $this->resJson($data);
        }

        // Get data from request
        try {
            // Get data from request
            $params = json_decode($request->getContent());

        } catch (Exception $error) {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "Error. Something wrong in video create. Try again.";
            $data['error']   = $error;

            return $this->resJson($data);
        }

        $title       = (!empty($params->title)) ? trim($params->title) : null;
        $description = (!empty($params->description)) ? trim($params->description) : null;
        $url         = (!empty($params->url)) ? trim($params->url) : null;

        if (is_null($title) || is_null($description) || is_null($url)) {
            return $this->resJson([
                'status'  => "error",
                'code'    => 400,
                'message' => "Any field from create video form is empty.",
            ]);
        }

        // validate data
        //Validate form fields
        $validator = Validation::createValidator();

        // title
        $validateTitle = $validator->validate($title, [
            new NotBlank(),
            new Length([
                'min' => 2,
                'max' => 100,
            ]),
        ]);
        if (count($validateTitle) == !0) {
            $data['code']  = 400;
            $data['error'] = 'title field is not valid';

            return $this->resJson($data);
        }

        // description
        $validateDescription = $validator->validate($description, [
            new NotBlank(),

        ]);
        if (count($validateDescription) == !0) {
            $data['code']  = 400;
            $data['error'] = 'description field is not valid';

            return $this->resJson($data);
        }

        // description
        $validateUrl = $validator->validate($url, [
            new Url(),
        ]);
        if (count($validateUrl) == !0) {
            $data['code']  = 400;
            $data['error'] = 'url field is not valid';

            return $this->resJson($data);
        }
        // set values
        $issetVideo->setTitle($title);
        $issetVideo->setDescription($description);
        $issetVideo->setUrl($url);
        $issetVideo->setUpdatedAt(new DateTime('now'));

        
        // update in db
        $em->persist($issetVideo);
        $em->flush();

        // return response
        return $this->resJson([
            'status'  => 'success',
            'code'    => 200,
            'message' => ' video update successfully',
            'video_updated' => $issetVideo,
        ]);
    }


    /**
     * Delete video
     *
     * @Route("/delete/{id}", methods={"DELETE"}, name="app_video_delete")
     *
     * @param Request $request
     * @param CheckRequest $checkRequest
     * @param JwtAuth $jwtAuth
     * @param VideoRepository $videoRepository
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $em
     * @param $id
     * @return Response
     */
    public function delete(
        Request $request,
        CheckRequest $checkRequest,
        JwtAuth $jwtAuth,
        VideoRepository $videoRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        $id = null
    ): response {

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
            $data['message'] = "Error. Something wrong in video delete. Try again.";
            $data['error']   = $checkAuthToken['message'];

            return $this->resJson($data);
        }

        // Find video to delete
        $video = $videoRepository->findOneBy([
            'id'   => $id,
            'user' => $checkAuthToken,
        ]);

        if (!$video || !is_object($video)) {
            $data['status']  = "error";
            $data['code']    = 404;
            $data['message'] = "Error. El video seleccionado no existe.";

            return $this->resJson($data);
        }

        // delete video
        $em->remove($video);
        $em->flush();


        return $this->resJson([
            'status'        => 'success',
            'code'          => 200,
            'message'       => ' video was deleted successfully',
            'video_deleted' => $video,
        ]);
    }
}
