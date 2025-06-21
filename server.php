<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Core\ErrorHandler;
use App\Mcp\McpServer;
use PhpMcp\Server\Exception\ConfigurationException;
use PhpMcp\Server\Exception\DiscoveryException;

try {
    $server = new McpServer();
    $server->listen();
    exit(0);
} catch (ConfigurationException $e) {
    ErrorHandler::handleConfigurationError($e);
} catch (DiscoveryException $e) {
    ErrorHandler::handleDiscoveryError($e);
} catch (Throwable $e) {
    ErrorHandler::handleCriticalError($e);
}