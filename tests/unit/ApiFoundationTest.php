<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Throwable;

/**
 * ApiFoundationTest
 *
 * Verifies CodeIgniter 4 Foundation, Database Connectivity on eskld_db,
 * and /api/v1/health endpoint execution.
 *
 * @internal
 */
final class ApiFoundationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /**
     * Test 1: Application Boot & Core Constants
     */
    public function testApplicationBoot(): void
    {
        $this->assertTrue(defined('APPPATH'), 'APPPATH constant must be defined.');
        $this->assertTrue(defined('SYSTEMPATH'), 'SYSTEMPATH constant must be defined.');
        $this->assertTrue(defined('ENVIRONMENT'), 'ENVIRONMENT constant must be defined.');
    }

    /**
     * Test 2: Database Connectivity to eskld_db
     */
    public function testDatabaseConnection(): void
    {
        try {
            $db = Database::connect();
            $this->assertNotNull($db, 'Database connection object must not be null.');

            $query = $db->query('SELECT 1 AS status, DATABASE() AS db_name');
            $row = $query->getRow();

            $this->assertNotNull($row, 'Query row must exist.');
            $this->assertEquals(1, (int)$row->status, 'Database query SELECT 1 must return 1.');
            $this->assertEquals('eskld_db', $row->db_name, 'Connected database must be eskld_db.');
        } catch (Throwable $e) {
            $this->fail('Database connection to eskld_db failed: ' . $e->getMessage());
        }
    }

    /**
     * Test 3: Health Check API Endpoint (GET /api/v1/health)
     */
    public function testHealthEndpoint(): void
    {
        $result = $this->get('api/v1/health');

        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'application/json; charset=UTF-8');

        $body = json_decode($result->getJSON(), true);

        $this->assertIsArray($body, 'Response must be valid JSON array.');
        $this->assertTrue($body['success'], 'Response success flag must be true.');
        $this->assertEquals(200, $body['statusCode'], 'Response statusCode must be 200.');
        $this->assertEquals('ok', $body['data']['status'], 'Response data status must be ok.');
        $this->assertArrayHasKey('meta', $body, 'Response must have meta key.');
        $this->assertArrayHasKey('timestamp', $body['meta'], 'Response meta must have timestamp.');
    }
}
