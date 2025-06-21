<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Config\Environment;
use PhpMcp\Server\Exception\ConfigurationException;
use PhpMcp\Server\Exception\DiscoveryException;
use PhpMcp\Server\Server;
use PhpMcp\Server\Transports\HttpServerTransport;
use PhpMcp\Server\Transports\StdioServerTransport;
use Throwable;

class McpServer
{
    private readonly McpConfig $config;

    public function __construct() {
        $this->config = new McpConfig(new Environment()->getMCPConfig());
    }

    /**
     * @throws ConfigurationException
     * @throws Throwable
     * @throws DiscoveryException
     */
    public function listen(): void
    {
        $server = Server::make()
            ->withServerInfo($this->config->getName(), $this->config->getVersion())
            ->build();
        $server->discover(basePath: __DIR__);

        $transportType = 'stdio';

        if (isset($argv)) {
            foreach ($argv as $arg) {
                if ($arg === '--transport=http') {
                    $transportType = 'http';
                    break;
                }
            }
        }

        if ($transportType === 'http') {
            $transport = new HttpServerTransport(
                host: $this->config->getServerHost(),
                port: $this->config->getServerPort(),
                mcpPathPrefix: 'mcp'
            );
        } else {
            $transport = new StdioServerTransport();
        }
        
        $server->listen($transport);
    }
}
