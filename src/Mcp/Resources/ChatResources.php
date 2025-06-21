<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PhpMcp\Server\Attributes\McpResourceTemplate;

/**
 * Chat resource templates for MCP server
 */
readonly class ChatResources
{
    /**
     * Get chat history for a user
     *
     * @param int $userId The user ID to retrieve chat history for
     * @param int $limit  Maximum number of messages to return
     * @param int $offset Number of messages to skip
     *
     * @return array<string, mixed> Chat history resource
     */
    #[McpResourceTemplate(
        uriTemplate: 'chat://{userId}/history',
        name: 'chat_history',
        description: 'Chat message history for a user',
        mimeType: 'application/json'
    )]
    public function getChatHistory(int $userId, int $limit = 50, int $offset = 0): array
    {
        $user = User::find($userId);
        
        if ($user === null) {
            return $this->createErrorHistoryResponse($userId, 'User not found');
        }

        $messages = $user->chats()
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $this->createChatHistoryResponse($userId, $messages, 'history');
    }

    /**
     * Get recent messages for a user
     *
     * @param int $userId The user ID to retrieve recent messages for
     * @param int $limit  Maximum number of messages to return
     * @param int $offset Number of messages to skip
     *
     * @return array<string, mixed> Recent messages resource
     */
    #[McpResourceTemplate(
        uriTemplate: 'chat://{userId}/recent',
        name: 'recent_messages',
        description: 'Recent chat messages for a user',
        mimeType: 'application/json'
    )]
    public function getRecentMessages(int $userId, int $limit = 50, int $offset = 0): array
    {
        $user = User::find($userId);
        
        if ($user === null) {
            return $this->createErrorRecentResponse($userId, 'User not found');
        }

        $messages = $user->chats()
            ->limit($limit)
            ->offset($offset)
            ->orderBy('timestamp', 'desc')
            ->get();

        return $this->createChatHistoryResponse($userId, $messages, 'recent');
    }

    /**
     * Get chat session history
     *
     * @param string $sessionId The session ID to retrieve messages for
     * @param int    $limit     Maximum number of messages to return
     * @param int    $offset    Number of messages to skip
     *
     * @return array<string, mixed> Session history resource
     */
    #[McpResourceTemplate(
        uriTemplate: 'chat://session/{sessionId}',
        name: 'session_history',
        description: 'Chat messages for a specific session',
        mimeType: 'application/json'
    )]
    public function getSessionHistory(string $sessionId, int $limit = 50, int $offset = 0): array
    {
        $messages = Chat::where('session_id', $sessionId)
            ->limit($limit)
            ->offset($offset)
            ->orderBy('timestamp', 'desc')
            ->get();

        return [
            'session_id'  => $sessionId,
            'messages'    => $messages->toArray(),
            'count'       => $messages->count(),
            'session_url' => $this->buildSessionUrl($sessionId),
            'timestamp'   => $this->getCurrentTimestamp(),
        ];
    }

    /**
     * Get active sessions for a user
     *
     * @param int $userId The user ID to get sessions for
     *
     * @return array<string, mixed> User sessions resource
     */
    #[McpResourceTemplate(
        uriTemplate: 'chat://{userId}/sessions',
        name: 'user_sessions',
        description: 'Active chat sessions for a user',
        mimeType: 'application/json'
    )]
    public function getUserSessions(int $userId): array
    {
        try {
            $sessions = User::findOrFail($userId)
                ->chats()
                ->select('session_id', Manager::raw('MAX(timestamp) as last_message_time'))
                ->whereNotNull('session_id')
                ->groupBy('session_id')
                ->orderByDesc('last_message_time')
                ->get();

            return $this->createSessionsResponse($userId, $sessions);
        } catch (ModelNotFoundException) {
            return $this->createErrorSessionsResponse($userId);
        }
    }

    /**
     * Get message count for a user
     *
     * @param int $userId The user ID to get message count for
     *
     * @return array<string, mixed> Message count resource
     */
    #[McpResourceTemplate(
        uriTemplate: 'chat://{userId}/count',
        name: 'message_count',
        description: 'Total message count for a user',
        mimeType: 'application/json'
    )]
    public function getMessageCount(int $userId): array
    {
        $user = User::find($userId);
        $messageCount = $user ? $user->chats()->count() : 0;

        return [
            'user_id'       => $userId,
            'message_count' => $messageCount,
            'count_url'     => $this->buildCountUrl($userId),
            'timestamp'     => $this->getCurrentTimestamp(),
        ];
    }

    /**
     * Create a standardized chat history response
     *
     * @param int    $userId   The user ID
     * @param mixed  $messages The messages collection
     * @param string $type     The type of history (history or recent)
     *
     * @return array<string, mixed> Chat history response
     */
    private function createChatHistoryResponse(int $userId, $messages, string $type): array
    {
        $urlKey = $type . '_url';
        $urlMethod = 'build' . ucfirst($type) . 'Url';

        return [
            'user_id'   => $userId,
            'messages'  => $messages->toArray(),
            'count'     => $messages->count(),
            $urlKey     => $this->$urlMethod($userId),
            'timestamp' => $this->getCurrentTimestamp(),
        ];
    }

    /**
     * Create a sessions response
     *
     * @param int   $userId   The user ID
     * @param mixed $sessions The sessions collection
     *
     * @return array<string, mixed> Sessions response
     */
    private function createSessionsResponse(int $userId, $sessions): array
    {
        return [
            'user_id'      => $userId,
            'sessions'     => $sessions->toArray(),
            'count'        => $sessions->count(),
            'sessions_url' => $this->buildSessionsUrl($userId),
            'timestamp'    => $this->getCurrentTimestamp(),
        ];
    }

    /**
     * Create an error sessions response
     *
     * @param int $userId The user ID
     *
     * @return array<string, mixed> Error sessions response
     */
    private function createErrorSessionsResponse(int $userId): array
    {
        return [
            'error'        => 'User not found',
            'user_id'      => $userId,
            'sessions'     => [],
            'count'        => 0,
            'sessions_url' => $this->buildSessionsUrl($userId),
            'timestamp'    => $this->getCurrentTimestamp(),
        ];
    }

    /**
     * Build a chat history URL
     *
     * @param int $userId The user ID
     *
     * @return string The chat history URL
     */
    private function buildHistoryUrl(int $userId): string
    {
        return sprintf('chat://%d/history', $userId);
    }

    /**
     * Build a recent messages URL
     *
     * @param int $userId The user ID
     *
     * @return string The recent messages URL
     */
    private function buildRecentUrl(int $userId): string
    {
        return sprintf('chat://%d/recent', $userId);
    }

    /**
     * Build a sessions URL
     *
     * @param int $userId The user ID
     *
     * @return string The sessions URL
     */
    private function buildSessionsUrl(int $userId): string
    {
        return sprintf('chat://%d/sessions', $userId);
    }

    /**
     * Build a count URL
     *
     * @param int $userId The user ID
     *
     * @return string The count URL
     */
    private function buildCountUrl(int $userId): string
    {
        return sprintf('chat://%d/count', $userId);
    }

    /**
     * Build a session URL
     *
     * @param string $sessionId The session ID
     *
     * @return string The session URL
     */
    private function buildSessionUrl(string $sessionId): string
    {
        return 'chat://session/' . $sessionId;
    }

    /**
     * Get current timestamp in ISO 8601 format
     *
     * @return string Current timestamp
     */
    private function getCurrentTimestamp(): string
    {
        return date('c');
    }

    /**
     * Create an error history response
     *
     * @param int    $userId      The user ID
     * @param string $errorMessage The error message
     *
     * @return array<string, mixed> Error history response
     */
    private function createErrorHistoryResponse(int $userId, string $errorMessage): array
    {
        return [
            'error'       => $errorMessage,
            'user_id'     => $userId,
            'messages'    => [],
            'count'       => 0,
            'history_url' => $this->buildHistoryUrl($userId),
            'timestamp'   => $this->getCurrentTimestamp(),
        ];
    }

    /**
     * Create an error recent response
     *
     * @param int    $userId      The user ID
     * @param string $errorMessage The error message
     *
     * @return array<string, mixed> Error recent response
     */
    private function createErrorRecentResponse(int $userId, string $errorMessage): array
    {
        return [
            'error'       => $errorMessage,
            'user_id'     => $userId,
            'messages'    => [],
            'count'       => 0,
            'recent_url'  => $this->buildRecentUrl($userId),
            'timestamp'   => $this->getCurrentTimestamp(),
        ];
    }
}