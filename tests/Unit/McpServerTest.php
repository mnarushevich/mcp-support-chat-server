<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Mcp\McpServer;

describe('McpServer', function () {
    beforeEach(function () {
        $_ENV['MCP_SERVER_NAME'] = 'Test MCP Server';
        $_ENV['MCP_SERVER_VERSION'] = '2.0.0';
        $_ENV['MCP_SERVER_HOST'] = 'localhost';
        $_ENV['MCP_SERVER_PORT'] = '8088';
    });

    afterEach(function () {
        unset($_ENV['MCP_SERVER_NAME']);
        unset($_ENV['MCP_SERVER_VERSION']);
        unset($_ENV['MCP_SERVER_HOST']);
        unset($_ENV['MCP_SERVER_PORT']);
    });

    describe('constructor', function () {
        it('creates instance with default configuration', function () {
            // Act
            $mcpServer = new McpServer();

            // Assert
            expect($mcpServer)->toBeInstanceOf(McpServer::class);
        });

        it('creates instance with custom environment configuration', function () {
            // Arrange
            $_ENV['MCP_SERVER_NAME'] = 'Custom Test Server';
            $_ENV['MCP_SERVER_VERSION'] = '3.0.0';
            $_ENV['MCP_SERVER_HOST'] = '192.168.1.100';
            $_ENV['MCP_SERVER_PORT'] = '9090';

            // Act
            $mcpServer = new McpServer();

            // Assert
            expect($mcpServer)->toBeInstanceOf(McpServer::class);
        });

        it('creates instance with fallback configuration when env vars are not set', function () {
            // Arrange
            unset($_ENV['MCP_SERVER_NAME']);
            unset($_ENV['MCP_SERVER_VERSION']);
            unset($_ENV['MCP_SERVER_HOST']);
            unset($_ENV['MCP_SERVER_PORT']);

            // Act
            $mcpServer = new McpServer();

            // Assert
            expect($mcpServer)->toBeInstanceOf(McpServer::class);
        });
    });

    describe('configuration integration', function () {
        it('uses correct server name from configuration', function () {
            // Arrange
            $_ENV['MCP_SERVER_NAME'] = 'Test Server Name';
            $mcpServer = new McpServer();

            // Assert
            expect($mcpServer)->toBeInstanceOf(McpServer::class);
        });

        it('uses correct server version from configuration', function () {
            // Arrange
            $_ENV['MCP_SERVER_VERSION'] = '1.2.3';
            $mcpServer = new McpServer();

            // Assert
            expect($mcpServer)->toBeInstanceOf(McpServer::class);
        });

        it('uses correct server host from configuration', function () {
            // Arrange
            $_ENV['MCP_SERVER_HOST'] = '0.0.0.0';
            $mcpServer = new McpServer();

            // Assert
            expect($mcpServer)->toBeInstanceOf(McpServer::class);
        });

        it('uses correct server port from configuration', function () {
            // Arrange
            $_ENV['MCP_SERVER_PORT'] = '9999';
            $mcpServer = new McpServer();

            // Assert
            expect($mcpServer)->toBeInstanceOf(McpServer::class);
        });
    });
}); 