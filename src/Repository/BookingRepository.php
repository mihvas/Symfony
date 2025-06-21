<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('b')
            ->getQuery()
            ->getResult();
    }

    public function findById(int $id): ?Booking
    {
        return $this->find($id);
    }

    public function save(Booking $booking, bool $flush = true): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($booking);

        if ($flush) {
            $entityManager->flush();
        }
    }

    public function deleteById(int $id, bool $flush = true): bool
    {
        $booking = $this->find($id);

        if (!$booking) {
            return false;
        }

        $entityManager = $this->getEntityManager();
        $entityManager->remove($booking);

        if ($flush) {
            $entityManager->flush();
        }

        return true;
    }
}
