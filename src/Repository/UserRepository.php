<?php

namespace App\Repository;

use App\Entity\DTO\Credentials;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return User|null Return an User object
     */
    public function findByIdentifier(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getFirstResult();
    }

    /**
     * @return bool Is user credientials correct
     */
    public function verifyUserCredientials(Credentials $credentials): bool
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email AND u.password_hash = :password_hash')
            ->setParameter('email', $credentials->getEmail())
            ->setParameter('password_hash', $credentials->getPassword())
            ->getQuery()
            ->getFirstResult() !== null;
    }
}
