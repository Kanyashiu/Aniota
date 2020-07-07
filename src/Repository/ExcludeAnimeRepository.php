<?php

namespace App\Repository;

use App\Entity\ExcludeAnime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ExcludeAnime|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExcludeAnime|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExcludeAnime[]    findAll()
 * @method ExcludeAnime[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExcludeAnimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExcludeAnime::class);
    }

    public function findByMalId($id)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.mal_id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }
}
