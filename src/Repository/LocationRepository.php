<?php

namespace App\Repository;

use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Location>
 */
class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    /**
     * Retourne les locations actives
     */
    public function findActive(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('l')
            ->where('l.dateDebut <= :now')
            ->andWhere('l.dateFin >= :now')
            ->setParameter('now', $now)
            ->orderBy('l.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les locations se terminant bientôt (dans les X jours)
     */
    public function findFinissantBientot(int $jours = 30): array
    {
        $now = new \DateTime();
        $dateLimite = (clone $now)->modify("+{$jours} days");
        
        return $this->createQueryBuilder('l')
            ->where('l.dateFin >= :now')
            ->andWhere('l.dateFin <= :dateLimite')
            ->setParameter('now', $now)
            ->setParameter('dateLimite', $dateLimite)
            ->orderBy('l.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les locations impayées
     */
    public function findImpayees(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.estPaye = :estPaye')
            ->setParameter('estPaye', false)
            ->orderBy('l.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Location[] Returns an array of Location objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Location
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
