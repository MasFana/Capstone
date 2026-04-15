<?php

namespace Tests\Feature\Api\V1;

use App\Models\AppUserProvider;
use App\Models\RoleModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

class MenuDishesTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedUsers();
        $this->seedMealTimes();
        $this->seedMenus();
        $this->seedDishes();
    }

    protected function seedRoles(): void
    {
        $roleModel = new RoleModel();
        $roleModel->insertBatch([
            ['name' => 'admin'],
            ['name' => 'dapur'],
            ['name' => 'gudang'],
        ]);
    }

    protected function seedUsers(): void
    {
        $roleModel    = new RoleModel();
        $userProvider = new AppUserProvider();

        foreach ([
            ['role' => 'admin', 'name' => 'Admin User', 'username' => 'admin', 'email' => 'admin@example.com'],
            ['role' => 'dapur', 'name' => 'Dapur User', 'username' => 'dapur', 'email' => 'dapur@example.com'],
            ['role' => 'gudang', 'name' => 'Gudang User', 'username' => 'gudang', 'email' => 'gudang@example.com'],
        ] as $userData) {
            $role = $roleModel->findByName($userData['role']);

            $user = new User([
                'role_id'   => $role['id'],
                'name'      => $userData['name'],
                'username'  => $userData['username'],
                'email'     => $userData['email'],
                'is_active' => true,
                'active'    => true,
            ]);
            $user->fill(['password' => 'password123']);
            $userProvider->insert($user, true);
        }
    }

    protected function seedMealTimes(): void
    {
        $db = Database::connect();

        $db->table('meal_times')->insertBatch([
            ['id' => 1, 'name' => 'Pagi'],
            ['id' => 2, 'name' => 'Siang'],
            ['id' => 3, 'name' => 'Sore'],
        ]);
    }

    protected function seedMenus(): void
    {
        $db = Database::connect();

        $rows = [];
        for ($id = 1; $id <= 11; $id++) {
            $rows[] = ['id' => $id, 'name' => 'Paket ' . $id];
        }

        $db->table('menus')->insertBatch($rows);
    }

    protected function seedDishes(): void
    {
        $db = Database::connect();

        $db->table('dishes')->insertBatch([
            ['name' => 'Bubur Ayam'],
            ['name' => 'Nasi Tim'],
            ['name' => 'Sup Sayur'],
        ]);
    }

    protected function login(string $username): string
    {
        $result = $this->withBodyFormat('json')
            ->post('api/v1/auth/login', [
                'username' => $username,
                'password' => 'password123',
            ]);

        $json = json_decode($result->getJSON(), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('access_token', $json);

        return $json['access_token'];
    }

    public function testSlotAssignmentHappyPathForPagiSiangSore(): void
    {
        $token = $this->login('dapur');

        $firstAssignResult = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/menu-dishes', [
                'menu_id'      => 1,
                'meal_time_id' => 1,
                'dish_id'      => 1,
            ]);

        $firstAssignResult->assertStatus(201);
        $firstAssignResult->assertJSONFragment(['message' => 'Menu slot assigned successfully.']);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/menu-dishes', [
                'menu_id'      => 1,
                'meal_time_id' => 2,
                'dish_id'      => 2,
            ])
            ->assertStatus(201);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/menu-dishes', [
                'menu_id'      => 1,
                'meal_time_id' => 3,
                'dish_id'      => 3,
            ])
            ->assertStatus(201);

        $listResult = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/menu-dishes');

        $listResult->assertStatus(200);

        $json = json_decode($listResult->getJSON(), true);
        $this->assertCount(3, $json['data']);
        $this->assertSame('Pagi', $json['data'][0]['meal_time']['name']);
        $this->assertSame('Siang', $json['data'][1]['meal_time']['name']);
        $this->assertSame('Sore', $json['data'][2]['meal_time']['name']);
    }

    public function testDuplicateSlotRejectedForSameMenuAndMealTime(): void
    {
        $token = $this->login('admin');

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/menu-dishes', [
                'menu_id'      => 2,
                'meal_time_id' => 1,
                'dish_id'      => 1,
            ])
            ->assertStatus(201);

        $duplicateResult = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/menu-dishes', [
                'menu_id'      => 2,
                'meal_time_id' => 1,
                'dish_id'      => 2,
            ]);

        $duplicateResult->assertStatus(400);
        $json = json_decode($duplicateResult->getJSON(), true);
        $this->assertSame('Validation failed.', $json['message']);
        $this->assertArrayHasKey('menu_id,meal_time_id', $json['errors']);
    }
}
