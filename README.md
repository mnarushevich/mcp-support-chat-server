# PHP MCP Chat Bot Server

A PHP MCP (Model Context Protocol) server that provides data from a MySQL database to chat support services.

## Features

- **MCP Tools**: Database query tools for chat support
- **MCP Resources**: Access to user data, chat history, and support tickets
- **MCP Prompts**: Pre-defined prompts for common support scenarios
- **MySQL Integration**: Secure database access with PDO
- **Testing**: Comprehensive PHPUnit test suite

## Requirements

- PHP >= 8.4
- MySQL >= 8.0
- Composer

## Installation

1. Clone the repository:

```bash
git clone <repository-url>
cd mcp-support-chat-server
```

2. Install dependencies:

```bash
composer install

OR if using Laravel Herd

herd composer install
```

3. Copy the environment file and configure your database:

```bash
cp .env.example .env
```

4. Create the database and run migrations:

```bash
docker compose up -d
docker exec -i mcp_db_mysql sh -c 'mysql -uroot mcp_chat' < ./database/schema.sql
```

## Usage

### Running the MCP Server

#### STDIO Transport (Recommended for development)

```bash
php server.php

OR if using Laravel Herd

herd php server.php
```

#### HTTP Transport

```bash
php server.php --transport=http

OR if using Laravel Herd

herd php server.php --transport=http
```

## MCP Tools

### Database Tools

- `get_user_info`: Retrieve user information by ID
- `search_users`: Search users by name or email
- `get_chat_history`: Get chat history for a user
- `get_support_tickets`: Get support tickets with filtering
- `create_support_ticket`: Create a new support ticket

### Resource Tools

- `user://{userId}/profile`: User profile data
- `chat://{userId}/history`: Chat history for a user

### Prompts

- `user_context`: Provide user context for support

## Database Schema

The server expects the following database tables:

- `users`: User information
- `chat_messages`: Chat message history

See `database/schema.sql` for the complete schema.

## Configuration

The server can be configured through environment variables:

- `DB_HOST`: Database host (default: localhost)
- `DB_PORT`: Database port (default: 3306)
- `DB_DATABASE`: Database name
- `DB_USERNAME`: Database username
- `DB_PASSWORD`: Database password
- `MCP_SERVER_NAME`: MCP server name (default: "Chat Support MCP Server")
- `MCP_SERVER_VERSION`: MCP server version (default: "1.0.0")

## Development

### Project Structure

```
├── src/
│   ├── Config/
│   │   └── Environment.php
│   ├── Core/
│   │   ├── Container.php
│   │   └── ErrorHandler.php
│   ├── Exceptions/
│   │   └── SystemExitException.php
│   ├── Factories/
│   │   ├── Builders/
│   │   │   ├── ChatFactoryBuilder.php
│   │   │   └── UserFactoryBuilder.php
│   │   ├── ChatFactory.php
│   │   └── UserFactory.php
│   ├── Mcp/
│   │   ├── McpConfig.php
│   │   ├── McpServer.php
│   │   ├── Prompts/
│   │   │   └── SupportPrompts.php
│   │   ├── Resources/
│   │   │   ├── ChatResources.php
│   │   │   └── UserResources.php
│   │   └── Tools/
│   │       ├── ChatTools.php
│   │       └── UserTools.php
│   └── Models/
│       ├── Chat.php
│       └── User.php
├── tests/
│   ├── Integration/
│   │   ├── Prompts/
│   │   │   └── SupportPromptsTest.php
│   │   ├── Resources/
│   │   │   ├── ChatResourcesTest.php
│   │   │   └── UserResourcesTest.php
│   │   └── Tools/
│   │       ├── ChatToolsTest.php
│   │       └── UserToolsTest.php
│   ├── Unit/
│   │   ├── ErrorHandlerTest.php
│   │   └── McpServerTest.php
│   ├── Pest.php
│   └── TestCase.php
├── database/
│   └── schema.sql
├── docker/
│   └── mysql/
│       └── create-testing-database.sh
├── bootstrap.php
├── composer.json
├── composer.lock
├── docker-compose.yml
├── phpunit.xml
├── rector.php
└── server.php
```

### Adding New MCP Tools

1. Create a new class in `src/Mcp/Tools/`
2. Use the `#[McpTool]` attribute to define the tool
3. Add the tool to the server registration in `server.php`

Example:

```php
#[McpTool(name: 'custom_tool', description: 'A custom tool')]
public function customTool(string $parameter): array
{
    // Tool implementation
    return ['result' => 'success'];
}
```

## License

MIT License - see LICENSE file for details.
