<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Capsule\Manager as Capsule;
use PhpMcp\Server\Attributes\McpTool;

/**
 * Chat management tools for MCP server
 */
readonly class ChatTools
{
    private const array VALID_SENDER_TYPES = ['user', 'agent', 'bot'];

    private const int   MIN_SEARCH_QUERY_LENGTH = 2;

    /**
     * Get chat history for a user
     *
     * @param int $userId The user ID to retrieve chat history for
     * @param int $limit  Maximum number of messages to return
     * @param int $offset Number of messages to skip
     *
     * @return array<string, mixed> Chat history or error response
     */
    #[McpTool(
        name: 'get_chat_history',
        description: 'Get chat message history for a specific user'
    )]
    public function getChatHistory(int $userId, int $limit = 50, int $offset = 0): array
    {
        $user = User::find($userId);

        if ($user === null) {
            return $this->createErrorResponse('User not found');
        }

        $messages = $user->chats()
            ->orderByDesc('timestamp')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return [
            'success'  => true,
            'messages' => $messages->toArray(),
            'count'    => $messages->count(),
            'user_id'  => $userId,
            'limit'    => $limit,
            'offset'   => $offset,
        ];
    }

    /**
     * Get recent messages for a user
     *
     * @param int $userId The user ID to retrieve recent messages for
     * @param int $limit  Maximum number of messages to return
     *
     * @return array<string, mixed> Recent messages or error response
     */
    #[McpTool(
        name: 'get_recent_messages',
        description: 'Get recent chat messages for a user'
    )]
    public function getRecentMessages(int $userId, int $limit = 10): array
    {
        try {
            $messages = User::findOrFail($userId)
                ->chats()
                ->limit($limit)
                ->get();

            return [
                'success'  => true,
                'messages' => $messages->toArray(),
                'count'    => $messages->count(),
                'user_id'  => $userId,
            ];
        } catch (ModelNotFoundException) {
            return $this->createErrorResponse(
                'User not found',
                ['user_id' => $userId]
            );
        }
    }

    /**
     * Get chat session history
     *
     * @param string $sessionId The session ID to retrieve messages for
     * @param int    $limit     Maximum number of messages to return
     *
     * @return array<string, mixed> Session history
     */
    #[McpTool(
        name: 'get_session_history',
        description: 'Get chat messages for a specific session'
    )]
    public function getSessionHistory(string $sessionId, int $limit = 50): array
    {
        $messages = Chat::where('session_id', $sessionId)
            ->limit($limit)
            ->get();

        return [
            'success'    => true,
            'messages'   => $messages->toArray(),
            'count'      => $messages->count(),
            'session_id' => $sessionId
        ];
    }

    /**
     * Add a new chat message
     *
     * @param int         $userId     The user ID
     * @param string      $message    The message content
     * @param string      $senderType The type of sender (user, agent, bot)
     * @param string|null $sessionId  The session ID (optional)
     *
     * @return array<string, mixed> Created message or error response
     */
    #[McpTool(
        name: 'add_chat_message',
        description: 'Add a new chat message to the database'
    )]
    public function addChatMessage(
        int $userId,
        string $message,
        string $senderType,
        ?string $sessionId = null
    ): array {
        if (!$this->isValidSenderType($senderType)) {
            return $this->createErrorResponse(
                'Invalid sender type. Must be user, agent, or bot'
            );
        }

        if (!$this->isValidMessage($message)) {
            return $this->createErrorResponse('Message cannot be empty');
        }

        try {
            $savedMessage = Chat::create([
                'user_id'     => $userId,
                'message'     => $message,
                'sender_type' => $senderType,
                'session_id'  => $sessionId,
            ]);

            return [
                'success' => true,
                'message' => $savedMessage->toArray(),
            ];
        } catch (\Exception $exception) {
            return $this->createErrorResponse(
                'Failed to add message: ' . $exception->getMessage()
            );
        }
    }

    /**
     * Search chat messages
     *
     * @param int    $userId The user ID to search messages for
     * @param string $query  The search query
     * @param int    $limit  Maximum number of results to return
     *
     * @return array<string, mixed> Search results or error response
     */
    #[McpTool(
        name: 'search_chat_messages',
        description: 'Search chat messages for a user'
    )]
    public function searchChatMessages(int $userId, string $query, int $limit = 20): array
    {
        if (!$this->isValidSearchQuery($query)) {
            return $this->createErrorResponse(
                'Search query must be at least ' . self::MIN_SEARCH_QUERY_LENGTH . ' characters long'
            );
        }

        try {
            $messages = User::findOrFail($userId)
                ->chats()
                ->where('message', 'like', sprintf('%%%s%%', $query))
                ->limit($limit)
                ->get();

            return [
                'success'  => true,
                'messages' => $messages->toArray(),
                'count'    => $messages->count(),
                'user_id'  => $userId,
                'query'    => $query
            ];
        } catch (ModelNotFoundException) {
            return $this->createErrorResponse(
                'User not found',
                ['user_id' => $userId]
            );
        }
    }

    /**
     * Get active chat sessions for a user
     *
     * @param int $userId The user ID to get sessions for
     *
     * @return array<string, mixed> Active sessions or error response
     */
    #[McpTool(
        name: 'get_active_sessions',
        description: 'Get active chat sessions for a user'
    )]
    public function getActiveSessions(int $userId): array
    {
        try {
            $sessions = User::findOrFail($userId)
                ->chats()
                ->select('session_id', Capsule::raw('MAX(timestamp) as last_message_time'))
                ->whereNotNull('session_id')
                ->groupBy('session_id')
                ->orderByDesc('last_message_time')
                ->get();

            return [
                'success'  => true,
                'sessions' => $sessions->toArray(),
                'count'    => $sessions->count(),
                'user_id'  => $userId
            ];
        } catch (ModelNotFoundException) {
            return $this->createErrorResponse(
                'User not found',
                ['user_id' => $userId]
            );
        }
    }

    /**
     * Get message count for a user
     *
     * @param int $userId The user ID to get message count for
     *
     * @return array<string, mixed> Message count
     */
    #[McpTool(
        name: 'get_message_count',
        description: 'Get total number of messages for a user'
    )]
    public function getMessageCount(int $userId): array
    {
        $user = User::find($userId);
        $count = $user ? $user->chats()->count() : 0;

        return [
            'success' => true,
            'count'   => $count,
            'user_id' => $userId
        ];
    }

    /**
     * Get a message by ID
     *
     * @param int $messageId The message ID to retrieve
     *
     * @return array<string, mixed> Message or error response
     */
    #[McpTool(
        name: 'get_message_by_id',
        description: 'Get a specific chat message by its ID'
    )]
    public function getMessageById(int $messageId): array
    {
        try {
            $message = Chat::findOrFail($messageId);

            return [
                'success' => true,
                'message' => $message->toArray(),
            ];
        } catch (ModelNotFoundException) {
            return $this->createErrorResponse(
                'Message not found',
                ['message_id' => $messageId]
            );
        }
    }

    /**
     * Create a standardized error response
     *
     * @param string     $message The error message
     * @param array|null $data    Additional error data
     *
     * @return array<string, mixed> Error response array
     */
    private function createErrorResponse(string $message, ?array $data = null): array
    {
        $response = [
            'success' => false,
            'error'   => $message,
        ];

        if ($data !== null) {
            return array_merge($response, $data);
        }

        return $response;
    }

    /**
     * Validate if a sender type is valid
     *
     * @param string $senderType The sender type to validate
     *
     * @return bool True if the sender type is valid
     */
    private function isValidSenderType(string $senderType): bool
    {
        return in_array($senderType, self::VALID_SENDER_TYPES, true);
    }

    /**
     * Validate if a message is valid
     *
     * @param string $message The message to validate
     *
     * @return bool True if the message is valid
     */
    private function isValidMessage(string $message): bool
    {
        $trimmedMessage = trim($message);
        return !in_array($trimmedMessage, ['', '0'], true);
    }

    /**
     * Validate if a search query is valid
     *
     * @param string $query The search query to validate
     *
     * @return bool True if the query is valid
     */
    private function isValidSearchQuery(string $query): bool
    {
        return strlen($query) >= self::MIN_SEARCH_QUERY_LENGTH;
    }
}
