<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Include the bootstrap file to set up the database connection
        require_once __DIR__ . '/../bootstrap.php';
    }

    protected function tearDown(): void
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();
        $db->statement('SET FOREIGN_KEY_CHECKS=0;');
        $db->table('chat_messages')->truncate();
        $db->table('users')->truncate();
        $db->statement('SET FOREIGN_KEY_CHECKS=1;');

        parent::tearDown();
    }
} 