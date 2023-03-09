<?php

namespace App\Controller;

use App\Entity\User as UserEntity;
use App\Repository\UserRepository;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Index extends AbstractController
{
    public function __construct(private UserRepository $query)
    {
    }

    #[Route('/users', methods: ['GET'], name: 'app_list')]
    #[Security(name: 'Bearer')]
    #[
        OA\Tag('Users'),
        OA\Response(
            content: new Model(type: UserEntity::class, groups: ['api']), // TODO make it as list
            response: Response::HTTP_OK,
            description: 'List of users',
        ),
    ]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(
            $this->query->findAll(),
            Response::HTTP_OK,
            ['groups' => 'api'],
        );
    }
}