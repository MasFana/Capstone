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

    public function testMenuSchedulesIsEmpty(): void
    {
        $count = $this->db->table('menu_schedules')->countAllResults();

        $this->assertSame(0, $count, 'menu_schedules must stay empty in the default baseline');
    }

    public function testDailyPatientsIsEmpty(): void
    {
        $count = $this->db->table('daily_patients')->countAllResults();

        $this->assertSame(0, $count, 'daily_patients must stay empty in the default baseline');
    }

    public function testStockTransactionsIsEmpty(): void
    {
        $count = $this->db->table('stock_transactions')->countAllResults();

        $this->assertSame(0, $count, 'stock_transactions must stay empty in the default baseline');
    }

    public function testStockTransactionDetailsIsEmpty(): void
    {
        $count = $this->db->table('stock_transaction_details')->countAllResults();

        $this->assertSame(0, $count, 'stock_transaction_details must stay empty in the default baseline');
    }

    public function testSpkCalculationsIsEmpty(): void
    {
        $count = $this->db->table('spk_calculations')->countAllResults();

        $this->assertSame(0, $count, 'spk_calculations must stay empty in the default baseline');
    }

    public function testSpkRecommendationsIsEmpty(): void
    {
        $count = $this->db->table('spk_recommendations')->countAllResults();

        $this->assertSame(0, $count, 'spk_recommendations must stay empty in the default baseline');
    }

    public function testAuditLogsIsEmpty(): void
    {
        $count = $this->db->table('audit_logs')->countAllResults();

        $this->assertSame(0, $count, 'audit_logs must stay empty in the default baseline');
    }
}
