<?php

namespace App\Repository;

use App\Entity\Admin;
use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findRecentForAdmin(Admin $admin, int $limit = 15): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.destinataire = :admin')
            ->setParameter('admin', $admin)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnreadForAdmin(Admin $admin): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.destinataire = :admin')
            ->andWhere('n.lu = :lu')
            ->setParameter('admin', $admin)
            ->setParameter('lu', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllAsReadForAdmin(Admin $admin): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.lu', true)
            ->where('n.destinataire = :admin')
            ->andWhere('n.lu = false')
            ->setParameter('admin', $admin)
            ->getQuery()
            ->execute();
    }
}
