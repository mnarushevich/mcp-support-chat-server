<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\SystemExitException;
use Throwable;

/**
 * Centralized error handling for the MCP server
 */
class ErrorHandler
{
    /**
     * Handle server errors with consistent formatting
     */
    public static function handle(Throwable $e, string $errorType): void
    {
        // Suppress output during testing
        if (!self::isTestEnvironment()) {
            echo $e->getMessage() . "\n";
            fwrite(STDERR, "[MCP SERVER {$errorType}]\n" . $e . "\n");
        }
        
        throw new SystemExitException($e->getMessage(), $e->getCode(), $e);
    }

    /**
     * Handle configuration errors
     */
    public static function handleConfigurationError(Throwable $e): void
    {
        self::handle($e, 'CONFIGURATION ERROR');
    }

    /**
     * Handle discovery errors
     */
    public static function handleDiscoveryError(Throwable $e): void
    {
        self::handle($e, 'DISCOVERY ERROR');
    }

    /**
     * Handle critical errors
     */
    public static function handleCriticalError(Throwable $e): void
    {
        self::handle($e, 'CRITICAL ERROR');
    }

    /**
     * Check if we're running in a test environment
     */
    private static function isTestEnvironment(): bool
    {
        return defined('PHPUNIT_COMPOSER_INSTALL') || 
               defined('PEST_RUNNING') || 
               str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'pest') ||
               str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'phpunit');
    }
} 