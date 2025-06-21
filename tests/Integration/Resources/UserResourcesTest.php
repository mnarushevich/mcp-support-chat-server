<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';

use App\Factories\UserFactory;
use App\Mcp\Resources\UserResources;
use App\Models\User;

beforeEach(function () {
    User::query()->delete();
});

describe('UserResources', function () {
    beforeEach(function () {
        $this->userResources = new UserResources();
    });

    describe('getUserProfile', function () {
        it('returns user profile when user exists', function () {
            // Arrange
            $user = UserFactory::create([
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'phone' => '+1234567890',
                'status' => 'active'
            ]);

            // Act
            $result = $this->userResources->getUserProfile($user->id);

            // Assert
            expect($result)->toHaveKey('user');
            expect($result)->toHaveKey('profile_url');
            expect($result)->toHaveKey('timestamp');
            expect($result['user']['id'])->toBe($user->id);
            expect($result['user']['email'])->toBe('test@example.com');
            expect($result['user']['first_name'])->toBe('John');
            expect($result['user']['last_name'])->toBe('Doe');
            expect($result['profile_url'])->toBe("user://{$user->id}/profile");
            expect($result['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
        });

        it('returns error when user does not exist', function () {
            // Act
            $result = $this->userResources->getUserProfile(999);

            // Assert
            expect($result)->toHaveKey('error', 'User not found');
            expect($result)->toHaveKey('user_id', 999);
        });
    });

    describe('getUserSummary', function () {
        it('returns user summary when user exists', function () {
            // Arrange
            $user = UserFactory::create([
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'phone' => '+1234567890',
                'status' => 'active'
            ]);

            // Act
            $result = $this->userResources->getUserSummary($user->id);

            // Assert
            expect($result)->toHaveKey('id', $user->id);
            expect($result)->toHaveKey('name', 'John Doe');
            expect($result)->toHaveKey('email', 'test@example.com');
            expect($result)->toHaveKey('status', 'active');
            expect($result)->toHaveKey('created_at');
            expect($result)->toHaveKey('summary_url', "user://{$user->id}/summary");
        });

        it('returns error when user does not exist', function () {
            // Act
            $result = $this->userResources->getUserSummary(999);

            // Assert
            expect($result)->toHaveKey('error', 'User not found');
            expect($result)->toHaveKey('user_id', 999);
        });

        it('builds full name correctly with different name combinations', function () {
            // Arrange
            $user1 = UserFactory::create([
                'first_name' => 'John',
                'last_name' => 'Doe'
            ]);
            $user2 = UserFactory::create([
                'first_name' => 'Jane',
                'last_name' => 'Smith'
            ]);

            // Act
            $result1 = $this->userResources->getUserSummary($user1->id);
            $result2 = $this->userResources->getUserSummary($user2->id);

            // Assert
            expect($result1['name'])->toBe('John Doe');
            expect($result2['name'])->toBe('Jane Smith');
        });
    });

    describe('getUsersList', function () {
        it('returns all users when users exist', function () {
            // Arrange
            $users = UserFactory::count(3)->create();

            // Act
            $result = $this->userResources->getUsersList();

            // Assert
            expect($result)->toHaveKey('users');
            expect($result)->toHaveKey('count', 3);
            expect($result)->toHaveKey('timestamp');
            expect($result['users'])->toHaveCount(3);
            expect($result['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
        });

        it('returns empty list when no users exist', function () {
            // Act
            $result = $this->userResources->getUsersList();

            // Assert
            expect($result)->toHaveKey('users');
            expect($result)->toHaveKey('count', 0);
            expect($result)->toHaveKey('timestamp');
            expect($result['users'])->toHaveCount(0);
        });

        it('includes all user data in the response', function () {
            // Arrange
            $user = UserFactory::create([
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'phone' => '+1234567890',
                'status' => 'active'
            ]);

            // Act
            $result = $this->userResources->getUsersList();

            // Assert
            expect($result['users'])->toHaveCount(1);
            expect($result['users'][0]['id'])->toBe($user->id);
            expect($result['users'][0]['email'])->toBe('test@example.com');
            expect($result['users'][0]['first_name'])->toBe('John');
            expect($result['users'][0]['last_name'])->toBe('Doe');
            expect($result['users'][0]['phone'])->toBe('+1234567890');
            expect($result['users'][0]['status'])->toBe('active');
        });
    });

    describe('getActiveUsers', function () {
        beforeEach(function () {
            // Create users with different statuses
            UserFactory::create(['status' => 'active']);
            UserFactory::create(['status' => 'active']);
            UserFactory::create(['status' => 'inactive']);
            UserFactory::create(['status' => 'suspended']);
        });

        it('returns only active users', function () {
            // Act
            $result = $this->userResources->getActiveUsers();

            // Assert
            expect($result)->toHaveKey('users');
            expect($result)->toHaveKey('count', 2);
            expect($result)->toHaveKey('timestamp');
            expect($result['users'])->toHaveCount(2);
            
            foreach ($result['users'] as $user) {
                expect($user['status'])->toBe('active');
            }
        });

        it('respects limit parameter', function () {
            // Create more active users
            UserFactory::count(5)->create(['status' => 'active']);

            // Act
            $result = $this->userResources->getActiveUsers(3);

            // Assert
            expect($result)->toHaveKey('users');
            expect($result['users'])->toHaveCount(3);
            expect($result)->toHaveKey('count', 3);
        });

        it('orders users by first name and last name', function () {
            // Create users with specific names for ordering test
            UserFactory::create([
                'first_name' => 'Alice',
                'last_name' => 'Brown',
                'status' => 'active'
            ]);
            UserFactory::create([
                'first_name' => 'Alice',
                'last_name' => 'Adams',
                'status' => 'active'
            ]);
            UserFactory::create([
                'first_name' => 'Bob',
                'last_name' => 'Adams',
                'status' => 'active'
            ]);

            // Act
            $result = $this->userResources->getActiveUsers();

            // Assert
            expect($result['users'][0]['first_name'])->toBe('Alice');
            expect($result['users'][0]['last_name'])->toBe('Adams');
            expect($result['users'][1]['first_name'])->toBe('Alice');
            expect($result['users'][1]['last_name'])->toBe('Brown');
        });

        it('returns empty list when no active users exist', function () {
            // Clear all users and create only inactive ones
            User::query()->delete();
            UserFactory::create(['status' => 'inactive']);
            UserFactory::create(['status' => 'suspended']);

            // Act
            $result = $this->userResources->getActiveUsers();

            // Assert
            expect($result)->toHaveKey('users');
            expect($result)->toHaveKey('count', 0);
            expect($result['users'])->toHaveCount(0);
        });

        it('uses default limit of 50', function () {
            // Create more than 50 active users
            UserFactory::count(60)->create(['status' => 'active']);

            // Act
            $result = $this->userResources->getActiveUsers();

            // Assert
            expect($result['users'])->toHaveCount(50);
            expect($result)->toHaveKey('count', 50);
        });
    });

    describe('private helper methods', function () {
        it('builds correct profile URL', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->userResources);
            $method = $reflection->getMethod('buildProfileUrl');
            $method->setAccessible(true);

            // Act
            $result = $method->invoke($this->userResources, 123);

            // Assert
            expect($result)->toBe('user://123/profile');
        });

        it('builds correct summary URL', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->userResources);
            $method = $reflection->getMethod('buildSummaryUrl');
            $method->setAccessible(true);

            // Act
            $result = $method->invoke($this->userResources, 456);

            // Assert
            expect($result)->toBe('user://456/summary');
        });

        it('builds full name correctly', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->userResources);
            $method = $reflection->getMethod('buildFullName');
            $method->setAccessible(true);

            // Act
            $result = $method->invoke($this->userResources, 'John', 'Doe');

            // Assert
            expect($result)->toBe('John Doe');
        });

        it('returns current timestamp in ISO format', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->userResources);
            $method = $reflection->getMethod('getCurrentTimestamp');
            $method->setAccessible(true);

            // Act
            $result = $method->invoke($this->userResources);

            // Assert
            expect($result)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
        });

        it('creates error response correctly', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->userResources);
            $method = $reflection->getMethod('createErrorResponse');
            $method->setAccessible(true);

            // Act
            $result = $method->invoke($this->userResources, 999);

            // Assert
            expect($result)->toHaveKey('error', 'User not found');
            expect($result)->toHaveKey('user_id', 999);
        });
    });

    describe('edge cases', function () {
        it('handles users with empty names', function () {
            // Arrange
            $user = UserFactory::create([
                'first_name' => '',
                'last_name' => ''
            ]);

            // Act
            $result = $this->userResources->getUserSummary($user->id);

            // Assert
            expect($result['name'])->toBe(' ');
        });

        it('handles users with only first name', function () {
            // Arrange
            $user = UserFactory::create([
                'first_name' => 'John',
                'last_name' => ''
            ]);

            // Act
            $result = $this->userResources->getUserSummary($user->id);

            // Assert
            expect($result['name'])->toBe('John ');
        });

        it('handles users with only last name', function () {
            // Arrange
            $user = UserFactory::create([
                'first_name' => '',
                'last_name' => 'Doe'
            ]);

            // Act
            $result = $this->userResources->getUserSummary($user->id);

            // Assert
            expect($result['name'])->toBe(' Doe');
        });

        it('handles users with special characters in names', function () {
            // Arrange
            $user = UserFactory::create([
                'first_name' => 'José',
                'last_name' => 'O\'Connor'
            ]);

            // Act
            $result = $this->userResources->getUserSummary($user->id);

            // Assert
            expect($result['name'])->toBe('José O\'Connor');
        });
    });
}); 