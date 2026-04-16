<?php

namespace Tests\Database;

use App\Database\Seeds\TestSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

class DefaultBaselineCoverageTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';
    protected $seed        = TestSeeder::class;

    public function testDishesTableIsPopulated(): void
    {
        $count = $this->db->table('dishes')->countAllResults();

        $this->assertSame(33, $count, 'dishes table should contain the full 33-row default baseline');
    }

    public function testDishCompositionsTableIsPopulated(): void
    {
        $count = $this->db->table('dish_compositions')->countAllResults();

        $this->assertSame(33, $count, 'dish_compositions table should contain one composition per seeded dish');
    }

    public function testMenuDishesTableIsPopulated(): void
    {
        $count = $this->db->table('menu_dishes')->countAllResults();

        $this->assertSame(33, $count, 'menu_dishes table should contain all 11 menus × 3 meal-time slots');
    }

    public function testMenusAndMealTimesMatchDocumentedBaseline(): void
    {
        $menuCount = $this->db->table('menus')->countAllResults();
        $this->assertSame(11, $menuCount, 'menus table should contain Paket 1 through Paket 11');

        $mealTimes = $this->db->table('meal_times')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertSame([1, 2, 3], array_map(static fn (array $row): int => (int) $row['id'], $mealTimes));
        $this->assertSame(['Pagi', 'Siang', 'Sore'], array_map(static fn (array $row): string => $row['name'], $mealTimes));
    }

    public function testEachSeededDishHasAtLeastOneComposition(): void
    {
        $dishes = $this->db->table('dishes')->get()->getResultArray();

        foreach ($dishes as $dish) {
            $compositionCount = $this->db->table('dish_compositions')
                ->where('dish_id', $dish['id'])
                ->countAllResults();

            $this->assertGreaterThan(
                0,
                $compositionCount,
                "Dish '{$dish['name']}' (id={$dish['id']}) has no composition"
            );
        }
    }

    public function testEachMenuOneToElevenHasAllThreeMealTimeSlots(): void
    {
        $mealTimeCount = $this->db->table('meal_times')->countAllResults();

        for ($menuId = 1; $menuId <= 11; $menuId++) {
            $slotCount = $this->db->table('menu_dishes')
                ->where('menu_id', $menuId)
                ->countAllResults();

            $this->assertSame(
                $mealTimeCount,
                $slotCount,
                "Menu id={$menuId} should have {$mealTimeCount} meal-time slots, got {$slotCount}"
            );
        }
    }

    public function testMenuSchedulesIsPopulated(): void
    {
        $count = $this->db->table('menu_schedules')->countAllResults();

        $this->assertGreaterThan(0, $count, 'menu_schedules should contain deterministic baseline overrides');
    }

    public function testDailyPatientsIsPopulated(): void
    {
        $count = $this->db->table('daily_patients')->countAllResults();

        $this->assertGreaterThan(0, $count, 'daily_patients should contain deterministic baseline rows');
    }

    public function testStockTransactionsIsPopulated(): void
    {
        $count = $this->db->table('stock_transactions')->countAllResults();

        $this->assertGreaterThan(0, $count, 'stock_transactions should contain lifecycle baseline rows');
    }

    public function testStockTransactionDetailsIsPopulated(): void
    {
        $count = $this->db->table('stock_transaction_details')->countAllResults();

        $this->assertGreaterThan(0, $count, 'stock_transaction_details should contain lifecycle baseline rows');
    }

    public function testSpkCalculationsIsPopulated(): void
    {
        $count = $this->db->table('spk_calculations')->countAllResults();

        $this->assertGreaterThan(0, $count, 'spk_calculations should contain versioned baseline rows');
    }

    public function testSpkRecommendationsIsPopulated(): void
    {
        $count = $this->db->table('spk_recommendations')->countAllResults();

        $this->assertGreaterThan(0, $count, 'spk_recommendations should contain baseline rows');
    }

    public function testStockOpnameLifecycleStatesAreSeeded(): void
    {
        $states = $this->db->table('stock_opnames')
            ->select('state')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $stateNames = array_values(array_unique(array_map(static fn (array $row): string => (string) $row['state'], $states)));

        $this->assertContains('DRAFT', $stateNames);
        $this->assertContains('SUBMITTED', $stateNames);
        $this->assertContains('APPROVED', $stateNames);
        $this->assertContains('REJECTED', $stateNames);
        $this->assertContains('POSTED', $stateNames);
    }

    public function testStockTransactionLifecycleRowsAreSeeded(): void
    {
        $rows = $this->db->table('stock_transactions')
            ->select('is_revision, reason, approval_status_id')
            ->get()
            ->getResultArray();

        $this->assertNotEmpty($rows);

        $hasRevision = false;
        $hasDirectCorrection = false;

        foreach ($rows as $row) {
            if ((int) $row['is_revision'] === 1) {
                $hasRevision = true;
            }

            if (is_string($row['reason']) && str_contains($row['reason'], 'direct correction')) {
                $hasDirectCorrection = true;
            }
        }

        $this->assertTrue($hasRevision, 'stock_transactions should include revision lifecycle samples');
        $this->assertTrue($hasDirectCorrection, 'stock_transactions should include direct correction baseline sample');
    }

    public function testAuditLogsIsEmpty(): void
    {
        $count = $this->db->table('audit_logs')->countAllResults();

        $this->assertSame(0, $count, 'audit_logs must stay empty in the default baseline');
    }
}
