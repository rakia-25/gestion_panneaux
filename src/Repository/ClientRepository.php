<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * Recherche avec filtres
     */
    public function findWithFilters(?string $type = null, ?string $ville = null, ?string $recherche = null): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($type) {
            $qb->andWhere('c.type = :type')
               ->setParameter('type', $type);
        }

        if ($ville) {
            $qb->andWhere('c.ville = :ville')
               ->setParameter('ville', $ville);
        }

        if ($recherche) {
            $qb->andWhere('c.nom LIKE :recherche OR c.email LIKE :recherche OR c.telephone LIKE :recherche OR c.adresse LIKE :recherche')
               ->setParameter('recherche', '%' . $recherche . '%');
        }

        return $qb->orderBy('c.nom', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    //    /**
    //     * @return Client[] Returns an array of Client objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Client
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
