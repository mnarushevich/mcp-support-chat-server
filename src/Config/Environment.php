<?php

declare(strict_types=1);

namespace App\Config;

class Environment
{
    public function getDBConfig(): array
    {
        return [
            'db_host' => $_ENV['DB_HOST'] ?? 'localhost',
            'db_port' => $_ENV['DB_PORT'] ?? 8911,
            'db_database' => $_ENV['DB_DATABASE'] ?? 'mcp_chat',
            'db_username' => $_ENV['DB_USERNAME'] ?? 'root',
            'db_password' => $_ENV['DB_PASSWORD'] ?? '',
        ];
    }

    public function getMCPConfig(): array
    {
        return [
            'name' =>$_ENV['MCP_SERVER_NAME'] ?? 'Chat Support MCP Server',
            'version' =>$_ENV['MCP_SERVER_VERSION'] ?? '1.0.0',
            'server_host' =>$_ENV['MCP_SERVER_HOST'] ?? '127.0.0.1',
            'server_port' =>$_ENV['MCP_SERVER_PORT'] ?? 8087,
        ];
    }
}

