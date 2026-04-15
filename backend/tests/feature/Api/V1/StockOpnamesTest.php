<?php

namespace Tests\Feature\Api\V1;

use App\Models\AppUserProvider;
use App\Models\ItemCategoryModel;
use App\Models\ItemModel;
use App\Models\ItemUnitModel;
use App\Models\RoleModel;
use App\Models\StockOpnameModel;
use App\Models\StockTransactionModel;
use App\Models\ApprovalStatusModel;
use App\Models\TransactionTypeModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

class StockOpnamesTest extends CIUnitTestCase
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
        $this->seedItemCategories();
        $this->seedItemUnits();
        $this->seedItems();
        $this->seedTransactionTypes();
        $this->seedApprovalStatuses();
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

        $users = [
            ['role' => 'admin', 'name' => 'Admin User', 'username' => 'admin', 'email' => 'admin@example.com'],
            ['role' => 'gudang', 'name' => 'Gudang User', 'username' => 'gudang', 'email' => 'gudang@example.com'],
            ['role' => 'dapur', 'name' => 'Dapur User', 'username' => 'dapur', 'email' => 'dapur@example.com'],
        ];

        foreach ($users as $userData) {
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

    protected function seedItemCategories(): void
    {
        $categoryModel = new ItemCategoryModel();
        $categoryModel->insertBatch([
            ['name' => 'BASAH'],
            ['name' => 'KERING'],
            ['name' => 'PENGEMAS'],
        ]);
    }

    protected function seedItemUnits(): void
    {
        $itemUnitModel = new ItemUnitModel();
        $itemUnitModel->insertBatch([
            ['name' => 'gram'],
            ['name' => 'kg'],
            ['name' => 'ml'],
            ['name' => 'liter'],
            ['name' => 'butir'],
            ['name' => 'pack'],
        ]);
    }

    protected function seedItems(): void
    {
        $categoryModel = new ItemCategoryModel();
        $itemUnitModel = new ItemUnitModel();
        $db            = Database::connect();

        $basah  = $categoryModel->where('name', 'BASAH')->first();
        $kering = $categoryModel->where('name', 'KERING')->first();

        $gramId = $itemUnitModel->getIdByName('gram');
        $kgId   = $itemUnitModel->getIdByName('kg');

        $db->table('items')->insertBatch([
            [
                'item_category_id'     => $kering['id'],
                'name'                 => 'Beras',
                'unit_base'            => 'gram',
                'unit_convert'         => 'kg',
                'item_unit_base_id'    => $gramId,
                'item_unit_convert_id' => $kgId,
                'conversion_base'      => 1000,
                'is_active'            => true,
                'qty'                  => 5000,
            ],
            [
                'item_category_id'     => $basah['id'],
                'name'                 => 'Ayam',
                'unit_base'            => 'gram',
                'unit_convert'         => 'kg',
                'item_unit_base_id'    => $gramId,
                'item_unit_convert_id' => $kgId,
                'conversion_base'      => 1000,
                'is_active'            => true,
                'qty'                  => 3000,
            ],
        ]);
    }

    protected function seedTransactionTypes(): void
    {
        $typeModel = new TransactionTypeModel();
        $typeModel->insertBatch([
            ['name' => 'IN'],
            ['name' => 'OUT'],
            ['name' => 'RETURN_IN'],
        ]);
    }

    protected function seedApprovalStatuses(): void
    {
        $statusModel = new ApprovalStatusModel();
        $statusModel->insertBatch([
            ['name' => 'APPROVED'],
            ['name' => 'PENDING'],
            ['name' => 'REJECTED'],
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

        return $json['access_token'];
    }

    public function testStockOpnameHappyPathLifecycle(): void
    {
        $gudangToken = $this->login('gudang');
        $adminToken  = $this->login('admin');

        $itemModel      = new ItemModel();
        $berasBeforeQty = (float) $itemModel->find(1)['qty'];
        $ayamBeforeQty  = (float) $itemModel->find(2)['qty'];

        $createResult = $this->withHeaders(['Authorization' => 'Bearer ' . $gudangToken])
            ->withBodyFormat('json')
            ->post('api/v1/stock-opnames', [
                'opname_date' => '2026-06-20',
                'notes'       => 'Cycle count June.',
                'details'     => [
                    ['item_id' => 1, 'counted_qty' => $berasBeforeQty - 100],
                    ['item_id' => 2, 'counted_qty' => $ayamBeforeQty + 50],
                ],
            ]);

        $createResult->assertStatus(201);
        $createJson = json_decode($createResult->getJSON(), true);
        $opnameId   = (int) $createJson['data']['id'];

        $submitResult = $this->withHeaders(['Authorization' => 'Bearer ' . $gudangToken])
            ->withBodyFormat('json')
            ->post('api/v1/stock-opnames/' . $opnameId . '/submit', []);
        $this->assertNotNull($submitResult);
        $submitResult->assertStatus(200);
        $submitJson = json_decode($submitResult->getJSON(), true);
        $this->assertSame(StockOpnameModel::STATE_SUBMITTED, $submitJson['data']['state']);

        $approveResult = $this->withHeaders(['Authorization' => 'Bearer ' . $adminToken])
            ->withBodyFormat('json')
            ->post('api/v1/stock-opnames/' . $opnameId . '/approve', []);
        $this->assertNotNull($approveResult);
        $approveResult->assertStatus(200);
        $approveJson = json_decode($approveResult->getJSON(), true);
        $this->assertSame(StockOpnameModel::STATE_APPROVED, $approveJson['data']['state']);

        $postResult = $this->withHeaders(['Authorization' => 'Bearer ' . $adminToken])
            ->withBodyFormat('json')
            ->post('api/v1/stock-opnames/' . $opnameId . '/post', []);

        $postResult->assertStatus(200);
        $postJson = json_decode($postResult->getJSON(), true);
        $this->assertSame(StockOpnameModel::STATE_POSTED, $postJson['data']['state']);

        $berasAfterQty = (float) $itemModel->find(1)['qty'];
        $ayamAfterQty  = (float) $itemModel->find(2)['qty'];

        $this->assertSame($berasBeforeQty - 100, $berasAfterQty);
        $this->assertSame($ayamBeforeQty + 50, $ayamAfterQty);

        $stockTransactionModel = new StockTransactionModel();
        $postedTransactions    = $stockTransactionModel
            ->like('reason', 'Stock opname #' . $opnameId . ' posting', 'both')
            ->findAll();

        $this->assertCount(2, $postedTransactions);

        $showResult = $this->withHeaders(['Authorization' => 'Bearer ' . $adminToken])
            ->get('api/v1/stock-opnames/' . $opnameId);
        $showResult->assertStatus(200);
        $showJson = json_decode($showResult->getJSON(), true);
        $this->assertSame(StockOpnameModel::STATE_POSTED, $showJson['data']['header']['state']);
        $this->assertCount(2, $showJson['data']['details']);
    }

    public function testStockOpnameInvalidTransitionApproveDraftRejected(): void
    {
        $gudangToken = $this->login('gudang');
        $adminToken  = $this->login('admin');

        $itemModel      = new ItemModel();
        $berasBeforeQty = (float) $itemModel->find(1)['qty'];

        $createResult = $this->withHeaders(['Authorization' => 'Bearer ' . $gudangToken])
            ->withBodyFormat('json')
            ->post('api/v1/stock-opnames', [
                'opname_date' => '2026-06-21',
                'details'     => [
                    ['item_id' => 1, 'counted_qty' => $berasBeforeQty - 25],
                ],
            ]);

        $createResult->assertStatus(201);
        $opnameId = (int) json_decode($createResult->getJSON(), true)['data']['id'];

        $approveResult = $this->withHeaders(['Authorization' => 'Bearer ' . $adminToken])
            ->withBodyFormat('json')
            ->post('api/v1/stock-opnames/' . $opnameId . '/approve', []);

        $approveResult->assertStatus(400);
        $approveResult->assertJSONFragment(['message' => 'Validation failed.']);

        $json = json_decode($approveResult->getJSON(), true);
        $this->assertArrayHasKey('state', $json['errors']);
        $this->assertStringContainsString('DRAFT', $json['errors']['state']);

        $stockOpnameModel = new StockOpnameModel();
        $opnameRow        = $stockOpnameModel->find($opnameId);
        $this->assertSame(StockOpnameModel::STATE_DRAFT, $opnameRow['state']);

        $berasAfterQty = (float) $itemModel->find(1)['qty'];
        $this->assertSame($berasBeforeQty, $berasAfterQty);
    }

    public function testGudangCannotApproveOrPostStockOpname(): void
    {
        $gudangToken = $this->login('gudang');

        $itemModel = new ItemModel();
        $beforeQty = (float) $itemModel->find(1)['qty'];

        $createResult = $this->withHeaders(['Authorization' => 'Bearer ' . $gudangToken])
            ->withBodyFormat('json')
            ->post('api/v1/stock-opnames', [
                'opname_date' => '2026-06-22',
                'details'     => [
                    ['item_id' => 1, 'counted_qty' => $beforeQty - 10],
                ],
            ]);

        $createResult->assertStatus(201);
        $opnameId = (int) json_decode($createResult->getJSON(), true)['data']['id'];

        $submitResult = $this->withHeaders(['Authorization' => 'Bearer ' . $gudangToken])
            ->withBodyFormat('json')
            ->post('api/v1/stock-opnames/' . $opnameId . '/submit', []);
        $submitResult->assertStatus(200);

        $approveResult = $this->withHeaders(['Authorization' => 'Bearer ' . $gudangToken])
            ->withBodyFormat('json')
            ->post('api/v1/stock-opnames/' . $opnameId . '/approve', []);
        $approveResult->assertStatus(403);

        $postResult = $this->withHeaders(['Authorization' => 'Bearer ' . $gudangToken])
            ->withBodyFormat('json')
            ->post('api/v1/stock-opnames/' . $opnameId . '/post', []);
        $postResult->assertStatus(403);

        $this->assertSame($beforeQty, (float) $itemModel->find(1)['qty']);
    }
}
