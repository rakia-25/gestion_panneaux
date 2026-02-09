<?php

namespace App\Repository;

use App\Entity\Paiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Paiement>
 */
class PaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }

    /**
     * Retourne tous les paiements avec leur location, triés par date de paiement (plus récent en premier).
     *
     * @return Paiement[]
     */
    public function findAllOrderedByDateWithLocation(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.location', 'l')
            ->addSelect('l')
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les N derniers paiements valides avec leur location.
     *
     * @return Paiement[]
     */
    public function findDerniersValidesWithLocation(int $maxResults = 15): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.location', 'l')
            ->leftJoin('l.client', 'c')
            ->leftJoin('l.face', 'f')
            ->leftJoin('f.panneau', 'pan')
            ->addSelect('l', 'c', 'f', 'pan')
            ->where('p.statut = :valide')
            ->setParameter('valide', 'valide')
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les revenus encaissés (paiements valides) par mois pour une année donnée.
     * Format: [1 => 150000, 2 => 200000, ...]
     */
    public function getRevenusEncaissesParMois(int $annee): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT MONTH(p.date_paiement) as mois, COALESCE(SUM(p.montant), 0) as total
            FROM paiement p
            WHERE YEAR(p.date_paiement) = :annee
              AND p.statut = 'valide'
            GROUP BY MONTH(p.date_paiement)
            ORDER BY mois
        ";
        $result = $conn->executeQuery($sql, ['annee' => $annee])->fetchAllAssociative();

        $data = array_fill(1, 12, 0.0);
        foreach ($result as $row) {
            $data[(int) $row['mois']] = (float) $row['total'];
        }
        return $data;
    }

    /**
     * Retourne les revenus encaissés par période (jour, semaine ou mois).
     * Retourne ['labels' => [...], 'values' => [...]]
     *
     * @param \DateTimeInterface $debut
     * @param \DateTimeInterface $fin
     */
    public function getRevenusEncaissesParPeriode(\DateTimeInterface $debut, \DateTimeInterface $fin, string $granularite = 'month'): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $debutStr = $debut->format('Y-m-d');
        $finStr = $fin->format('Y-m-d');

        $labels = [];
        $values = [];
        $valuesByKey = [];

        /** @var \DateTime $current */
        $current = $debut instanceof \DateTime ? clone $debut : new \DateTime($debut->format('c'));
        $finDt = $fin instanceof \DateTime ? clone $fin : new \DateTime($fin->format('c'));

        if ($granularite === 'day') {
            $sql = "
                SELECT DATE(p.date_paiement) as dt, COALESCE(SUM(p.montant), 0) as total
                FROM paiement p
                WHERE p.date_paiement >= :debut AND p.date_paiement < DATE_ADD(:fin, INTERVAL 1 DAY)
                  AND p.statut = 'valide'
                GROUP BY DATE(p.date_paiement)
                ORDER BY dt
            ";
            $result = $conn->executeQuery($sql, ['debut' => $debutStr, 'fin' => $finStr])->fetchAllAssociative();
            $current->setTime(0, 0);
            $finDt->setTime(23, 59, 59);
            while ($current <= $finDt) {
                $key = $current->format('Y-m-d');
                $labels[] = $current->format('d/m');
                $valuesByKey[$key] = 0.0;
                $current->modify('+1 day');
            }
            foreach ($result as $row) {
                $valuesByKey[$row['dt']] = (float) $row['total'];
            }
            $values = array_values($valuesByKey);
        } elseif ($granularite === 'week') {
            $sql = "
                SELECT YEARWEEK(p.date_paiement, 3) as yw, COALESCE(SUM(p.montant), 0) as total
                FROM paiement p
                WHERE p.date_paiement >= :debut AND p.date_paiement < DATE_ADD(:fin, INTERVAL 1 DAY)
                  AND p.statut = 'valide'
                GROUP BY YEARWEEK(p.date_paiement, 3)
                ORDER BY yw
            ";
            $result = $conn->executeQuery($sql, ['debut' => $debutStr, 'fin' => $finStr])->fetchAllAssociative();
            $current->setTime(0, 0);
            $current->modify('monday this week');
            if ($current > $debut) {
                $current->modify('-1 week');
            }
            $finDt->setTime(23, 59, 59);
            while ($current <= $finDt) {
                $yw = $current->format('o') . '-' . str_pad($current->format('W'), 2, '0', STR_PAD_LEFT);
                $labels[] = $current->format('d/m');
                $valuesByKey[$yw] = 0.0;
                $current->modify('+1 week');
            }
            foreach ($result as $row) {
                $yw = (int) $row['yw'];
                $key = floor($yw / 100) . '-' . str_pad($yw % 100, 2, '0', STR_PAD_LEFT);
                if (isset($valuesByKey[$key])) {
                    $valuesByKey[$key] = (float) $row['total'];
                }
            }
            $values = array_values($valuesByKey);
        } else {
            $sql = "
                SELECT YEAR(p.date_paiement) as an, MONTH(p.date_paiement) as mois, COALESCE(SUM(p.montant), 0) as total
                FROM paiement p
                WHERE p.date_paiement >= :debut AND p.date_paiement < DATE_ADD(:fin, INTERVAL 1 DAY)
                  AND p.statut = 'valide'
                GROUP BY YEAR(p.date_paiement), MONTH(p.date_paiement)
                ORDER BY an, mois
            ";
            $result = $conn->executeQuery($sql, ['debut' => $debutStr, 'fin' => $finStr])->fetchAllAssociative();
            $current = $debut instanceof \DateTime ? clone $debut : new \DateTime($debut->format('c'));
            $current->setTime(0, 0);
            $finDt->setTime(23, 59, 59);
            $moisLibelles = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
            while ($current <= $finDt) {
                $key = $current->format('Y-m');
                $labels[] = $moisLibelles[(int) $current->format('n')] . ' ' . $current->format('y');
                $valuesByKey[$key] = 0.0;
                $current->modify('first day of next month');
            }
            foreach ($result as $row) {
                $key = $row['an'] . '-' . str_pad($row['mois'], 2, '0', STR_PAD_LEFT);
                $valuesByKey[$key] = (float) $row['total'];
            }
            $values = array_values($valuesByKey);
        }

        return ['labels' => $labels, 'values' => $values];
    }

//    /**
//     * @return Paiement[] Returns an array of Paiement objects
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

//    public function findOneBySomeField($value): ?Paiement
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
