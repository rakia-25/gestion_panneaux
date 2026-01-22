<?php

namespace App\Repository;

use App\Entity\Panneau;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Panneau>
 */
class PanneauRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Panneau::class);
    }

    /**
     * Recherche avec filtres
     */
    public function findWithFilters(?string $type = null, ?string $etat = null, ?string $eclairage = null, ?string $recherche = null): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($type) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        if ($etat) {
            $qb->andWhere('p.etat = :etat')
               ->setParameter('etat', $etat);
        }

        if ($eclairage !== null && $eclairage !== '') {
            $qb->andWhere('p.eclairage = :eclairage')
               ->setParameter('eclairage', $eclairage === '1' || $eclairage === 'true');
        }

        if ($recherche) {
            $qb->andWhere('p.reference LIKE :recherche OR p.emplacement LIKE :recherche OR p.quartier LIKE :recherche OR p.rue LIKE :recherche')
               ->setParameter('recherche', '%' . $recherche . '%');
        }

        return $qb->orderBy('p.reference', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    //    /**
    //     * @return Panneau[] Returns an array of Panneau objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Panneau
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
