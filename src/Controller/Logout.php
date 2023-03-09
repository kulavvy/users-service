<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Logout as LogoutForm;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Logout extends AbstractController
{
    public function __construct(private RequestStack $request)
    {
    }

    #[Route('/logout', methods: ['POST'], name: 'app_logout')]
    #[Security(name: 'Bearer')]
    #[
        OA\Post(description: 'Destroy access token'),
        OA\Tag('User'),
        OA\RequestBody(
            content: new Model(type: LogoutForm::class)
        ),
        OA\Response(
            response: Response::HTTP_NO_CONTENT,
            description: 'Successfully logged out'
        ),
        OA\Response(
            response: Response::HTTP_UNPROCESSABLE_ENTITY,
            description: 'Form error',
            content: new Model(type: \App\Error::class, groups: ['api'])
        ),
    ]
    public function __invoke(): JsonResponse
    {
        $form = $this->createForm(LogoutForm::class, new User());
        $form->submit($this->request->getCurrentRequest()->request->all());
        if ($form->isSubmitted() && $form->isValid()) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        $errors = $form->getErrors();
        return new JsonResponse(
            new \App\Error($errors[0]->getMessage()),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            ['groups' => 'api'],
        );
    }
}