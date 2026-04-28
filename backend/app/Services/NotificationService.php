<?php

namespace App\Services;

use App\Models\NotificationModel;
use App\Models\RoleModel;
use App\Models\AppUserProvider;

class NotificationService
{
    private NotificationModel $notificationModel;
    private RoleModel $roleModel;
    private AppUserProvider $userProvider;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
        $this->roleModel = new RoleModel();
        $this->userProvider = new AppUserProvider();
    }

    public function sendToUser(int $userId, string $title, string $message, string $type, ?int $relatedId = null): ?int
    {
        $data = [
            'user_id'    => $userId,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'related_id' => $relatedId,
            'is_read'    => false,
        ];

        $insertId = $this->notificationModel->insert($data, true);
        return is_numeric($insertId) ? (int)$insertId : null;
    }

    public function sendToRole(string $roleName, string $title, string $message, string $type, ?int $relatedId = null): array
    {
        $roleId = $this->roleModel->getIdByName($roleName);
        if (!$roleId) {
            return [];
        }

        $users = $this->userProvider->where('role_id', $roleId)->findAll();
        $insertedIds = [];

        foreach ($users as $user) {
            $id = $this->sendToUser((int)$user['id'], $title, $message, $type, $relatedId);
            if ($id) {
                $insertedIds[] = $id;
            }
        }

        return $insertedIds;
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = $this->notificationModel->where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return false;
        }

        return $this->notificationModel->update($notificationId, ['is_read' => true]);
    }

    public function getUserNotifications(int $userId): array
    {
        return $this->notificationModel->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
