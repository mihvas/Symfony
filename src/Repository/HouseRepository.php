<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\House;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<House>
 */
class HouseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, House::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('h')
            ->getQuery()
            ->getResult();
    }

    public function findById(int $id): ?House
    {
        return $this->find($id);
    }

    public function save(House $house): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($house);
        $entityManager->flush();
    }

    public function deleteById(int $id, bool $flush = true): bool
    {
        $house = $this->find($id);
        if (!$house) {
            return false;
        }

        $entityManager = $this->getEntityManager();
        $entityManager->remove($house);
        if ($flush) {
            $entityManager->flush();
        }

        return true;
    }

    /**
     * @return House[]
     */
    public function findAllFree(): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.free = :free')
            ->setParameter('free', true)
            ->getQuery()
            ->getResult();
    }
}
