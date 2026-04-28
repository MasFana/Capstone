<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Services\NotificationService;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\NotificationModel;

class NotificationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = 'App';

    public function testNotificationService()
    {
        $roleModel = new RoleModel();
        $userModel = new UserModel();

        $roleId = $roleModel->insert(['name' => 'testrole', 'description' => 'desc'], true);
        $userId = $userModel->insert([
            'role_id' => $roleId,
            'name' => 'test',
            'username' => 'test',
            'email' => 'test@test.com',
            'password' => 'secret123',
            'is_active' => true
        ], true);

        $service = new NotificationService();
        $id = $service->sendToUser($userId, 'Title', 'Message', 'INFO');
        if (!$id) {
            $notifModel = new NotificationModel();
            print_r($notifModel->errors());
        }

        $this->assertIsInt($id);

        $notifs = $service->getUserNotifications($userId);
        $this->assertCount(1, $notifs);

        $notifModel = new NotificationModel();
        $this->assertEquals('Title', $notifModel->find($id)['title']);

        file_put_contents('../.sisyphus/evidence/task-1-schema.txt', "Notification created with ID: {$id}\n", FILE_APPEND);
    }
}
