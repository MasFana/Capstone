<?php

namespace Tests\Feature\Api\V1;

use App\Models\AppUserProvider;
use App\Models\RoleModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

class SpkBasahTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'tests';
    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedUsers();
        $this->seedOperationalBaseline();
    }

    public function testGenerateIncludesRequestedDateAndNextDateWhenStillSameMonth(): void
    {
        $token = $this->login('dapur');

        $this->createDailyPatient($token, '2026-03-01', 100);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/spk/basah/generate', [
                'service_date' => '2026-03-01',
            ]);

        $response->assertStatus(201);
        $response->assertJSONFragment(['message' => 'SPK basah generated successfully.']);

        $json = json_decode($response->getJSON(), true);
        $this->assertSame(['2026-03-01', '2026-03-02'], $json['data']['target_dates']);
        $this->assertSame(105, $json['data']['estimated_patients']);

        $db = Database::connect();
        $spk = $db->table('spk_calculations')->where('id', (int) $json['data']['id'])->get()->getRowArray();
        $this->assertNotNull($spk);
        $this->assertSame('2026-03-01', $spk['target_date_start']);
        $this->assertSame('2026-03-02', $spk['target_date_end']);
        $this->assertSame('basah', $spk['spk_type']);

        $details = $db->table('spk_recommendations')
            ->where('spk_id', (int) $json['data']['id'])
            ->orderBy('target_date', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertCount(2, $details);
        $this->assertSame('2026-03-01', $details[0]['target_date']);
        $this->assertSame('2026-03-02', $details[1]['target_date']);
        $this->assertSame('210.00', number_format((float) $details[0]['required_qty'], 2, '.', ''));
        $this->assertSame('210.00', number_format((float) $details[1]['required_qty'], 2, '.', ''));
        $this->assertSame('100.00', number_format((float) $details[0]['current_stock_qty'], 2, '.', ''));
        $this->assertSame('110.00', number_format((float) $details[0]['system_recommended_qty'], 2, '.', ''));
        $this->assertSame('210.00', number_format((float) $details[1]['system_recommended_qty'], 2, '.', ''));
    }

    public function testGenerateOnMonthEndIncludesOnlyRequestedDate(): void
    {
        $token = $this->login('dapur');

        $this->createDailyPatient($token, '2026-03-31', 80);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/spk/basah/generate', [
                'service_date' => '2026-03-31',
            ]);

        $response->assertStatus(201);
        $json = json_decode($response->getJSON(), true);

        $this->assertSame(['2026-03-31'], $json['data']['target_dates']);
        $this->assertSame(84, $json['data']['estimated_patients']);

        $db = Database::connect();
        $details = $db->table('spk_recommendations')
            ->where('spk_id', (int) $json['data']['id'])
            ->get()
            ->getResultArray();

        $this->assertCount(1, $details);
        $this->assertSame('2026-03-31', $details[0]['target_date']);
    }

    public function testGenerateFailsAtomicallyWhenDailyPatientMissingAndCreatesNoRows(): void
    {
        $token = $this->login('dapur');
        $db = Database::connect();

        $beforeHeaderCount = $db->table('spk_calculations')->countAllResults();
        $beforeDetailCount = $db->table('spk_recommendations')->countAllResults();

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/spk/basah/generate', [
                'service_date' => '2026-03-01',
            ]);

        $response->assertStatus(400);
        $response->assertJSONFragment(['message' => 'Validation failed.']);

        $afterHeaderCount = $db->table('spk_calculations')->countAllResults();
        $afterDetailCount = $db->table('spk_recommendations')->countAllResults();
        $this->assertSame($beforeHeaderCount, $afterHeaderCount);
        $this->assertSame($beforeDetailCount, $afterDetailCount);
    }

    public function testGenerateFailsAtomicallyWhenRecipeMappingMissingAndCreatesNoRows(): void
    {
        $token = $this->login('dapur');
        $db = Database::connect();

        $this->createDailyPatient($token, '2026-03-01', 100);
        $db->table('dish_compositions')->where('dish_id', 1)->delete();

        $beforeHeaderCount = $db->table('spk_calculations')->countAllResults();
        $beforeDetailCount = $db->table('spk_recommendations')->countAllResults();

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/spk/basah/generate', [
                'service_date' => '2026-03-01',
            ]);

        $response->assertStatus(400);
        $response->assertJSONFragment(['message' => 'Validation failed.']);

        $afterHeaderCount = $db->table('spk_calculations')->countAllResults();
        $afterDetailCount = $db->table('spk_recommendations')->countAllResults();
        $this->assertSame($beforeHeaderCount, $afterHeaderCount);
        $this->assertSame($beforeDetailCount, $afterDetailCount);
    }

    public function testGenerateDoesNotCreateStockTransactions(): void
    {
        $token = $this->login('dapur');
        $db = Database::connect();

        $this->createDailyPatient($token, '2026-03-01', 100);
        $beforeCount = $db->table('stock_transactions')->countAllResults();

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/spk/basah/generate', [
                'service_date' => '2026-03-01',
            ]);

        $response->assertStatus(201);
        $afterCount = $db->table('stock_transactions')->countAllResults();
        $this->assertSame($beforeCount, $afterCount);
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

    protected function seedOperationalBaseline(): void
    {
        $db = Database::connect();

        $db->table('meal_times')->insertBatch([
            ['id' => 1, 'name' => 'Pagi'],
            ['id' => 2, 'name' => 'Siang'],
            ['id' => 3, 'name' => 'Sore'],
        ]);

        $db->table('menus')->insertBatch([
            ['id' => 1, 'name' => 'Paket 1'],
            ['id' => 2, 'name' => 'Paket 2'],
            ['id' => 3, 'name' => 'Paket 3'],
            ['id' => 4, 'name' => 'Paket 4'],
            ['id' => 5, 'name' => 'Paket 5'],
            ['id' => 6, 'name' => 'Paket 6'],
            ['id' => 7, 'name' => 'Paket 7'],
            ['id' => 8, 'name' => 'Paket 8'],
            ['id' => 9, 'name' => 'Paket 9'],
            ['id' => 10, 'name' => 'Paket 10'],
            ['id' => 11, 'name' => 'Paket 11'],
        ]);

        $db->table('item_categories')->insertBatch([
            ['name' => 'BASAH'],
            ['name' => 'KERING'],
            ['name' => 'PENGEMAS'],
        ]);

        $basahCategoryId = (int) $db->table('item_categories')->where('name', 'BASAH')->get()->getRowArray()['id'];

        $db->table('item_units')->insertBatch([
            ['name' => 'gram'],
            ['name' => 'kg'],
        ]);
        $gramUnit = (int) $db->table('item_units')->where('name', 'gram')->get()->getRowArray()['id'];
        $kgUnit   = (int) $db->table('item_units')->where('name', 'kg')->get()->getRowArray()['id'];

        $itemBuilder = $db->table('items');
        $itemBuilder->insert([
            'item_category_id'      => $basahCategoryId,
            'name'                  => 'Ayam Basah',
            'unit_base'             => 'gram',
            'unit_convert'          => 'kg',
            'item_unit_base_id'     => $gramUnit,
            'item_unit_convert_id'  => $kgUnit,
            'conversion_base'       => 1000,
            'is_active'             => true,
            'qty'                   => 100,
        ]);
        $itemId = (int) $db->insertID();

        $db->table('dishes')->insert([
            'id'   => 1,
            'name' => 'Sup Ayam',
        ]);

        $db->table('dish_compositions')->insert([
            'dish_id'          => 1,
            'item_id'          => $itemId,
            'qty_per_patient'  => 2.00,
        ]);

        $db->table('menu_dishes')->insertBatch([
            ['menu_id' => 1, 'meal_time_id' => 2, 'dish_id' => 1],
            ['menu_id' => 2, 'meal_time_id' => 2, 'dish_id' => 1],
            ['menu_id' => 11, 'meal_time_id' => 2, 'dish_id' => 1],
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

    private function createDailyPatient(string $token, string $serviceDate, int $totalPatients): void
    {
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/daily-patients', [
                'service_date'   => $serviceDate,
                'total_patients' => $totalPatients,
            ])
            ->assertStatus(201);
    }
}
