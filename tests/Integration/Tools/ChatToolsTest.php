<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';

use App\Factories\ChatFactory;
use App\Factories\UserFactory;
use App\Mcp\Tools\ChatTools;
use App\Models\Chat;
use App\Models\User;

beforeEach(function () {
    Chat::query()->delete();
    User::query()->delete();
});

describe('ChatTools', function () {
    beforeEach(function () {
        $this->chatTools = new ChatTools();
    });

    describe('getChatHistory', function () {
        it('returns chat history when user exists', function () {
            // Arrange
            $user = UserFactory::create();
           ChatFactory::count(3)->create(['user_id' => $user->id]);

            // Act
            $result = $this->chatTools->getChatHistory($user->id);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('messages');
            expect($result)->toHaveKey('count', 3);
            expect($result)->toHaveKey('user_id', $user->id);
            expect($result)->toHaveKey('limit', 50);
            expect($result)->toHaveKey('offset', 0);
            expect($result['messages'])->toHaveCount(3);
        });

        it('returns error when user does not exist', function () {
            // Act
            $result = $this->chatTools->getChatHistory(999);

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'User not found');
        });

        it('respects limit parameter', function () {
            // Arrange
            $user = UserFactory::create();
            ChatFactory::count(5)->create(['user_id' => $user->id]);

            // Act
            $result = $this->chatTools->getChatHistory($user->id, 3);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['messages'])->toHaveCount(3);
            expect($result)->toHaveKey('limit', 3);
        });

        it('respects offset parameter', function () {
            // Arrange
            $user = UserFactory::create();
            ChatFactory::count(5)->create(['user_id' => $user->id]);

            // Act
            $result = $this->chatTools->getChatHistory($user->id, 3, 2);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['messages'])->toHaveCount(3);
            expect($result)->toHaveKey('offset', 2);
        });

        it('returns empty messages when user has no chat history', function () {
            // Arrange
            $user = UserFactory::create();

            // Act
            $result = $this->chatTools->getChatHistory($user->id);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['messages'])->toHaveCount(0);
            expect($result)->toHaveKey('count', 0);
        });
    });

    describe('getRecentMessages', function () {
        it('returns recent messages when user exists', function () {
            // Arrange
            $user = UserFactory::create();
            $messages = ChatFactory::count(5)->create(['user_id' => $user->id]);

            // Act
            $result = $this->chatTools->getRecentMessages($user->id);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('messages');
            expect($result)->toHaveKey('count', 5);
            expect($result)->toHaveKey('user_id', $user->id);
            expect($result['messages'])->toHaveCount(5);
        });

        it('returns error when user does not exist', function () {
            // Act
            $result = $this->chatTools->getRecentMessages(999);

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'User not found');
            expect($result)->toHaveKey('user_id', 999);
        });

        it('respects limit parameter', function () {
            // Arrange
            $user = UserFactory::create();
            ChatFactory::count(5)->create(['user_id' => $user->id]);

            // Act
            $result = $this->chatTools->getRecentMessages($user->id, 3);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['messages'])->toHaveCount(3);
        });

        it('returns empty messages when user has no chat history', function () {
            // Arrange
            $user = UserFactory::create();

            // Act
            $result = $this->chatTools->getRecentMessages($user->id);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['messages'])->toHaveCount(0);
            expect($result)->toHaveKey('count', 0);
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
            $result = $this->chatTools->getSessionHistory($sessionId);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('messages');
            expect($result)->toHaveKey('count', 3);
            expect($result)->toHaveKey('session_id', $sessionId);
            expect($result['messages'])->toHaveCount(3);
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
            $result = $this->chatTools->getSessionHistory($sessionId, 3);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['messages'])->toHaveCount(3);
        });

        it('returns empty messages when session does not exist', function () {
            // Act
            $result = $this->chatTools->getSessionHistory('nonexistent-session');

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['messages'])->toHaveCount(0);
            expect($result)->toHaveKey('count', 0);
        });
    });

    describe('addChatMessage', function () {
        it('creates message successfully with valid data', function () {
            // Arrange
            $user = UserFactory::create();

            // Act
            $result = $this->chatTools->addChatMessage(
                $user->id,
                'Hello, this is a test message',
                'user',
                'test-session-123'
            );

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('message');
            expect($result['message']['user_id'])->toBe($user->id);
            expect($result['message']['message'])->toBe('Hello, this is a test message');
            expect($result['message']['sender_type'])->toBe('user');
            expect($result['message']['session_id'])->toBe('test-session-123');
        });

        it('creates message successfully without session ID', function () {
            // Arrange
            $user = UserFactory::create();

            // Act
            $result = $this->chatTools->addChatMessage(
                $user->id,
                'Hello, this is a test message',
                'agent'
            );

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['message']['session_id'])->toBeNull();
        });

        it('returns error for invalid sender type', function () {
            // Arrange
            $user = UserFactory::create();

            // Act
            $result = $this->chatTools->addChatMessage(
                $user->id,
                'Hello, this is a test message',
                'invalid_sender_type'
            );

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'Invalid sender type. Must be user, agent, or bot');
        });

        it('returns error for empty message', function () {
            // Arrange
            $user = UserFactory::create();

            // Act
            $result = $this->chatTools->addChatMessage(
                $user->id,
                '',
                'user'
            );

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'Message cannot be empty');
        });

        it('returns error for whitespace-only message', function () {
            // Arrange
            $user = UserFactory::create();

            // Act
            $result = $this->chatTools->addChatMessage(
                $user->id,
                '   ',
                'user'
            );

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'Message cannot be empty');
        });

        it('accepts all valid sender types', function () {
            // Arrange
            $user = UserFactory::create();
            $validSenderTypes = ['user', 'agent', 'bot'];

            foreach ($validSenderTypes as $senderType) {
                // Act
                $result = $this->chatTools->addChatMessage(
                    $user->id,
                    "Test message from {$senderType}",
                    $senderType
                );

                // Assert
                expect($result)->toHaveKey('success', true);
                expect($result['message']['sender_type'])->toBe($senderType);
            }
        });
    });

    describe('searchChatMessages', function () {
        it('finds messages containing search query', function () {
            // Arrange
            $user = UserFactory::create();
            ChatFactory::create([
                'user_id' => $user->id,
                'message' => 'Hello world, how are you?'
            ]);
            ChatFactory::create([
                'user_id' => $user->id,
                'message' => 'This is a different message'
            ]);
            ChatFactory::create([
                'user_id' => $user->id,
                'message' => 'Hello there, nice to meet you'
            ]);

            // Act
            $result = $this->chatTools->searchChatMessages($user->id, 'hello');

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('messages');
            expect($result)->toHaveKey('count', 2);
            expect($result)->toHaveKey('user_id', $user->id);
            expect($result)->toHaveKey('query', 'hello');
            expect($result['messages'])->toHaveCount(2);
        });

        it('returns error for short search query', function () {
            // Arrange
            $user = UserFactory::create();

            // Act
            $result = $this->chatTools->searchChatMessages($user->id, 'a');

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'Search query must be at least 2 characters long');
        });

        it('returns error when user does not exist', function () {
            // Act
            $result = $this->chatTools->searchChatMessages(999, 'test');

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'User not found');
            expect($result)->toHaveKey('user_id', 999);
        });

        it('respects limit parameter', function () {
            // Arrange
            $user = UserFactory::create();
            ChatFactory::count(5)->create([
                'user_id' => $user->id,
                'message' => 'Test message with common word'
            ]);

            // Act
            $result = $this->chatTools->searchChatMessages($user->id, 'test', 3);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['messages'])->toHaveCount(3);
        });

        it('returns empty results when no matches found', function () {
            // Arrange
            $user = UserFactory::create();
            ChatFactory::create([
                'user_id' => $user->id,
                'message' => 'This is a test message'
            ]);

            // Act
            $result = $this->chatTools->searchChatMessages($user->id, 'nonexistent');

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['messages'])->toHaveCount(0);
            expect($result)->toHaveKey('count', 0);
        });
    });

    describe('getActiveSessions', function () {
        it('returns active sessions when user has sessions', function () {
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
            $result = $this->chatTools->getActiveSessions($user->id);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('sessions');
            expect($result)->toHaveKey('count', 2);
            expect($result)->toHaveKey('user_id', $user->id);
            expect($result['sessions'])->toHaveCount(2);
        });

        it('returns error when user does not exist', function () {
            // Act
            $result = $this->chatTools->getActiveSessions(999);

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'User not found');
            expect($result)->toHaveKey('user_id', 999);
        });

        it('returns empty sessions when user has no sessions', function () {
            // Arrange
            $user = UserFactory::create();

            // Act
            $result = $this->chatTools->getActiveSessions($user->id);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result['sessions'])->toHaveCount(0);
            expect($result)->toHaveKey('count', 0);
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
            $result = $this->chatTools->getActiveSessions($user->id);

            // Assert
            expect($result)->toHaveKey('success', true);
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
            $result = $this->chatTools->getMessageCount($user->id);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('count', 5);
            expect($result)->toHaveKey('user_id', $user->id);
        });

        it('returns zero count when user has no messages', function () {
            // Arrange
            $user = UserFactory::create();

            // Act
            $result = $this->chatTools->getMessageCount($user->id);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('count', 0);
        });

        it('returns zero count when user does not exist', function () {
            // Act
            $result = $this->chatTools->getMessageCount(999);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('count', 0);
            expect($result)->toHaveKey('user_id', 999);
        });
    });

    describe('getMessageById', function () {
        it('returns message when message exists', function () {
            // Arrange
            $message = ChatFactory::create([
                'message' => 'Test message content',
                'sender_type' => 'user'
            ]);

            // Act
            $result = $this->chatTools->getMessageById($message->id);

            // Assert
            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('message');
            expect($result['message']['id'])->toBe($message->id);
            expect($result['message']['message'])->toBe('Test message content');
            expect($result['message']['sender_type'])->toBe('user');
        });

        it('returns error when message does not exist', function () {
            // Act
            $result = $this->chatTools->getMessageById(999);

            // Assert
            expect($result)->toHaveKey('success', false);
            expect($result)->toHaveKey('error', 'Message not found');
            expect($result)->toHaveKey('message_id', 999);
        });
    });
}); 