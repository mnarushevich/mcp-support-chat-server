<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';

use App\Mcp\Prompts\SupportPrompts;

describe('SupportPrompts', function () {
    beforeEach(function () {
        $this->supportPrompts = new SupportPrompts();
    });

    describe('generateSupportGreeting', function () {
        it('generates basic greeting without user name and issue type', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting();

            // Assert
            expect($result)->toBeArray();
            expect($result)->toHaveCount(1);
            expect($result[0])->toHaveKey('role', 'assistant');
            expect($result[0])->toHaveKey('content');
            
            $content = $result[0]['content'];
            expect($content)->toContain('Hello! Thank you for contacting our support team.');
            expect($content)->toContain("I'm here to help you resolve this as quickly as possible.");
            expect($content)->toContain("Could you please provide more details about what you're experiencing?");
        });

        it('generates greeting with user name only', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('John Doe');

            // Assert
            expect($result)->toBeArray();
            expect($result)->toHaveCount(1);
            expect($result[0])->toHaveKey('role', 'assistant');
            
            $content = $result[0]['content'];
            expect($content)->toContain('Hello John Doe! Thank you for contacting our support team.');
            expect($content)->toContain("I'm here to help you resolve this as quickly as possible.");
            expect($content)->toContain("Could you please provide more details about what you're experiencing?");
        });

        it('generates greeting with issue type only', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('', 'login problems');

            // Assert
            expect($result)->toBeArray();
            expect($result)->toHaveCount(1);
            expect($result[0])->toHaveKey('role', 'assistant');
            
            $content = $result[0]['content'];
            expect($content)->toContain('Hello! Thank you for contacting our support team.');
            expect($content)->toContain("I understand you're experiencing an issue with login problems.");
            expect($content)->toContain("I'm here to help you resolve this as quickly as possible.");
            expect($content)->toContain("Could you please provide more details about what you're experiencing?");
        });

        it('generates greeting with both user name and issue type', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('Jane Smith', 'billing issues');

            // Assert
            expect($result)->toBeArray();
            expect($result)->toHaveCount(1);
            expect($result[0])->toHaveKey('role', 'assistant');
            
            $content = $result[0]['content'];
            expect($content)->toContain('Hello Jane Smith! Thank you for contacting our support team.');
            expect($content)->toContain("I understand you're experiencing an issue with billing issues.");
            expect($content)->toContain("I'm here to help you resolve this as quickly as possible.");
            expect($content)->toContain("Could you please provide more details about what you're experiencing?");
        });

        it('handles empty user name', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('');

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain('Hello! Thank you for contacting our support team.');
            expect($content)->not->toContain('Hello ! Thank you for contacting our support team.');
        });

        it('handles empty issue type', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('John', '');

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain('Hello John! Thank you for contacting our support team.');
            expect($content)->not->toContain("I understand you're experiencing an issue with .");
        });

        it('handles zero string as user name', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('0');

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain('Hello! Thank you for contacting our support team.');
            expect($content)->not->toContain('Hello 0! Thank you for contacting our support team.');
        });

        it('handles zero string as issue type', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('John', '0');

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain('Hello John! Thank you for contacting our support team.');
            expect($content)->not->toContain("I understand you're experiencing an issue with 0.");
        });

        it('handles whitespace-only user name', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('   ');

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain('Hello    ! Thank you for contacting our support team.');
        });

        it('handles whitespace-only issue type', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('John', '   ');

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain('Hello John! Thank you for contacting our support team.');
            expect($content)->toContain("I understand you're experiencing an issue with    .");
        });

        it('handles special characters in user name', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('José O\'Connor');

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain('Hello José O\'Connor! Thank you for contacting our support team.');
        });

        it('handles special characters in issue type', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('John', 'API & database connectivity');

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain("I understand you're experiencing an issue with API & database connectivity.");
        });

        it('handles very long user name', function () {
            // Act
            $longName = str_repeat('A', 100);
            $result = $this->supportPrompts->generateSupportGreeting($longName);

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain("Hello {$longName}! Thank you for contacting our support team.");
        });

        it('handles very long issue type', function () {
            // Act
            $longIssue = str_repeat('very long issue description ', 10);
            $result = $this->supportPrompts->generateSupportGreeting('John', $longIssue);

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain("I understand you're experiencing an issue with {$longIssue}.");
        });
    });

    describe('private helper methods', function () {
        it('builds base greeting correctly without user name', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->supportPrompts);
            $method = $reflection->getMethod('buildBaseGreeting');
            $method->setAccessible(true);

            // Act
            $result = $method->invoke($this->supportPrompts, '');

            // Assert
            expect($result)->toBe('Hello! Thank you for contacting our support team.');
        });

        it('builds base greeting correctly with user name', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->supportPrompts);
            $method = $reflection->getMethod('buildBaseGreeting');
            $method->setAccessible(true);

            // Act
            $result = $method->invoke($this->supportPrompts, 'John Doe');

            // Assert
            expect($result)->toBe('Hello John Doe! Thank you for contacting our support team.');
        });

        it('adds issue type context correctly', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->supportPrompts);
            $method = $reflection->getMethod('addIssueTypeContext');
            $method->setAccessible(true);

            // Arrange
            $baseGreeting = 'Hello John! Thank you for contacting our support team.';

            // Act
            $result = $method->invoke($this->supportPrompts, $baseGreeting, 'login problems');

            // Assert
            expect($result)->toBe('Hello John! Thank you for contacting our support team. I understand you\'re experiencing an issue with login problems.');
        });

        it('does not add issue type context for empty issue type', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->supportPrompts);
            $method = $reflection->getMethod('addIssueTypeContext');
            $method->setAccessible(true);

            // Arrange
            $baseGreeting = 'Hello John! Thank you for contacting our support team.';

            // Act
            $result = $method->invoke($this->supportPrompts, $baseGreeting, '');

            // Assert
            expect($result)->toBe($baseGreeting);
        });

        it('does not add issue type context for zero string', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->supportPrompts);
            $method = $reflection->getMethod('addIssueTypeContext');
            $method->setAccessible(true);

            // Arrange
            $baseGreeting = 'Hello John! Thank you for contacting our support team.';

            // Act
            $result = $method->invoke($this->supportPrompts, $baseGreeting, '0');

            // Assert
            expect($result)->toBe($baseGreeting);
        });

        it('validates strings correctly', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->supportPrompts);
            $method = $reflection->getMethod('isValidString');
            $method->setAccessible(true);

            // Test valid strings
            expect($method->invoke($this->supportPrompts, 'John'))->toBe(true);
            expect($method->invoke($this->supportPrompts, 'login problems'))->toBe(true);
            expect($method->invoke($this->supportPrompts, ' '))->toBe(true);
            expect($method->invoke($this->supportPrompts, '0.1'))->toBe(true);

            // Test invalid strings
            expect($method->invoke($this->supportPrompts, ''))->toBe(false);
            expect($method->invoke($this->supportPrompts, '0'))->toBe(false);
        });
    });

    describe('edge cases', function () {
        it('handles null-like strings', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('null', 'undefined');

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain('Hello null! Thank you for contacting our support team.');
            expect($content)->toContain("I understand you're experiencing an issue with undefined.");
        });

        it('handles numeric strings', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('123', '456');

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain('Hello 123! Thank you for contacting our support team.');
            expect($content)->toContain("I understand you're experiencing an issue with 456.");
        });

        it('handles HTML-like strings', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('<script>alert("test")</script>', '<div>issue</div>');

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain('Hello <script>alert("test")</script>! Thank you for contacting our support team.');
            expect($content)->toContain("I understand you're experiencing an issue with <div>issue</div>.");
        });

        it('handles emoji in user name', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('John 😊');

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain('Hello John 😊! Thank you for contacting our support team.');
        });

        it('handles emoji in issue type', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('John', 'bug 🐛');

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain("I understand you're experiencing an issue with bug 🐛.");
        });

        it('handles unicode characters', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('José María', 'café & résumé');

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain('Hello José María! Thank you for contacting our support team.');
            expect($content)->toContain("I understand you're experiencing an issue with café & résumé.");
        });

        it('maintains consistent response structure', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('John', 'login');

            // Assert
            expect($result)->toBeArray();
            expect($result)->toHaveCount(1);
            expect($result[0])->toHaveKey('role');
            expect($result[0])->toHaveKey('content');
            expect($result[0]['role'])->toBe('assistant');
            expect($result[0]['content'])->toBeString();
            expect(strlen($result[0]['content']))->toBeGreaterThan(0);
        });

        it('always includes the standard closing phrases', function () {
            // Act
            $result = $this->supportPrompts->generateSupportGreeting('John', 'login');

            // Assert
            $content = $result[0]['content'];
            expect($content)->toContain("I'm here to help you resolve this as quickly as possible.");
            expect($content)->toContain("Could you please provide more details about what you're experiencing?");
        });
    });
}); 