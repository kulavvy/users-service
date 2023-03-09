<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Register as RegisterForm;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Register extends AbstractController
{
    public function __construct(
        private RequestStack $request,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('/register', methods: ['POST'], name: 'app_register')]
    #[Security(name: null)]
    #[
        OA\Tag('Guest'),
        OA\RequestBody(
            content: new Model(type: RegisterForm::class)
        ),
        OA\Response(
            response: Response::HTTP_NO_CONTENT,
            description: 'Successfully signed in'
        ),
        OA\Response(
            response: Response::HTTP_UNPROCESSABLE_ENTITY,
            description: 'Form error',
            content: new Model(type: \App\Error::class, groups: ['api'])
        ),
    ]
    public function __invoke(): JsonResponse
    {
        $model = new User();
        $form = $this->createForm(RegisterForm::class, $model);
        $form->submit($this->request->getCurrentRequest()->request->all());
        if ($form->isSubmitted() && $form->isValid()) {
            $model = $form->getData();
            $this->em->persist($model);
            $this->em->flush();
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