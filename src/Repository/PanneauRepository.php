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
     *
     * @param string|null $statutActif '1' = uniquement actifs, '0' = uniquement archivés, null = tous
     */
    public function findWithFilters(?string $type = null, ?string $etat = null, ?string $eclairage = null, ?string $recherche = null, ?string $statutActif = null): array
    {
        $qb = $this->createQueryBuilder('p');

        // Filtre sur le statut actif/archivé
        if ($statutActif === '1') {
            // Uniquement actifs
            $qb->andWhere('p.actif = :actif')
               ->setParameter('actif', true);
        } elseif ($statutActif === '0') {
            // Uniquement archivés
            $qb->andWhere('p.actif = :actif')
               ->setParameter('actif', false);
        }

        if ($type) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        if ($etat) {
            if ($etat === 'variable') {
                $qb->leftJoin('p.faces', 'f_etat')
                   ->groupBy('p.id')
                   ->andWhere('COUNT(DISTINCT f_etat.etat) > 1');
            } else {
                $qb->leftJoin('p.faces', 'f_etat')
                   ->andWhere('f_etat.etat = :etat')
                   ->setParameter('etat', $etat)
                   ->distinct();
            }
        }

        if ($eclairage !== null && $eclairage !== '') {
            $qb->andWhere('p.eclairage = :eclairage')
               ->setParameter('eclairage', $eclairage === '1' || $eclairage === 'true');
        }

        if ($recherche) {
            $qb->andWhere('p.reference LIKE :recherche OR p.emplacement LIKE :recherche OR p.quartier LIKE :recherche OR p.visibilite LIKE :recherche')
               ->setParameter('recherche', '%' . $recherche . '%');
        }

        return $qb->orderBy('p.reference', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve la dernière référence générée pour créer la suivante
     */
    public function findLastReferenceNumber(): int
    {
        $lastPanneau = $this->createQueryBuilder('p')
            ->where('p.reference LIKE :pattern')
            ->setParameter('pattern', 'PAN-%')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastPanneau && $lastPanneau->getReference()) {
            // Extraire le numéro de la référence (ex: PAN-001 -> 1)
            if (preg_match('/PAN-(\d+)/', $lastPanneau->getReference(), $matches)) {
                return (int) $matches[1];
            }
        }

        return 0;
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
