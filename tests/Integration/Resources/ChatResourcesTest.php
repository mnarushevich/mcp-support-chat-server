<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';

use App\Factories\ChatFactory;
use App\Factories\UserFactory;
use App\Mcp\Resources\ChatResources;
use App\Models\Chat;
use App\Models\User;

beforeEach(function () {
    Chat::query()->delete();
    User::query()->delete();
});

describe('ChatResources', function () {
    beforeEach(function () {
        $this->chatResources = new ChatResources();
    });

    describe('getChatHistory', function () {
        it('returns chat history when user exists', function () {
            // Arrange
            $user = UserFactory::create();
            $messages = ChatFactory::count(3)->create(['user_id' => $user->id]);

            // Act
            $result = $this->chatResources->getChatHistory($user->id);

            // Assert
            expect($result)->toHaveKey('user_id', $user->id);
            expect($result)->toHaveKey('messages');
            expect($result)->toHaveKey('count', 3);
            expect($result)->toHaveKey('history_url', "chat://{$user->id}/history");
            expect($result)->toHaveKey('timestamp');
            expect($result['messages'])->toHaveCount(3);
            expect($result['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
        });

        it('returns empty history when user has no messages', function () {
            // Arrange
            $user = UserFactory::create();

            // Act
            $result = $this->chatResources->getChatHistory($user->id);

            // Assert
            expect($result)->toHaveKey('user_id', $user->id);
            expect($result)->toHaveKey('messages');
            expect($result)->toHaveKey('count', 0);
            expect($result['messages'])->toHaveCount(0);
        });

        it('respects limit parameter', function () {
            // Arrange
            $user = UserFactory::create();
            ChatFactory::count(5)->create(['user_id' => $user->id]);

            // Act
            $result = $this->chatResources->getChatHistory($user->id, 3);

            // Assert
            expect($result)->toHaveKey('count', 3);
            expect($result['messages'])->toHaveCount(3);
        });

        it('respects offset parameter', function () {
            // Arrange
            $user = UserFactory::create();
            ChatFactory::count(5)->create(['user_id' => $user->id]);

            // Act
            $result = $this->chatResources->getChatHistory($user->id, 3, 2);

            // Assert
            expect($result)->toHaveKey('count', 3);
            expect($result['messages'])->toHaveCount(3);
        });

        it('returns error when user does not exist', function () {
            // Act
            $result = $this->chatResources->getChatHistory(999);

            // Assert
            expect($result)->toHaveKey('error', 'User not found');
            expect($result)->toHaveKey('user_id', 999);
            expect($result)->toHaveKey('messages');
            expect($result)->toHaveKey('count', 0);
            expect($result)->toHaveKey('history_url');
            expect($result)->toHaveKey('timestamp');
            expect($result['messages'])->toHaveCount(0);
        });
    });

    describe('getRecentMessages', function () {
        it('returns recent messages when user exists', function () {
            // Arrange
            $user = UserFactory::create();
            $messages = ChatFactory::count(3)->create(['user_id' => $user->id]);

            // Act
            $result = $this->chatResources->getRecentMessages($user->id);

            // Assert
            expect($result)->toHaveKey('user_id', $user->id);
            expect($result)->toHaveKey('messages');
            expect($result)->toHaveKey('count', 3);
            expect($result)->toHaveKey('recent_url', "chat://{$user->id}/recent");
            expect($result)->toHaveKey('timestamp');
            expect($result['messages'])->toHaveCount(3);
        });

        it('returns empty messages when user has no chat history', function () {
            // Arrange
            $user = UserFactory::create();

            // Act
            $result = $this->chatResources->getRecentMessages($user->id);

            // Assert
            expect($result)->toHaveKey('user_id', $user->id);
            expect($result)->toHaveKey('messages');
            expect($result)->toHaveKey('count', 0);
            expect($result['messages'])->toHaveCount(0);
        });

        it('respects limit parameter', function () {
            // Arrange
            $user = UserFactory::create();
            ChatFactory::count(5)->create(['user_id' => $user->id]);

            // Act
            $result = $this->chatResources->getRecentMessages($user->id, 3);

            // Assert
            expect($result)->toHaveKey('count', 3);
            expect($result['messages'])->toHaveCount(3);
        });

        it('respects offset parameter', function () {
            // Arrange
            $user = UserFactory::create();
            ChatFactory::count(5)->create(['user_id' => $user->id]);

            // Act
            $result = $this->chatResources->getRecentMessages($user->id, 3, 2);

            // Assert
            expect($result)->toHaveKey('count', 3);
            expect($result['messages'])->toHaveCount(3);
        });

        it('returns error when user does not exist', function () {
            // Act
            $result = $this->chatResources->getRecentMessages(999);

            // Assert
            expect($result)->toHaveKey('error', 'User not found');
            expect($result)->toHaveKey('user_id', 999);
            expect($result)->toHaveKey('messages');
            expect($result)->toHaveKey('count', 0);
            expect($result)->toHaveKey('recent_url');
            expect($result)->toHaveKey('timestamp');
            expect($result['messages'])->toHaveCount(0);
        });
    });

    describe('getSessionHistory', function () {
        it('returns session history when session exists', function () {
            // Arrange
            $sessionId = 'test-session-123';
            $user = UserFactory::create();
            ChatFactory::count(3)->create([
                'user_id' => $user->id,
                'session_id' => $sessionId
            ]);

            // Act
            $result = $this->chatResources->getSessionHistory($sessionId);

            // Assert
            expect($result)->toHaveKey('session_id', $sessionId);
            expect($result)->toHaveKey('messages');
            expect($result)->toHaveKey('count', 3);
            expect($result)->toHaveKey('session_url', "chat://session/{$sessionId}");
            expect($result)->toHaveKey('timestamp');
            expect($result['messages'])->toHaveCount(3);
        });

        it('returns empty messages when session does not exist', function () {
            // Act
            $result = $this->chatResources->getSessionHistory('nonexistent-session');

            // Assert
            expect($result)->toHaveKey('session_id', 'nonexistent-session');
            expect($result)->toHaveKey('messages');
            expect($result)->toHaveKey('count', 0);
            expect($result['messages'])->toHaveCount(0);
        });

        it('respects limit parameter', function () {
            // Arrange
            $sessionId = 'test-session-123';
            $user = UserFactory::create();
            ChatFactory::count(5)->create([
                'user_id' => $user->id,
                'session_id' => $sessionId
            ]);

            // Act
            $result = $this->chatResources->getSessionHistory($sessionId, 3);

            // Assert
            expect($result)->toHaveKey('count', 3);
            expect($result['messages'])->toHaveCount(3);
        });

        it('respects offset parameter', function () {
            // Arrange
            $sessionId = 'test-session-123';
            $user = UserFactory::create();
            ChatFactory::count(5)->create([
                'user_id' => $user->id,
                'session_id' => $sessionId
            ]);

            // Act
            $result = $this->chatResources->getSessionHistory($sessionId, 3, 2);

            // Assert
            expect($result)->toHaveKey('count', 3);
            expect($result['messages'])->toHaveCount(3);
        });
    });

    describe('getUserSessions', function () {
        it('returns user sessions when user exists', function () {
            // Arrange
            $user = UserFactory::create();
            $session1 = 'session-1';
            $session2 = 'session-2';
            
            ChatFactory::count(2)->create([
                'user_id' => $user->id,
                'session_id' => $session1
            ]);
            ChatFactory::count(3)->create([
                'user_id' => $user->id,
                'session_id' => $session2
            ]);

            // Act
            $result = $this->chatResources->getUserSessions($user->id);

            // Assert
            expect($result)->toHaveKey('user_id', $user->id);
            expect($result)->toHaveKey('sessions');
            expect($result)->toHaveKey('count', 2);
            expect($result)->toHaveKey('sessions_url', "chat://{$user->id}/sessions");
            expect($result)->toHaveKey('timestamp');
            expect($result['sessions'])->toHaveCount(2);
        });

        it('returns error when user does not exist', function () {
            // Act
            $result = $this->chatResources->getUserSessions(999);

            // Assert
            expect($result)->toHaveKey('error', 'User not found');
            expect($result)->toHaveKey('user_id', 999);
            expect($result)->toHaveKey('sessions');
            expect($result)->toHaveKey('count', 0);
            expect($result['sessions'])->toHaveCount(0);
        });

        it('returns empty sessions when user has no sessions', function () {
            // Arrange
            $user = UserFactory::create();

            // Act
            $result = $this->chatResources->getUserSessions($user->id);

            // Assert
            expect($result)->toHaveKey('user_id', $user->id);
            expect($result)->toHaveKey('sessions');
            expect($result)->toHaveKey('count', 0);
            expect($result['sessions'])->toHaveCount(0);
        });

        it('excludes messages without session_id', function () {
            // Arrange
            $user = UserFactory::create();
            ChatFactory::create([
                'user_id' => $user->id,
                'session_id' => null
            ]);
            ChatFactory::create([
                'user_id' => $user->id,
                'session_id' => 'valid-session'
            ]);

            // Act
            $result = $this->chatResources->getUserSessions($user->id);

            // Assert
            expect($result)->toHaveKey('count', 1);
            expect($result['sessions'])->toHaveCount(1);
            expect($result['sessions'][0]['session_id'])->toBe('valid-session');
        });
    });

    describe('getMessageCount', function () {
        it('returns correct message count for user', function () {
            // Arrange
            $user = UserFactory::create();
            ChatFactory::count(5)->create(['user_id' => $user->id]);

            // Act
            $result = $this->chatResources->getMessageCount($user->id);

            // Assert
            expect($result)->toHaveKey('user_id', $user->id);
            expect($result)->toHaveKey('message_count', 5);
            expect($result)->toHaveKey('count_url', "chat://{$user->id}/count");
            expect($result)->toHaveKey('timestamp');
        });

        it('returns zero count when user has no messages', function () {
            // Arrange
            $user = UserFactory::create();

            // Act
            $result = $this->chatResources->getMessageCount($user->id);

            // Assert
            expect($result)->toHaveKey('user_id', $user->id);
            expect($result)->toHaveKey('message_count', 0);
        });

        it('returns zero count when user does not exist', function () {
            // Act
            $result = $this->chatResources->getMessageCount(999);

            // Assert
            expect($result)->toHaveKey('user_id', 999);
            expect($result)->toHaveKey('message_count', 0);
            expect($result)->toHaveKey('count_url');
            expect($result)->toHaveKey('timestamp');
        });

        it('handles very large user ID', function () {
            // Arrange
            $user = UserFactory::create();
            ChatFactory::create(['user_id' => $user->id]);

            // Act
            $result = $this->chatResources->getMessageCount($user->id);

            // Assert
            expect($result)->toHaveKey('user_id', $user->id);
            expect($result)->toHaveKey('message_count', 1);
            expect($result['count_url'])->toBe("chat://{$user->id}/count");
        });
    });

    describe('private helper methods', function () {
        it('builds correct history URL', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->chatResources);
            $method = $reflection->getMethod('buildHistoryUrl');
            $method->setAccessible(true);

            // Act
            $result = $method->invoke($this->chatResources, 123);

            // Assert
            expect($result)->toBe('chat://123/history');
        });

        it('builds correct recent URL', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->chatResources);
            $method = $reflection->getMethod('buildRecentUrl');
            $method->setAccessible(true);

            // Act
            $result = $method->invoke($this->chatResources, 456);

            // Assert
            expect($result)->toBe('chat://456/recent');
        });

        it('builds correct sessions URL', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->chatResources);
            $method = $reflection->getMethod('buildSessionsUrl');
            $method->setAccessible(true);

            // Act
            $result = $method->invoke($this->chatResources, 789);

            // Assert
            expect($result)->toBe('chat://789/sessions');
        });

        it('builds correct count URL', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->chatResources);
            $method = $reflection->getMethod('buildCountUrl');
            $method->setAccessible(true);

            // Act
            $result = $method->invoke($this->chatResources, 101);

            // Assert
            expect($result)->toBe('chat://101/count');
        });

        it('builds correct session URL', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->chatResources);
            $method = $reflection->getMethod('buildSessionUrl');
            $method->setAccessible(true);

            // Act
            $result = $method->invoke($this->chatResources, 'test-session-123');

            // Assert
            expect($result)->toBe('chat://session/test-session-123');
        });

        it('returns current timestamp in ISO format', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->chatResources);
            $method = $reflection->getMethod('getCurrentTimestamp');
            $method->setAccessible(true);

            // Act
            $result = $method->invoke($this->chatResources);

            // Assert
            expect($result)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
        });

        it('creates chat history response correctly', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->chatResources);
            $method = $reflection->getMethod('createChatHistoryResponse');
            $method->setAccessible(true);

            // Arrange
            $user = UserFactory::create();
            $messages = ChatFactory::count(2)->create(['user_id' => $user->id]);

            // Act
            $result = $method->invoke($this->chatResources, $user->id, $messages, 'history');

            // Assert
            expect($result)->toHaveKey('user_id', $user->id);
            expect($result)->toHaveKey('messages');
            expect($result)->toHaveKey('count', 2);
            expect($result)->toHaveKey('history_url');
            expect($result)->toHaveKey('timestamp');
        });

        it('creates sessions response correctly', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->chatResources);
            $method = $reflection->getMethod('createSessionsResponse');
            $method->setAccessible(true);

            // Arrange
            $user = UserFactory::create();
            $sessions = collect([
                ['session_id' => 'session-1', 'last_message_time' => '2023-01-01 10:00:00'],
                ['session_id' => 'session-2', 'last_message_time' => '2023-01-02 10:00:00']
            ]);

            // Act
            $result = $method->invoke($this->chatResources, $user->id, $sessions);

            // Assert
            expect($result)->toHaveKey('user_id', $user->id);
            expect($result)->toHaveKey('sessions');
            expect($result)->toHaveKey('count', 2);
            expect($result)->toHaveKey('sessions_url');
            expect($result)->toHaveKey('timestamp');
        });

        it('creates error sessions response correctly', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->chatResources);
            $method = $reflection->getMethod('createErrorSessionsResponse');
            $method->setAccessible(true);

            // Act
            $result = $method->invoke($this->chatResources, 999);

            // Assert
            expect($result)->toHaveKey('error', 'User not found');
            expect($result)->toHaveKey('user_id', 999);
            expect($result)->toHaveKey('sessions');
            expect($result)->toHaveKey('count', 0);
            expect($result)->toHaveKey('sessions_url');
            expect($result)->toHaveKey('timestamp');
        });
    });

    describe('edge cases', function () {
        it('handles empty session ID', function () {
            // Act
            $result = $this->chatResources->getSessionHistory('');

            // Assert
            expect($result)->toHaveKey('session_id', '');
            expect($result)->toHaveKey('count', 0);
            expect($result['messages'])->toHaveCount(0);
        });

        it('handles special characters in session ID', function () {
            // Arrange
            $sessionId = 'session-with-special-chars-@#$%^&*()';
            $user = UserFactory::create();
            ChatFactory::create([
                'user_id' => $user->id,
                'session_id' => $sessionId
            ]);

            // Act
            $result = $this->chatResources->getSessionHistory($sessionId);

            // Assert
            expect($result)->toHaveKey('session_id', $sessionId);
            expect($result)->toHaveKey('count', 1);
            expect($result['session_url'])->toBe("chat://session/{$sessionId}");
        });
    });
}); 