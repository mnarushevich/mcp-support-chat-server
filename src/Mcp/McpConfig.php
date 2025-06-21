<?php

declare(strict_types=1);

namespace App\Mcp;

readonly class McpConfig
{
    public string $name;
    
    public string $version;
    
    public string $serverHost;
    
    public int $serverPort;

    public function __construct(array $config)
    {
        $this->name = $config['name'];
        $this->version = $config['version'];
        $this->serverHost = $config['server_host'];
        $this->serverPort = (int)$config['server_port'];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getServerHost(): string
    {
        return $this->serverHost;
    }

    public function getServerPort(): int
    {
        return $this->serverPort;
    }
}
