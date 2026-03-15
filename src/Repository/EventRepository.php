<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @return Event[] Returns upcoming events ordered by date
     */
    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.date >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[] Returns all events ordered by date
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
