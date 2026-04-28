<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Services\NotificationService;
use CodeIgniter\HTTP\ResponseInterface;

class Notifications extends BaseController
{
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    public function index(): ResponseInterface
    {
        $userId = auth()->id();
        $notifications = $this->notificationService->getUserNotifications($userId);

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'data' => $notifications,
            ]);
    }

    public function markAsRead(int $id): ResponseInterface
    {
        $userId = auth()->id();

        $success = $this->notificationService->markAsRead($id, $userId);

        if (!$success) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'message' => 'Notification not found or access denied.',
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'message' => 'Notification marked as read.',
            ]);
    }

    public function markAllAsRead(): ResponseInterface
    {
        $userId = auth()->id();

        $success = $this->notificationService->markAllAsRead($userId);

        if (!$success) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'message' => 'Failed to mark notifications as read.',
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'message' => 'All notifications marked as read.',
            ]);
    }
}
