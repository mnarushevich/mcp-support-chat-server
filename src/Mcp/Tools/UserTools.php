<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PhpMcp\Server\Attributes\McpTool;

/**
 * User management tools for MCP server
 */
readonly class UserTools
{
    /**
     * Get user information by ID
     *
     * @param int $userId The user ID to retrieve
     *
     * @return array<string, mixed> User information or error response
     */
    #[McpTool(
        name: 'get_user_info',
        description: 'Retrieve user information by user ID'
    )]
    public function getUserInfo(int $userId): array
    {
        try {
            $user = User::findOrFail($userId);

            return [
                'success' => true,
                'user'    => $user->toArray(),
            ];
        } catch (ModelNotFoundException) {
            return $this->createErrorResponse(
                'User not found',
                ['user_id' => $userId]
            );
        }
    }

    /**
     * Search users by name or email
     *
     * @param string $query The search query
     * @param int    $limit Maximum number of results to return
     *
     * @return array<string, mixed> Search results or error response
     */
    #[McpTool(
        name: 'search_users',
        description: 'Search users by name or email address'
    )]
    public function searchUsers(string $query, int $limit = 10): array
    {
        if (!$this->isValidSearchQuery($query)) {
            return $this->createErrorResponse(
                'Search query must be at least 2 characters long'
            );
        }

        $users = User::where('first_name', 'like', sprintf('%%%s%%', $query))
            ->orWhere('last_name', 'like', sprintf('%%%s%%', $query))
            ->orWhere('email', 'like', sprintf('%%%s%%', $query))
            ->limit($limit)
            ->get();

        return [
            'success' => true,
            'users'   => $users,
            'count'   => $users->count(),
            'query'   => $query,
        ];
    }

    /**
     * Get all active users
     *
     * @param int $limit Maximum number of results to return
     *
     * @return array<string, mixed> Active users list
     */
    #[McpTool(
        name: 'get_active_users',
        description: 'Get all active users in the system'
    )]
    public function getActiveUsers(int $limit = 50): array
    {
        $users = User::where('status', 'active')
            ->limit($limit)
            ->orderBy('first_name', 'asc')
            ->orderBy('last_name', 'asc')
            ->get();

        return [
            'success' => true,
            'users'   => $users,
            'count'   => $users->count(),
        ];
    }

    /**
     * Get user by email
     *
     * @param string $email The email address to search for
     *
     * @return array<string, mixed> User information or error response
     */
    #[McpTool(
        name: 'get_user_by_email',
        description: 'Retrieve user information by email address'
    )]
    public function getUserByEmail(string $email): array
    {
        if (!$this->isValidEmail($email)) {
            return $this->createErrorResponse('Invalid email address format');
        }

        try {
            $user = User::where('email', $email)->firstOrFail();

            return [
                'success' => true,
                'user'    => $user->toArray(),
            ];
        } catch (ModelNotFoundException) {
            return $this->createErrorResponse(
                'User not found',
                ['email' => $email]
            );
        }
    }

    /**
     * Create a new user
     *
     * @param string      $email     The user's email address
     * @param string      $firstName The user's first name
     * @param string      $lastName  The user's last name
     * @param string|null $phone     The user's phone number (optional)
     *
     * @return array<string, mixed> Created user information or error response
     */
    #[McpTool(
        name: 'create_user',
        description: 'Create a new user account'
    )]
    public function createUser(
        string $email,
        string $firstName,
        string $lastName,
        ?string $phone = null
    ): array {
        if (!$this->isValidEmail($email)) {
            return $this->createErrorResponse('Invalid email address format');
        }

        if ($this->userExists($email)) {
            return $this->createErrorResponse('User with this email already exists');
        }

        $userData = $this->prepareUserData($email, $firstName, $lastName, $phone);

        try {
            $user = User::create($userData);

            return [
                'success' => true,
                'user'    => $user,
                'message' => 'User created successfully',
            ];
        } catch (\Exception $exception) {
            return $this->createErrorResponse(
                'Failed to create user: ' . $exception->getMessage()
            );
        }
    }

    /**
     * Update user information
     *
     * @param int   $userId   The user ID to update
     * @param array $userData The data to update
     *
     * @return array<string, mixed> Updated user information or error response
     */
    #[McpTool(
        name: 'update_user',
        description: 'Update user information'
    )]
    public function updateUser(int $userId, array $userData): array
    {
        try {
            $user = User::findOrFail($userId);
        } catch (ModelNotFoundException) {
            return $this->createErrorResponse(
                'User not found',
                ['user_id' => $userId]
            );
        }

        if (!$this->validateUpdateData($userData)) {
            return $this->createErrorResponse('Invalid email address format');
        }

        try {
            $success = $user->update($userData);

            if ($success) {
                $user->refresh();

                return [
                    'success' => true,
                    'user'    => $user->toArray(),
                    'message' => 'User updated successfully'
                ];
            }

            return $this->createErrorResponse('Failed to update user');
        } catch (\Exception $exception) {
            return $this->createErrorResponse(
                'Failed to update user: ' . $exception->getMessage()
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
     * Validate if a search query is valid
     *
     * @param string $query The search query to validate
     *
     * @return bool True if the query is valid
     */
    private function isValidSearchQuery(string $query): bool
    {
        return strlen($query) >= 2;
    }

    /**
     * Validate if an email address is valid
     *
     * @param string $email The email address to validate
     *
     * @return bool True if the email is valid
     */
    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if a user exists with the given email
     *
     * @param string $email The email address to check
     *
     * @return bool True if the user exists
     */
    private function userExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Prepare user data for creation
     *
     * @param string      $email     The user's email
     * @param string      $firstName The user's first name
     * @param string      $lastName  The user's last name
     * @param string|null $phone     The user's phone number
     *
     * @return array<string, mixed> Prepared user data
     */
    private function prepareUserData(
        string $email,
        string $firstName,
        string $lastName,
        ?string $phone
    ): array {
        return [
            'email'      => $email,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'phone'      => $phone,
            'status'     => 'active',
        ];
    }

    /**
     * Validate user update data
     *
     * @param array $userData The data to validate
     *
     * @return bool True if the data is valid
     */
    private function validateUpdateData(array $userData): bool
    {
        if (!isset($userData['email'])) {
            return true;
        }

        return $this->isValidEmail($userData['email']);
    }
}
