<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class ReadOnlyDatabaseGuardTest extends TestCase
{
    public function test_mutating_queries_are_blocked_before_execution(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Legacy database is configured as read-only');

        DB::update('update users set name = name where 1 = 0');
    }
}
