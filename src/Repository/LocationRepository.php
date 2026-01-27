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
     * Retourne les locations actives (exclut les annulées)
     */
    public function findActive(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('l')
            ->where('l.dateDebut <= :now')
            ->andWhere('l.dateFin >= :now')
            ->andWhere('l.statut != :annulee')
            ->setParameter('now', $now)
            ->setParameter('annulee', 'annulee')
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
     * Retourne les locations impayées (calculé à partir des paiements)
     */
    public function findImpayees(): array
    {
        $locations = $this->createQueryBuilder('l')
            ->leftJoin('l.paiements', 'p')
            ->addSelect('p')
            ->orderBy('l.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
        
        // Filtrer les locations impayées en calculant le statut
        return array_filter($locations, function($location) {
            return $location->isImpaye();
        });
    }

    /**
     * Recherche avec filtres (inclut les annulées par défaut)
     */
    public function findWithFilters(?string $statut = null, ?string $estPaye = null, ?int $clientId = null, ?string $recherche = null, ?bool $inclureAnnulees = true): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.client', 'c')
            ->leftJoin('l.face', 'f')
            ->leftJoin('f.panneau', 'p')
            ->leftJoin('l.paiements', 'pai')
            ->addSelect('pai');

        // Exclure les annulées seulement si explicitement demandé
        if (!$inclureAnnulees) {
            $qb->andWhere('l.statut != :annulee')
               ->setParameter('annulee', 'annulee');
        }

        $now = new \DateTime();

        if ($statut) {
            if ($statut === 'active') {
                $qb->andWhere('l.dateDebut <= :now')
                   ->andWhere('l.dateFin >= :now')
                   ->andWhere('l.statut != :annulee')
                   ->setParameter('now', $now)
                   ->setParameter('annulee', 'annulee');
            } elseif ($statut === 'terminee') {
                $qb->andWhere('l.dateFin < :now')
                   ->andWhere('l.statut != :annulee')
                   ->setParameter('now', $now)
                   ->setParameter('annulee', 'annulee');
            } elseif ($statut === 'avenir') {
                $qb->andWhere('l.dateDebut > :now')
                   ->andWhere('l.statut != :annulee')
                   ->setParameter('now', $now)
                   ->setParameter('annulee', 'annulee');
            } elseif ($statut === 'annulee') {
                $qb->andWhere('l.statut = :annulee')
                   ->setParameter('annulee', 'annulee');
            }
        }

        if ($clientId) {
            $qb->andWhere('c.id = :clientId')
               ->setParameter('clientId', $clientId);
        }

        if ($recherche) {
            $qb->andWhere('c.nom LIKE :recherche OR f.lettre LIKE :recherche OR p.reference LIKE :recherche OR p.emplacement LIKE :recherche')
               ->setParameter('recherche', '%' . $recherche . '%');
        }

        $locations = $qb->orderBy('l.dateDebut', 'DESC')
                  ->getQuery()
                  ->getResult();
        
        // Filtrer par statut de paiement si demandé (calculé à partir des paiements)
        if ($estPaye !== null && $estPaye !== '') {
            $locations = array_filter($locations, function($location) use ($estPaye) {
                $statutPaiement = $location->getStatutPaiement();
                if ($estPaye === '1') {
                    return $statutPaiement === 'paye';
                } elseif ($estPaye === '2') {
                    return $statutPaiement === 'partiellement_paye';
                } else {
                    return $statutPaiement === 'impaye';
                }
            });
        }

        return array_values($locations);
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
