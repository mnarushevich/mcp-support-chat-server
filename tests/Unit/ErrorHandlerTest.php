<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Core\ErrorHandler;
use App\Exceptions\SystemExitException;
use PhpMcp\Server\Exception\ConfigurationException;
use PhpMcp\Server\Exception\DiscoveryException;

describe('ErrorHandler', function () {
    describe('handleConfigurationError', function () {
        it('handles configuration exceptions', function () {
            $exception = new ConfigurationException('Config error');

            expect(function () use ($exception) {
                ErrorHandler::handleConfigurationError($exception);
            })->toThrow(SystemExitException::class);
        });
    });

    describe('handleDiscoveryError', function () {
        it('handles discovery exceptions', function () {
            $exception = new DiscoveryException('Discovery error');

            expect(function () use ($exception) {
                ErrorHandler::handleDiscoveryError($exception);
            })->toThrow(SystemExitException::class);
        });
    });

    describe('handleCriticalError', function () {
        it('handles critical exceptions', function () {
            $exception = new \Exception('Critical error');

            expect(function () use ($exception) {
                ErrorHandler::handleCriticalError($exception);
            })->toThrow(SystemExitException::class);
        });
    });
});
