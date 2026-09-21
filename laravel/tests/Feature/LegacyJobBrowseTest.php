<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyJobBrowseTest extends TestCase
{
    public function test_migration_status_reads_legacy_database(): void
    {
        $this->get('/migration-status')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('mode', 'legacy-database-read-only');
    }

    public function test_job_listing_renders_from_legacy_database(): void
    {
        $this->get('/viec-lam')
            ->assertOk()
            ->assertSee('Việc làm Part-time dành cho sinh viên')
            ->assertSee('Bản Laravel thử nghiệm');
    }
}
