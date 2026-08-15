<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\DatabaseTruncation;

/**
 * For tests that assert on product search.
 *
 * RefreshDatabase wraps each test in a transaction, and InnoDB only updates a
 * FULLTEXT index at COMMIT — a product created inside that transaction is
 * invisible to MATCH, so every search assertion would find nothing. These tests
 * therefore truncate instead of rolling back, which commits.
 *
 * The tearDown matters as much as the trait: truncation leaves its rows
 * committed, and the RefreshDatabase classes that run afterwards assume the
 * clean, translations-only database the suite started with. Without this they
 * hit unique-constraint violations on data this class left behind.
 * truncateDatabaseTables() also re-runs the seeder, so it restores exactly that
 * baseline rather than an empty schema.
 */
trait CommitsToDatabase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        $this->truncateDatabaseTables();

        parent::tearDown();
    }
}
