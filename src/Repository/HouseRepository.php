<?php

namespace App\Repository;

use App\Entity\House;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Cake\Chronos\Chronos;

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
        //print_r($house);
        $entityManager->flush();

    }

    public function findByAvailablePeriod(Chronos $start, Chronos $end): array
    {
        $qb = $this->createQueryBuilder('h');

        return $qb
            ->where('h.free = true')
            ->andWhere('h.date_start <= :start OR h.date_start IS NULL')
            ->andWhere('h.date_end >= :end OR h.date_end IS NULL')
            ->setParameter('start', $start->toDateString())
            ->setParameter('end', $end->toDateString())
            ->orderBy('h.date_start', 'ASC')
            ->orderBy('h.date_end', 'DESC')
            ->getQuery()
            ->getResult();
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
