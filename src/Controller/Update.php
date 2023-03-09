<?php

namespace App\Controller;

use App\Form\Update as UpdateForm;
use App\Entity\User as UserEntity;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Update extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $query,
        private RequestStack $request
    ) {
    }

    #[Route('/update/my', methods: ['PATCH'], name: 'app_my_update')]
    #[Route('/update/{id}', methods: ['PATCH'], name: 'app_user_update')]
    #[Security(name: 'Bearer')]
    #[
        OA\Tag('User'),
        OA\RequestBody(
            description: 'Update current user',
            content: new Model(type: UpdateForm::class, options: ['my_profile' => true])
        ),
        // OA\RequestBody(
        //     description: 'Update user by ID',
        //     content: new Model(type: UpdateForm::class)
        // ),
        OA\Response(
            response: Response::HTTP_OK,
            description: 'Successfully updated user',
            content: new Model(type: UserEntity::class, groups: ['api'])
        ),
        OA\Response(
            response: Response::HTTP_UNPROCESSABLE_ENTITY,
            description: 'Form error',
            content: new Model(type: \App\Error::class, groups: ['api'])
        ),
    ]
    public function __invoke(int $id = null): JsonResponse
    {
        $update = new \App\Entity\DTO\Update();
        $form = $this->createForm(
            UpdateForm::class,
            $update,
            ['my_profile' => $id === null],
        );
        $userId = $id ?? 1; // TODO grab value from token
        $user = $this->query->findOneBy(['id' => $userId]);
        if ($user === null && $id === null) {
            throw new \LogicException('User required but not found');
        }
        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = $form->getErrors();
            return new JsonResponse(
                new \App\Error($errors[0]->getMessage()),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['groups' => 'api'],
            );
        }
        $model = $form->getData();
        if ($user->getEmail() !== $model->getEmail()) {
            $this->validateIsEmailExists($model);
        }
        $this->em->getConnection()->beginTransaction();
        try {
            $this->saveUser($user, $model);
            if ($id === null) {
                $this->uploadAvatar($form, $userId);
            }
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
        return new JsonResponse(
            $user,
            Response::HTTP_OK,
            ['groups' => 'api']
        );
    }

    private function uploadAvatar(FormInterface $form, int $userId)
    {
        $path = $form->get('avatar')->getData();
        if (!$path) {
            return;
        }

        $fileName = sha1($userId) . '.' . $path->guessExtension(); // move this
        try {
            $path->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads',
                $fileName
            );
        } catch (FileException $e) {
            return new JsonResponse(
                new \App\Error($e->getMessage()),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['groups' => 'api'],
            );
        }
    }

    private function validateIsEmailExists(UserEntity $model)
    {
        $user = $this->query->findByIdentifier($model->getEmail());
        if ($user === null) {
            return;
        }
        return new JsonResponse(
            new \App\Error('Email already exists'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            ['groups' => 'api'],
        );
    }

    private function saveUser(UserEntity $user, \App\Entity\DTO\Update $formModel)
    {
        $user
            ->setFirstName($formModel->getFirstName())
            ->setLastName($formModel->getLastName())
            ->setEmail($formModel->getEmail())
            ->setPassword($formModel->getPassword());
        $this->em->persist($user);
        $this->em->flush();
    }
}