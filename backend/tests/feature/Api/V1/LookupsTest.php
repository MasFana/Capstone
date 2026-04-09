<?php

namespace Tests\Feature\Api\V1;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Shield\Entities\User;
use App\Models\AppUserProvider;
use App\Models\RoleModel;

class LookupsTest extends CIUnitTestCase
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
        $this->seedLookupData();
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
        $roleModel = new RoleModel();
        $userProvider = new AppUserProvider();

        $adminRole = $roleModel->findByName('admin');
        $dapurRole = $roleModel->findByName('dapur');
        $gudangRole = $roleModel->findByName('gudang');

        $adminUser = new User([
            'role_id'   => $adminRole['id'],
            'name'      => 'Admin User',
            'username'  => 'admin',
            'email'     => 'admin@example.com',
            'is_active' => true,
            'active'    => true,
        ]);
        $adminUser->fill(['password' => 'password123']);
        $userProvider->insert($adminUser, true);

        $dapurUser = new User([
            'role_id'   => $dapurRole['id'],
            'name'      => 'Dapur User',
            'username'  => 'dapur',
            'email'     => 'dapur@example.com',
            'is_active' => true,
            'active'    => true,
        ]);
        $dapurUser->fill(['password' => 'password123']);
        $userProvider->insert($dapurUser, true);

        $gudangUser = new User([
            'role_id'   => $gudangRole['id'],
            'name'      => 'Gudang User',
            'username'  => 'gudang',
            'email'     => 'gudang@example.com',
            'is_active' => true,
            'active'    => true,
        ]);
        $gudangUser->fill(['password' => 'password123']);
        $userProvider->insert($gudangUser, true);
    }

    protected function seedLookupData(): void
    {
        $this->db->table('item_categories')->insertBatch([
            ['name' => 'BASAH'],
            ['name' => 'KERING'],
            ['name' => 'PENGEMAS'],
        ]);

        $this->db->table('transaction_types')->insertBatch([
            ['name' => 'IN'],
            ['name' => 'OUT'],
            ['name' => 'RETURN_IN'],
        ]);

        $this->db->table('approval_statuses')->insertBatch([
            ['name' => 'APPROVED'],
            ['name' => 'PENDING'],
            ['name' => 'REJECTED'],
        ]);
    }

    protected function getToken(string $username): string
    {
        $loginResult = $this->withBodyFormat('json')
            ->post('api/v1/auth/login', [
                'username' => $username,
                'password' => 'password123',
            ]);

        $loginJson = json_decode($loginResult->getJSON(), true);
        return $loginJson['access_token'];
    }

    // Item Categories Tests

    public function testItemCategoriesWithoutAuth(): void
    {
        $result = $this->get('api/v1/item-categories');
        $result->assertStatus(401);
    }

    public function testItemCategoriesWithDapurRole(): void
    {
        $token = $this->getToken('dapur');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/item-categories');

        $result->assertStatus(403);
        $result->assertJSONFragment(['message' => 'Insufficient permissions.']);
    }

    public function testItemCategoriesWithAdminRole(): void
    {
        $token = $this->getToken('admin');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/item-categories');

        $result->assertStatus(200);
        
        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $json);
        $this->assertCount(3, $json['data']);
        $this->assertSame('BASAH', $json['data'][0]['name']);
        $this->assertSame('KERING', $json['data'][1]['name']);
        $this->assertSame('PENGEMAS', $json['data'][2]['name']);
    }

    public function testItemCategoriesWithGudangRole(): void
    {
        $token = $this->getToken('gudang');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/item-categories');

        $result->assertStatus(200);
        
        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $json);
        $this->assertCount(3, $json['data']);
    }

    // Transaction Types Tests

    public function testTransactionTypesWithoutAuth(): void
    {
        $result = $this->get('api/v1/transaction-types');
        $result->assertStatus(401);
    }

    public function testTransactionTypesWithDapurRole(): void
    {
        $token = $this->getToken('dapur');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/transaction-types');

        $result->assertStatus(403);
        $result->assertJSONFragment(['message' => 'Insufficient permissions.']);
    }

    public function testTransactionTypesWithAdminRole(): void
    {
        $token = $this->getToken('admin');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/transaction-types');

        $result->assertStatus(200);
        
        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $json);
        $this->assertCount(3, $json['data']);
        $this->assertSame('IN', $json['data'][0]['name']);
        $this->assertSame('OUT', $json['data'][1]['name']);
        $this->assertSame('RETURN_IN', $json['data'][2]['name']);
    }

    public function testTransactionTypesWithGudangRole(): void
    {
        $token = $this->getToken('gudang');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/transaction-types');

        $result->assertStatus(200);
        
        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $json);
        $this->assertCount(3, $json['data']);
    }

    // Approval Statuses Tests

    public function testApprovalStatusesWithoutAuth(): void
    {
        $result = $this->get('api/v1/approval-statuses');
        $result->assertStatus(401);
    }

    public function testApprovalStatusesWithDapurRole(): void
    {
        $token = $this->getToken('dapur');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/approval-statuses');

        $result->assertStatus(403);
        $result->assertJSONFragment(['message' => 'Insufficient permissions.']);
    }

    public function testApprovalStatusesWithAdminRole(): void
    {
        $token = $this->getToken('admin');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/approval-statuses');

        $result->assertStatus(200);
        
        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $json);
        $this->assertCount(3, $json['data']);
        $this->assertSame('APPROVED', $json['data'][0]['name']);
        $this->assertSame('PENDING', $json['data'][1]['name']);
        $this->assertSame('REJECTED', $json['data'][2]['name']);
    }

    public function testApprovalStatusesWithGudangRole(): void
    {
        $token = $this->getToken('gudang');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/approval-statuses');

        $result->assertStatus(200);
        
        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $json);
        $this->assertCount(3, $json['data']);
    }
}
