<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_200_saat_database_terhubung(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertExactJson(['status' => 'ok', 'database' => 'up']);
    }

    public function test_health_503_saat_database_tidak_terhubung(): void
    {
        $koneksi = config('database.default');
        config(["database.connections.{$koneksi}.port" => 1]);
        DB::purge($koneksi);

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertExactJson(['status' => 'error', 'database' => 'down']);
    }
}
