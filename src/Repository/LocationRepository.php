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
     * Retourne une location par ID avec ses paiements chargés.
     */
    public function findOneWithPaiements(int $id): ?Location
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.paiements', 'p')
            ->addSelect('p')
            ->where('l.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne les locations actives (exclut les annulées).
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
     * Retourne le revenu mensuel total des locations actives (exclut les annulées).
     */
    public function getRevenuMensuelActif(): float
    {
        $now = new \DateTime();
        $result = $this->createQueryBuilder('l')
            ->select('SUM(l.montantMensuel) as total')
            ->where('l.dateDebut <= :now')
            ->andWhere('l.dateFin >= :now')
            ->andWhere('l.statut != :annulee')
            ->setParameter('now', $now)
            ->setParameter('annulee', 'annulee')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Retourne les locations se terminant bientôt (dans les X jours).
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
     * Retourne les locations se terminant bientôt avec leurs paiements chargés.
     */
    public function findFinissantBientotWithPaiements(int $jours = 30): array
    {
        $now = new \DateTime();
        $dateLimite = (clone $now)->modify("+{$jours} days");

        return $this->createQueryBuilder('l')
            ->leftJoin('l.paiements', 'p')
            ->addSelect('p')
            ->where('l.dateFin >= :now')
            ->andWhere('l.dateFin <= :dateLimite')
            ->setParameter('now', $now)
            ->setParameter('dateLimite', $dateLimite)
            ->orderBy('l.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les N dernières locations avec leurs paiements.
     */
    public function findDernieresWithPaiements(int $maxResults = 5): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.paiements', 'p')
            ->leftJoin('l.client', 'c')
            ->leftJoin('l.face', 'f')
            ->leftJoin('f.panneau', 'pan')
            ->addSelect('p', 'c', 'f', 'pan')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($maxResults)
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

    /**
     * Retourne les revenus prévus (montant mensuel des locations actives) par mois pour une année.
     * Pour chaque mois, somme les montantMensuel des locations actives pendant ce mois.
     */
    public function getRevenusPrevusParMois(int $annee): array
    {
        $data = array_fill(1, 12, 0.0);
        $conn = $this->getEntityManager()->getConnection();

        for ($mois = 1; $mois <= 12; $mois++) {
            $debutMois = sprintf('%d-%02d-01', $annee, $mois);
            $finMois = date('Y-m-t', strtotime($debutMois));

            $sql = "
                SELECT COALESCE(SUM(l.montant_mensuel), 0) as total
                FROM location l
                WHERE l.statut != 'annulee'
                  AND l.date_debut <= :fin_mois
                  AND l.date_fin >= :debut_mois
            ";
            $result = $conn->executeQuery($sql, [
                'debut_mois' => $debutMois,
                'fin_mois' => $finMois,
            ])->fetchOne();

            $data[$mois] = (float) ($result ?? 0);
        }
        return $data;
    }

    /**
     * Retourne les revenus prévus par période (jour, semaine ou mois).
     * Pour les périodes courtes (jour/semaine), proratise le montant mensuel.
     *
     * @param \DateTimeInterface $debut
     * @param \DateTimeInterface $fin
     */
    public function getRevenusPrevusParPeriode(\DateTimeInterface $debut, \DateTimeInterface $fin, string $granularite = 'month'): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $debutStr = $debut->format('Y-m-d');
        $finStr = $fin->format('Y-m-d');

        $labels = [];
        $valuesByKey = [];

        /** @var \DateTime $current */
        $current = $debut instanceof \DateTime ? clone $debut : new \DateTime($debut->format('c'));
        /** @var \DateTime $finDt */
        $finDt = $fin instanceof \DateTime ? clone $fin : new \DateTime($fin->format('c'));

        if ($granularite === 'day') {
            $current->setTime(0, 0);
            $finDt->setTime(23, 59, 59);
            while ($current <= $finDt) {
                $key = $current->format('Y-m-d');
                $labels[] = $current->format('d/m');
                $valuesByKey[$key] = 0.0;
                $current->modify('+1 day');
            }
            $sql = "
                SELECT COALESCE(SUM(l.montant_mensuel) / 30, 0) as total
                FROM location l
                WHERE l.statut != 'annulee'
                  AND l.date_debut <= :jour
                  AND l.date_fin >= :jour
            ";
            foreach (array_keys($valuesByKey) as $jour) {
                $result = $conn->executeQuery($sql, ['jour' => $jour])->fetchOne();
                $valuesByKey[$jour] = (float) ($result ?? 0);
            }
        } elseif ($granularite === 'week') {
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
            $weekStarts = array_keys($valuesByKey);
            foreach ($weekStarts as $idx => $yw) {
                $weekStart = (new \DateTime())->setISODate((int) substr($yw, 0, 4), (int) substr($yw, 5, 2))->format('Y-m-d');
                $weekEnd = (new \DateTime($weekStart))->modify('+6 days')->format('Y-m-d');
                $sql = "
                    SELECT COALESCE(SUM(l.montant_mensuel) * 7 / 30, 0) as total
                    FROM location l
                    WHERE l.statut != 'annulee'
                      AND l.date_debut <= :fin_semaine
                      AND l.date_fin >= :debut_semaine
                ";
                $result = $conn->executeQuery($sql, ['debut_semaine' => $weekStart, 'fin_semaine' => $weekEnd])->fetchOne();
                $valuesByKey[$yw] = (float) ($result ?? 0);
            }
        } else {
            $current = $debut instanceof \DateTime ? clone $debut : new \DateTime($debut->format('c'));
            $current->setTime(0, 0);
            $finDt->setTime(23, 59, 59);
            $moisLibelles = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
            while ($current <= $finDt) {
                $key = $current->format('Y-m');
                $labels[] = $moisLibelles[(int) $current->format('n')] . ' ' . $current->format('y');
                $debutMois = $current->format('Y-m-01');
                $finMois = $current->format('Y-m-t');
                $sql = "
                    SELECT COALESCE(SUM(l.montant_mensuel), 0) as total
                    FROM location l
                    WHERE l.statut != 'annulee'
                      AND l.date_debut <= :fin_mois
                      AND l.date_fin >= :debut_mois
                ";
                $result = $conn->executeQuery($sql, ['debut_mois' => $debutMois, 'fin_mois' => $finMois])->fetchOne();
                $valuesByKey[$key] = (float) ($result ?? 0);
                $current->modify('first day of next month');
            }
        }

        return ['labels' => $labels, 'values' => array_values($valuesByKey)];
    }

    /**
     * Retourne les locations pour le calendrier (entre date début et date fin, exclut annulées).
     */
    public function findForCalendar(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.client', 'c')
            ->leftJoin('l.face', 'f')
            ->leftJoin('f.panneau', 'p')
            ->addSelect('c', 'f', 'p')
            ->where('l.statut != :annulee')
            ->andWhere('l.dateDebut <= :end')
            ->andWhere('l.dateFin >= :start')
            ->setParameter('annulee', 'annulee')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('l.dateDebut', 'ASC')
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
