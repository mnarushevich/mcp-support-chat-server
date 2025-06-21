<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';

use App\Factories\UserFactory;
use App\Mcp\Tools\UserTools;
use App\Models\User;

beforeEach(function () {
    User::query()->delete();
});

describe('UserTools', function () {
    beforeEach(function () {
        $this->userTools = new UserTools();
    });

    describe('getUserInfo', function () {
        it('returns user information when user exists', function () {
            // Arrange
            $user = UserFactory::create([
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'phone' => '+1234567890',
                'status' => 'active'
            ]);

            // Act
            $result = $this->userTools->getUserInfo($user->id);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('user');
            expect($result['user']['id'])->toBe($user->id);
            expect($result['user']['email'])->toBe('test@example.com');
            expect($result['user']['first_name'])->toBe('John');
            expect($result['user']['last_name'])->toBe('Doe');
        });

        it('returns error when user does not exist', function () {
            // Act
            $result = $this->userTools->getUserInfo(999);

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'User not found');
            expect($result)->toHaveKey('user_id', 999);
        });
    });

    describe('searchUsers', function () {
        beforeEach(function () {
            // Create test users
            UserFactory::create([
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com'
            ]);
            UserFactory::create([
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith@example.com'
            ]);
            UserFactory::create([
                'first_name' => 'Bob',
                'last_name' => 'Johnson',
                'email' => 'bob.johnson@example.com'
            ]);
        });

        it('finds users by first name', function () {
            // Act
            $result = $this->userTools->searchUsers('John');

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('users');
            expect($result['users'])->toHaveCount(2);
            expect(collect($result['users'])->pluck('first_name'))->toContain('John');
        });

        it('finds users by last name', function () {
            // Act
            $result = $this->userTools->searchUsers('Smith');

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['users'])->toHaveCount(1);
            expect($result['users'][0]['last_name'])->toBe('Smith');
        });

        it('finds users by email', function () {
            // Act
            $result = $this->userTools->searchUsers('john.doe@example.com');

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['users'])->toHaveCount(1);
            expect($result['users'][0]['email'])->toBe('john.doe@example.com');
        });

        it('finds multiple users with partial match', function () {
            // Act
            $result = $this->userTools->searchUsers('john');

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['users'])->toHaveCount(2); // John Doe and Bob Johnson
        });

        it('returns error for short search query', function () {
            // Act
            $result = $this->userTools->searchUsers('a');

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'Search query must be at least 2 characters long');
        });

        it('respects limit parameter', function () {
            // Create more users
            for ($i = 0; $i < 5; $i++) {
                UserFactory::create([
                    'first_name' => 'Test',
                    'email' => "testuser{$i}@example.com"
                ]);
            }

            // Act
            $result = $this->userTools->searchUsers('test', 3);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['users'])->toHaveCount(3);
            expect($result)->toHaveKey('count', 3);
        });

        it('returns empty results when no matches found', function () {
            // Act
            $result = $this->userTools->searchUsers('nonexistent');

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['users'])->toHaveCount(0);
            expect($result)->toHaveKey('count', 0);
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
            $result = $this->userTools->getActiveUsers();

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['users'])->toHaveCount(2);
            expect($result)->toHaveKey('count', 2);
            
            foreach ($result['users'] as $user) {
                expect($user['status'])->toBe('active');
            }
        });

        it('respects limit parameter', function () {
            // Create more active users
            UserFactory::count(5)->create(['status' => 'active']);

            // Act
            $result = $this->userTools->getActiveUsers(3);

            // Assert
            expect($result)->toHaveKey('success', true);
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
            $result = $this->userTools->getActiveUsers();

            // Assert
            expect($result['users'][0]['first_name'])->toBe('Alice');
            expect($result['users'][0]['last_name'])->toBe('Adams');
            expect($result['users'][1]['first_name'])->toBe('Alice');
            expect($result['users'][1]['last_name'])->toBe('Brown');
        });
    });

    describe('getUserByEmail', function () {
        it('returns user when email exists', function () {
            // Arrange
            $user = UserFactory::create([
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe'
            ]);

            // Act
            $result = $this->userTools->getUserByEmail('test@example.com');

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('user');
            expect($result['user']['id'])->toBe($user->id);
            expect($result['user']['email'])->toBe('test@example.com');
        });

        it('returns error when email does not exist', function () {
            // Act
            $result = $this->userTools->getUserByEmail('nonexistent@example.com');

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'User not found');
            expect($result)->toHaveKey('email', 'nonexistent@example.com');
        });

        it('returns error for invalid email format', function () {
            // Act
            $result = $this->userTools->getUserByEmail('invalid-email');

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'Invalid email address format');
        });

        it('returns error for empty email', function () {
            // Act
            $result = $this->userTools->getUserByEmail('');

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'Invalid email address format');
        });
    });

    describe('createUser', function () {
        it('creates user successfully with all required fields', function () {
            // Act
            $result = $this->userTools->createUser(
                'newuser@example.com',
                'New',
                'User',
                '+1234567890'
            );

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('user');
            expect($result)->toHaveKey('message', 'User created successfully');
            expect($result['user']['email'])->toBe('newuser@example.com');
            expect($result['user']['first_name'])->toBe('New');
            expect($result['user']['last_name'])->toBe('User');
            expect($result['user']['phone'])->toBe('+1234567890');
            expect($result['user']['status'])->toBe('active');
        });

        it('creates user successfully without phone number', function () {
            // Act
            $result = $this->userTools->createUser(
                'newuser@example.com',
                'New',
                'User'
            );

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['user']['phone'])->toBeNull();
        });

        it('returns error for invalid email format', function () {
            // Act
            $result = $this->userTools->createUser(
                'invalid-email',
                'New',
                'User'
            );

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'Invalid email address format');
        });

        it('returns error when user with email already exists', function () {
            // Arrange
            UserFactory::create(['email' => 'existing@example.com']);

            // Act
            $result = $this->userTools->createUser(
                'existing@example.com',
                'New',
                'User'
            );

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'User with this email already exists');
        });

        it('returns error for empty email', function () {
            // Act
            $result = $this->userTools->createUser(
                '',
                'New',
                'User'
            );

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'Invalid email address format');
        });
    });

    describe('updateUser', function () {
        it('updates user successfully', function () {
            // Arrange
            $user = UserFactory::create([
                'email' => 'old@example.com',
                'first_name' => 'Old',
                'last_name' => 'Name'
            ]);

            $updateData = [
                'email' => 'new@example.com',
                'first_name' => 'New',
                'last_name' => 'Name'
            ];

            // Act
            $result = $this->userTools->updateUser($user->id, $updateData);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('message', 'User updated successfully');
            expect($result['user']['email'])->toBe('new@example.com');
            expect($result['user']['first_name'])->toBe('New');
            expect($result['user']['last_name'])->toBe('Name');
        });

        it('returns error when user does not exist', function () {
            // Act
            $result = $this->userTools->updateUser(999, ['first_name' => 'New']);

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'User not found');
            expect($result)->toHaveKey('user_id', 999);
        });

        it('returns error for invalid email in update data', function () {
            // Arrange
            $user = UserFactory::create();

            // Act
            $result = $this->userTools->updateUser($user->id, ['email' => 'invalid-email']);

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'Invalid email address format');
        });

        it('updates user successfully without email field', function () {
            // Arrange
            $user = UserFactory::create([
                'first_name' => 'Old',
                'last_name' => 'Name'
            ]);

            $updateData = [
                'first_name' => 'New',
                'last_name' => 'Name'
            ];

            // Act
            $result = $this->userTools->updateUser($user->id, $updateData);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['user']['first_name'])->toBe('New');
            expect($result['user']['email'])->toBe($user->email); // Email unchanged
        });
    });
}); 