<?php

namespace Tests\Feature\Api\V1;

use CodeIgniter\Test\CIUnitTestCase;

class DailyPatientsContractTest extends CIUnitTestCase
{
    private function routesSource(): string
    {
        $routesPath = APPPATH . 'Config/Routes.php';
        $this->assertFileExists($routesPath);

        $contents = (string) file_get_contents($routesPath);
        $this->assertNotSame('', $contents);

        return $contents;
    }

    public function testDailyPatientsRouteFamilyContainsListCreateAndDetailEndpoints(): void
    {
        $routes = $this->routesSource();

        $this->assertStringContainsString('$routes->get("daily-patients", "DailyPatients::index")', $routes);
        $this->assertStringContainsString('$routes->post("daily-patients", "DailyPatients::create")', $routes);
        $this->assertStringContainsString('$routes->get("daily-patients/(:num)", \'DailyPatients::show/$1\')', $routes);
    }

    public function testDailyPatientsCorsOptionsAreExplicitlyDeclared(): void
    {
        $routes = $this->routesSource();

        $this->assertStringContainsString('"daily-patients",', $routes);
        $this->assertStringContainsString('"daily-patients/(:num)",', $routes);
    }

    public function testDailyPatientsFamilyIsDistinctFromMenuCalendarPath(): void
    {
        $routes = $this->routesSource();

        $this->assertStringNotContainsString('"menu-calendar/daily-patients"', $routes);
        $this->assertStringContainsString('$routes->get("menu-calendar", "MenuSchedules::calendarProjection")', $routes);
    }
}
