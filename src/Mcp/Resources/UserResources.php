<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PhpMcp\Server\Attributes\McpResource;
use PhpMcp\Server\Attributes\McpResourceTemplate;

/**
 * User resource templates for MCP server
 */
readonly class UserResources
{
    /**
     * Get user profile data
     *
     * @param int $userId The user ID to retrieve profile for
     *
     * @return array<string, mixed> User profile resource or error response
     */
    #[McpResourceTemplate(
        uriTemplate: 'user://{userId}/profile',
        name: 'user_profile',
        description: 'User profile information',
        mimeType: 'application/json'
    )]
    public function getUserProfile(int $userId): array
    {
        try {
            $user = User::findOrFail($userId);

            return [
                'user'        => $user,
                'profile_url' => $this->buildProfileUrl($userId),
                'timestamp'   => $this->getCurrentTimestamp(),
            ];
        } catch (ModelNotFoundException) {
            return $this->createErrorResponse($userId);
        }
    }

    /**
     * Get user summary
     *
     * @param int $userId The user ID to retrieve summary for
     *
     * @return array<string, mixed> User summary resource or error response
     */
    #[McpResourceTemplate(
        uriTemplate: 'user://{userId}/summary',
        name: 'user_summary',
        description: 'User summary information',
        mimeType: 'application/json'
    )]
    public function getUserSummary(int $userId): array
    {
        try {
            $user = User::findOrFail($userId);

            return [
                'id'          => $user['id'],
                'name'        => $this->buildFullName($user['first_name'], $user['last_name']),
                'email'       => $user['email'],
                'status'      => $user['status'],
                'created_at'  => $user['created_at'],
                'summary_url' => $this->buildSummaryUrl($userId),
            ];
        } catch (ModelNotFoundException) {
            return $this->createErrorResponse($userId);
        }
    }

    /**
     * Get all user's list
     *
     * @return array<string, mixed> Users list resource
     */
    #[McpResource(
        uri: 'users://list',
        name: 'users_list',
        description: 'List of all users in the system',
        mimeType: 'application/json'
    )]
    public function getUsersList(): array
    {
        $users = User::all();

        return [
            'users'     => $users->toArray(),
            'count'     => $users->count(),
            'timestamp' => $this->getCurrentTimestamp(),
        ];
    }

    /**
     * Get an active users list
     *
     * @param int $limit Maximum number of users to return
     *
     * @return array<string, mixed> Active users resource
     */
    #[McpResource(
        uri: 'users://active',
        name: 'active_users',
        description: 'List of all active users',
        mimeType: 'application/json'
    )]
    public function getActiveUsers(int $limit = 50): array
    {
        $users = User::where('status', 'active')
            ->limit($limit)
            ->orderBy('first_name', 'asc')
            ->orderBy('last_name', 'asc')
            ->get();

        return [
            'users'     => $users->toArray(),
            'count'     => $users->count(),
            'timestamp' => $this->getCurrentTimestamp(),
        ];
    }

    /**
     * Create a standardized error response
     *
     * @param int $userId The user ID that was not found
     *
     * @return array<string, mixed> Error response array
     */
    private function createErrorResponse(int $userId): array
    {
        return [
            'error'   => 'User not found',
            'user_id' => $userId,
        ];
    }

    /**
     * Build a user profile URL
     *
     * @param int $userId The user ID
     *
     * @return string The profile URL
     */
    private function buildProfileUrl(int $userId): string
    {
        return sprintf('user://%d/profile', $userId);
    }

    /**
     * Build a user summary URL
     *
     * @param int $userId The user ID
     *
     * @return string The summary URL
     */
    private function buildSummaryUrl(int $userId): string
    {
        return sprintf('user://%d/summary', $userId);
    }

    /**
     * Build a full name from the first and last name
     *
     * @param string $firstName The user's first name
     * @param string $lastName  The user's last name
     *
     * @return string The full name
     */
    private function buildFullName(string $firstName, string $lastName): string
    {
        return $firstName . ' ' . $lastName;
    }

    /**
     * Get the current timestamp in ISO 8601 format
     *
     * @return string Current timestamp
     */
    private function getCurrentTimestamp(): string
    {
        return date('c');
    }
}
