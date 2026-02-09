<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/notifications')]
class NotificationController extends AbstractController
{
    #[Route('/', name: 'app_notification_index', methods: ['GET'])]
    public function index(NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $notifications = $notificationRepository->findRecentForAdmin($user, 50);
        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/dropdown', name: 'app_notification_dropdown', methods: ['GET'])]
    public function dropdown(NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }
        $notifications = $notificationRepository->findRecentForAdmin($user, 10);
        $unreadCount = $notificationRepository->countUnreadForAdmin($user);
        return $this->render('notification/_dropdown.html.twig', [
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    #[Route('/tout-lu', name: 'app_notification_mark_all_read', methods: ['POST'])]
    public function markAllRead(NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_dashboard');
        }
        $notificationRepository->markAllAsReadForAdmin($user);
        return $this->redirectToRoute('app_notification_index');
    }

    #[Route('/{id}/voir', name: 'app_notification_voir', methods: ['GET'])]
    public function voir(Notification $notification, EntityManagerInterface $entityManager, RouterInterface $router): Response
    {
        $user = $this->getUser();
        if (!$user || $notification->getDestinataire() !== $user) {
            return $this->redirectToRoute('app_dashboard');
        }
        $notification->setLu(true);
        $entityManager->flush();
        if ($notification->getRoute() && $notification->getRouteParams()) {
            return $this->redirect($router->generate($notification->getRoute(), $notification->getRouteParams()));
        }
        return $this->redirectToRoute('app_notification_index');
    }

    #[Route('/{id}/lu', name: 'app_notification_mark_read', methods: ['POST'])]
    public function markRead(Notification $notification, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || $notification->getDestinataire() !== $user) {
            return $this->redirectToRoute('app_dashboard');
        }
        $notification->setLu(true);
        $entityManager->flush();
        return $this->redirectToRoute('app_notification_index');
    }
}
