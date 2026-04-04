<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApprovalStatusModel;
use App\Models\AppUserProvider;
use App\Models\AuditLogModel;
use App\Models\ItemCategoryModel;
use App\Models\ItemModel;
use App\Models\RoleModel;
use App\Models\TransactionTypeModel;
use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

class StockTransactionsTest extends CIUnitTestCase
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
        $this->seedTransactionTypes();
        $this->seedApprovalStatuses();
        $this->seedItemCategories();
        $this->seedItems();
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

    protected function seedItemCategories(): void
    {
        $categoryModel = new ItemCategoryModel();
        $categoryModel->insertBatch([
            ['name' => 'BASAH'],
            ['name' => 'KERING'],
            ['name' => 'PENGEMAS'],
        ]);
    }

    protected function seedItems(): void
    {
        $categoryModel = new ItemCategoryModel();
        $db            = Database::connect();

        $basah  = $categoryModel->where('name', 'BASAH')->first();
        $kering = $categoryModel->where('name', 'KERING')->first();

        $db->table('items')->insertBatch([
            [
                'item_category_id' => $kering['id'],
                'name'             => 'Beras',
                'unit_base'        => 'gram',
                'unit_convert'     => 'kg',
                'conversion_base'  => 1000,
                'is_active'        => true,
                'qty'              => 5000,
            ],
            [
                'item_category_id' => $basah['id'],
                'name'             => 'Ayam',
                'unit_base'        => 'gram',
                'unit_convert'     => 'kg',
                'conversion_base'  => 1000,
                'is_active'        => true,
                'qty'              => 3000,
            ],
        ]);
    }

    public function testItemModelDirectQtyUpdateDoesNotChangeStoredQty(): void
    {
        $itemModel = new ItemModel();

        $before = $itemModel->find(1);
        $this->assertNotNull($before);

        try {
            $itemModel->update(1, ['qty' => 9999]);
            $this->fail('Expected DataException when attempting direct qty update via ItemModel.');
        } catch (DataException $exception) {
            $this->assertSame('There is no data to update.', $exception->getMessage());
        }


        $after = $itemModel->find(1);
        $this->assertNotNull($after);
        $this->assertSame((string) $before['qty'], (string) $after['qty']);
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

    public function testListTransactionsWithoutAuth(): void
    {
        $this->get('api/v1/stock-transactions')->assertStatus(401);
    }

    public function testCreateTransactionWithoutAuth(): void
    {
        $this->post('api/v1/stock-transactions')->assertStatus(401);
    }

    public function testListTransactionsAsDapurIsForbidden(): void
    {
        $token = $this->login('dapur');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/stock-transactions');

        $result->assertStatus(403);
    }

    public function testCreateTransactionAsDapurIsForbidden(): void
    {
        $token = $this->login('dapur');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-01',
                'details'          => [
                    ['item_id' => 1, 'qty' => 100],
                ],
            ]);

        $result->assertStatus(403);
    }

    public function testShowTransactionAsDapurIsForbidden(): void
    {
        $adminToken = $this->login('admin');
        $dapurToken = $this->login('dapur');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $createResult = $this->withHeaders(['Authorization' => 'Bearer ' . $adminToken])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-01',
                'details'          => [
                    ['item_id' => 1, 'qty' => 50],
                ],
            ]);

        $json = json_decode($createResult->getJSON(), true);
        $id   = $json['data']['id'];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $dapurToken])
            ->get('api/v1/stock-transactions/' . $id);

        $result->assertStatus(403);
        $result->assertJSONFragment(['message' => 'Insufficient permissions.']);
    }

    public function testDetailsTransactionAsDapurIsForbidden(): void
    {
        $adminToken = $this->login('admin');
        $dapurToken = $this->login('dapur');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $createResult = $this->withHeaders(['Authorization' => 'Bearer ' . $adminToken])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-01',
                'details'          => [
                    ['item_id' => 1, 'qty' => 50],
                ],
            ]);

        $json = json_decode($createResult->getJSON(), true);
        $id   = $json['data']['id'];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $dapurToken])
            ->get('api/v1/stock-transactions/' . $id . '/details');

        $result->assertStatus(403);
        $result->assertJSONFragment(['message' => 'Insufficient permissions.']);
    }

    public function testCreateValidInTransactionIncreasesQty(): void
    {
        $token = $this->login('gudang');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $itemModel     = new ItemModel();
        $itemBefore    = $itemModel->find(1);
        $qtyBefore     = (float) $itemBefore['qty'];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-01',
                'details'          => [
                    ['item_id' => 1, 'qty' => 100.50],
                ],
            ]);

        $result->assertStatus(201);
        $result->assertJSONFragment(['message' => 'Stock transaction created successfully.']);

        $json = json_decode($result->getJSON(), true);
        $this->assertSame(1, $json['data']['approval_status_id']);
        $this->assertFalse($json['data']['is_revision']);

        $itemAfter = $itemModel->find(1);
        $qtyAfter  = (float) $itemAfter['qty'];

        $this->assertSame($qtyBefore + 100.50, $qtyAfter);
    }

    public function testCreateValidOutTransactionDecreasesQty(): void
    {
        $token = $this->login('admin');

        $typeModel = new TransactionTypeModel();
        $outType   = $typeModel->where('name', 'OUT')->first();

        $itemModel  = new ItemModel();
        $itemBefore = $itemModel->find(1);
        $qtyBefore  = (float) $itemBefore['qty'];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $outType['id'],
                'transaction_date' => '2026-04-02',
                'details'          => [
                    ['item_id' => 1, 'qty' => 200],
                ],
            ]);

        $result->assertStatus(201);

        $itemAfter = $itemModel->find(1);
        $qtyAfter  = (float) $itemAfter['qty'];

        $this->assertSame($qtyBefore - 200, $qtyAfter);
    }

    public function testCreateValidReturnInTransactionIncreasesQty(): void
    {
        $token = $this->login('gudang');

        $typeModel  = new TransactionTypeModel();
        $returnType = $typeModel->where('name', 'RETURN_IN')->first();

        $itemModel  = new ItemModel();
        $itemBefore = $itemModel->find(2);
        $qtyBefore  = (float) $itemBefore['qty'];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $returnType['id'],
                'transaction_date' => '2026-04-03',
                'details'          => [
                    ['item_id' => 2, 'qty' => 50],
                ],
            ]);

        $result->assertStatus(201);

        $itemAfter = $itemModel->find(2);
        $qtyAfter  = (float) $itemAfter['qty'];

        $this->assertSame($qtyBefore + 50, $qtyAfter);
    }

    public function testCreateOutTransactionWithInsufficientStockReturnsError(): void
    {
        $token = $this->login('admin');

        $typeModel = new TransactionTypeModel();
        $outType   = $typeModel->where('name', 'OUT')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $outType['id'],
                'transaction_date' => '2026-04-04',
                'details'          => [
                    ['item_id' => 1, 'qty' => 99999],
                ],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionWithDuplicateItemIdReturnsError(): void
    {
        $token = $this->login('gudang');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-05',
                'details'          => [
                    ['item_id' => 1, 'qty' => 10],
                    ['item_id' => 1, 'qty' => 20],
                ],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionWithNonexistentItemReturnsError(): void
    {
        $token = $this->login('admin');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-06',
                'details'          => [
                    ['item_id' => 9999, 'qty' => 10],
                ],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionWithMissingTypeIdReturnsError(): void
    {
        $token = $this->login('gudang');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'transaction_date' => '2026-04-07',
                'details'          => [
                    ['item_id' => 1, 'qty' => 10],
                ],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionWithMissingTransactionDateReturnsError(): void
    {
        $token = $this->login('admin');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id' => $inType['id'],
                'details' => [
                    ['item_id' => 1, 'qty' => 10],
                ],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionWithMissingDetailsReturnsError(): void
    {
        $token = $this->login('gudang');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-08',
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionWithEmptyDetailsReturnsError(): void
    {
        $token = $this->login('admin');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-09',
                'details'          => [],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionRejectsClientSuppliedUserId(): void
    {
        $token = $this->login('gudang');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-10',
                'user_id'          => 999,
                'details'          => [
                    ['item_id' => 1, 'qty' => 10],
                ],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionRejectsClientSuppliedApprovalStatusId(): void
    {
        $token = $this->login('admin');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'            => $inType['id'],
                'transaction_date'   => '2026-04-11',
                'approval_status_id' => 3,
                'details'            => [
                    ['item_id' => 1, 'qty' => 10],
                ],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionRejectsClientSuppliedIsRevision(): void
    {
        $token = $this->login('gudang');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-12',
                'is_revision'      => true,
                'details'          => [
                    ['item_id' => 1, 'qty' => 10],
                ],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionRejectsClientSuppliedParentTransactionId(): void
    {
        $token = $this->login('admin');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'               => $inType['id'],
                'transaction_date'      => '2026-04-13',
                'parent_transaction_id' => 1,
                'details'               => [
                    ['item_id' => 1, 'qty' => 10],
                ],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionRejectsClientSuppliedApprovedBy(): void
    {
        $token = $this->login('gudang');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-14',
                'approved_by'      => 1,
                'details'          => [
                    ['item_id' => 1, 'qty' => 10],
                ],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testSuccessfulCreateWritesAuditLog(): void
    {
        $token = $this->login('admin');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $auditModel   = new AuditLogModel();
        $countBefore  = $auditModel->countAllResults();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-15',
                'details'          => [
                    ['item_id' => 1, 'qty' => 25],
                ],
            ]);

        $result->assertStatus(201);

        $countAfter = $auditModel->countAllResults();

        $this->assertSame($countBefore + 1, $countAfter);

        $latestAudit = $auditModel->orderBy('id', 'DESC')->first();
        $this->assertSame('stock_transaction_create', $latestAudit['action_type']);
        $this->assertSame('stock_transactions', $latestAudit['table_name']);
    }

    public function testListTransactionsReturnsDataMetaLinks(): void
    {
        $token = $this->login('gudang');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-16',
                'details'          => [
                    ['item_id' => 1, 'qty' => 10],
                ],
            ]);

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/stock-transactions');

        $result->assertStatus(200);

        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('meta', $json);
        $this->assertArrayHasKey('links', $json);
        $this->assertSame(1, $json['meta']['page']);
        $this->assertSame(10, $json['meta']['perPage']);
    }

    public function testListTransactionsRejectsUnknownQueryParam(): void
    {
        $token = $this->login('admin');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/stock-transactions?unknown=value');

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testShowExistingTransactionReturnsHeader(): void
    {
        $token = $this->login('gudang');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $createResult = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-17',
                'details'          => [
                    ['item_id' => 1, 'qty' => 15],
                ],
            ]);

        $json = json_decode($createResult->getJSON(), true);
        $id   = $json['data']['id'];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/stock-transactions/' . $id);

        $result->assertStatus(200);

        $showJson = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $showJson);
        $this->assertSame($id, $showJson['data']['id']);
        $this->assertArrayHasKey('type_id', $showJson['data']);
        $this->assertArrayHasKey('transaction_date', $showJson['data']);
        $this->assertArrayNotHasKey('details', $showJson['data']);
    }

    public function testShowMissingTransactionReturnsNotFound(): void
    {
        $token = $this->login('admin');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/stock-transactions/9999');

        $result->assertStatus(404);
        $result->assertJSONFragment(['message' => 'Stock transaction not found.']);
    }

    public function testDetailsExistingTransactionReturnsLineItems(): void
    {
        $token = $this->login('gudang');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $createResult = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-18',
                'details'          => [
                    ['item_id' => 1, 'qty' => 20],
                    ['item_id' => 2, 'qty' => 30],
                ],
            ]);

        $json = json_decode($createResult->getJSON(), true);
        $id   = $json['data']['id'];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/stock-transactions/' . $id . '/details');

        $result->assertStatus(200);

        $detailsJson = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $detailsJson);
        $this->assertCount(2, $detailsJson['data']);
        $this->assertArrayHasKey('item_id', $detailsJson['data'][0]);
        $this->assertArrayHasKey('qty', $detailsJson['data'][0]);
    }

    public function testDetailsMissingTransactionReturnsNotFound(): void
    {
        $token = $this->login('admin');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/stock-transactions/9999/details');

        $result->assertStatus(404);
        $result->assertJSONFragment(['message' => 'Stock transaction not found.']);
    }

    public function testItemMasterStillRejectsDirectQtyWrites(): void
    {
        $token = $this->login('admin');

        $categoryModel = new ItemCategoryModel();
        $category      = $categoryModel->where('name', 'KERING')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/items', [
                'name'             => 'Test Item',
                'item_category_id' => $category['id'],
                'unit_base'        => 'gram',
                'unit_convert'     => 'kg',
                'conversion_base'  => 1000,
                'qty'              => 500,
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionRejectsUnknownTopLevelField(): void
    {
        $token = $this->login('gudang');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-20',
                'unknown_field'    => 'some value',
                'details'          => [
                    ['item_id' => 1, 'qty' => 10],
                ],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionRejectsUnknownDetailField(): void
    {
        $token = $this->login('admin');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-21',
                'details'          => [
                    ['item_id' => 1, 'qty' => 10, 'extra_field' => 'not allowed'],
                ],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionRejectsNonObjectDetailEntry(): void
    {
        $token = $this->login('admin');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-21',
                'details'          => ['invalid-detail-entry'],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionRejectsInvalidTransactionDate(): void
    {
        $token = $this->login('gudang');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => 'not-a-date',
                'details'          => [
                    ['item_id' => 1, 'qty' => 10],
                ],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testCreateTransactionRejectsInvalidSpkId(): void
    {
        $token = $this->login('admin');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-22',
                'spk_id'           => -5,
                'details'          => [
                    ['item_id' => 1, 'qty' => 10],
                ],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testListTransactionsRejectsInvalidPage(): void
    {
        $token = $this->login('gudang');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/stock-transactions?page=0');

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testListTransactionsRejectsInvalidPerPage(): void
    {
        $token = $this->login('admin');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/stock-transactions?perPage=200');

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }

    public function testListTransactionsReturnsNewestFirst(): void
    {
        $token = $this->login('gudang');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();

        // Create three transactions with different dates
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-01',
                'details'          => [
                    ['item_id' => 1, 'qty' => 10],
                ],
            ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-03',
                'details'          => [
                    ['item_id' => 1, 'qty' => 10],
                ],
            ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-02',
                'details'          => [
                    ['item_id' => 1, 'qty' => 10],
                ],
            ]);

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/stock-transactions');

        $result->assertStatus(200);

        $json = json_decode($result->getJSON(), true);
        $this->assertSame('2026-04-03', $json['data'][0]['transaction_date']);
        $this->assertSame('2026-04-02', $json['data'][1]['transaction_date']);
        $this->assertSame('2026-04-01', $json['data'][2]['transaction_date']);
    }

    public function testMultipleTransactionsMutateQtyCorrectly(): void
    {
        $token = $this->login('admin');

        $typeModel = new TransactionTypeModel();
        $inType    = $typeModel->where('name', 'IN')->first();
        $outType   = $typeModel->where('name', 'OUT')->first();

        $itemModel  = new ItemModel();
        $itemBefore = $itemModel->find(1);
        $qtyStart   = (float) $itemBefore['qty'];

        // IN +100
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-23',
                'details'          => [
                    ['item_id' => 1, 'qty' => 100],
                ],
            ])->assertStatus(201);

        $item1 = $itemModel->find(1);
        $this->assertSame($qtyStart + 100, (float) $item1['qty']);

        // OUT -50
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $outType['id'],
                'transaction_date' => '2026-04-24',
                'details'          => [
                    ['item_id' => 1, 'qty' => 50],
                ],
            ])->assertStatus(201);

        $item2 = $itemModel->find(1);
        $this->assertSame($qtyStart + 100 - 50, (float) $item2['qty']);

        // IN +25
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $inType['id'],
                'transaction_date' => '2026-04-25',
                'details'          => [
                    ['item_id' => 1, 'qty' => 25],
                ],
            ])->assertStatus(201);

        $itemFinal = $itemModel->find(1);
        $this->assertSame($qtyStart + 100 - 50 + 25, (float) $itemFinal['qty']);
    }

    public function testFailedOutTransactionDoesNotMutateQty(): void
    {
        $token = $this->login('admin');

        $typeModel = new TransactionTypeModel();
        $outType   = $typeModel->where('name', 'OUT')->first();

        $itemModel  = new ItemModel();
        $itemBefore = $itemModel->find(1);
        $qtyBefore  = (float) $itemBefore['qty'];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $outType['id'],
                'transaction_date' => '2026-04-25',
                'details'          => [
                    ['item_id' => 1, 'qty' => $qtyBefore + 1],
                ],
            ]);

        $result->assertStatus(400);

        $itemAfter = $itemModel->find(1);
        $this->assertSame($qtyBefore, (float) $itemAfter['qty']);
    }

    public function testCreateTransactionRejectsUnsupportedTransactionTypeName(): void
    {
        $token = $this->login('admin');

        // Create a transaction type that exists in DB but is not in SUPPORTED_TRANSACTION_TYPES
        $typeModel = new TransactionTypeModel();
        $typeModel->insert(['name' => 'UNSUPPORTED_TYPE']);
        $unsupportedType = $typeModel->where('name', 'UNSUPPORTED_TYPE')->first();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/stock-transactions', [
                'type_id'          => $unsupportedType['id'],
                'transaction_date' => '2026-04-26',
                'details'          => [
                    ['item_id' => 1, 'qty' => 10],
                ],
            ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Validation failed.']);
    }
}
