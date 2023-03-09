<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Delete as DeleteForm;
use App\Repository\UserRepository;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Delete extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $query,
        private RequestStack $request
    ) {
    }

    #[Route('/delete', methods: ['DELETE'], name: 'app_delete')]
    #[Security(name: 'Bearer')]
    #[
        OA\Tag('User'),
        OA\RequestBody(
            content: new Model(type: DeleteForm::class)
        ),
        OA\Response(
            response: Response::HTTP_NO_CONTENT,
            description: 'Successfully deleted user',
        ),
        OA\Response(
            response: Response::HTTP_UNPROCESSABLE_ENTITY,
            description: 'Form error',
            content: new Model(type: \App\Error::class, groups: ['api'])
        ),
    ]
    public function __invoke(): Response
    {
        $user = new User();
        $form = $this->createForm(DeleteForm::class, $user);
        $form->submit($this->request->getCurrentRequest()->request->all());
        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = $form->getErrors();
            return new JsonResponse(
                new \App\Error($errors[0]->getMessage()),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['groups' => 'api'],
            );
        }
        $model = $form->getData();
        $user = $this->query->findByIdentifier($model->getEmail());
        if ($user === null) {
            return new JsonResponse(
                new \App\Error('User not found'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['groups' => 'api'],
            );
        }
        $this->em->remove($user);
        $this->em->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}