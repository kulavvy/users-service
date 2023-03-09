<?php

namespace App\Controller;

use App\Entity\Token;
use App\Form\Credentials as CredentialsForm;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Login extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private RequestStack $request)
    {
    }

    #[Route('/login', methods: ['POST'], name: 'app_login')]
    #[Security(name: null)]
    #[
        OA\Post(description: 'Recive access token'),
        OA\Tag('Guest'),
        OA\RequestBody(
            content: new Model(type: CredentialsForm::class)
        ),
        OA\Response(
            response: Response::HTTP_OK,
            description: 'Successfully logged in',
            content: new Model(type: Token::class, groups: ['api'])
        ),
        OA\Response(
            response: Response::HTTP_UNPROCESSABLE_ENTITY,
            description: 'Form error',
            content: new Model(type: \App\Error::class, groups: ['api'])
        ),
    ]
    public function __invoke(): JsonResponse
    {
        $login = new \App\Entity\DTO\Credentials();
        $form = $this->createForm(CredentialsForm::class, $login);
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
        if (!$this->userRepository->verifyUserCredientials($model)){
            return new JsonResponse(
                new \App\Error('Invalid credientials'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['groups' => 'api'],
            );
        }

        $token = (new Token())
            ->setUserId($this->userRepository->findByIdentifier($model->getEmail()));
        $this->em->persist($token);
        $this->em->flush();

        return new JsonResponse(
            $token,
            Response::HTTP_OK,
            ['groups' => 'api']
        );
    }
}