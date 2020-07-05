<?php

namespace App\Repository;

use App\Entity\ExcludeManga;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ExcludeManga|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExcludeManga|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExcludeManga[]    findAll()
 * @method ExcludeManga[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExcludeMangaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExcludeManga::class);
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