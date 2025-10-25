<?php
namespace App\Repository;

use App\Entity\Character;
use App\Entity\PvPChallenge;
use App\Enum\PvpStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PvPChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PvPChallenge::class);
    }

    public function findPendingForOpponent(Character $me): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.opponent = :me')
            ->andWhere('c.status = :s')
            ->setParameter('me', $me)
            ->setParameter('s', PvpStatus::Pending)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function existsOngoingBetween(Character $a, Character $b): bool
    {
        return (bool) $this->createQueryBuilder('c')
            ->andWhere('(c.challenger = :a AND c.opponent = :b) OR (c.challenger = :b AND c.opponent = :a)')
            ->andWhere('c.status != :done')
            ->setParameter('a', $a)->setParameter('b', $b)
            ->setParameter('done', PvpStatus::Done)
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    public function recentHistoryFor(Character $me, int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('(c.challenger = :me OR c.opponent = :me)')
            ->andWhere('c.status = :done')
            ->andWhere('c.combat IS NOT NULL')
            ->setParameter('me', $me)
            ->setParameter('done', PvpStatus::Done)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }
}
