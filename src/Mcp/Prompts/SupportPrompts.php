<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use PhpMcp\Server\Attributes\McpPrompt;

/**
 * Support prompt generator for MCP server
 */
class SupportPrompts
{
    /**
     * Generate a support greeting prompt
     *
     * @param string $userName   The name of the user (optional)
     * @param string $issueType  The type of issue being reported (optional)
     *
     * @return array<int, array<string, string>> Array containing the assistant message
     */
    #[McpPrompt(
        name: 'support_greeting',
        description: 'Generate a professional support greeting'
    )]
    public function generateSupportGreeting(string $userName = '', string $issueType = ''): array
    {
        $greeting = $this->buildBaseGreeting($userName);
        $greeting = $this->addIssueTypeContext($greeting, $issueType);
        $greeting .= " I'm here to help you resolve this as quickly as possible. "
            . "Could you please provide more details about what you're experiencing?";

        return [
            [
                'role' => 'assistant',
                'content' => $greeting
            ]
        ];
    }

    /**
     * Build the base greeting with optional user name
     *
     * @param string $userName The name of the user
     *
     * @return string The base greeting
     */
    private function buildBaseGreeting(string $userName): string
    {
        $greeting = 'Hello';

        if ($this->isValidString($userName)) {
            $greeting .= ' ' . $userName;
        }

        return $greeting . '! Thank you for contacting our support team.';
    }

    /**
     * Add issue type context to the greeting if provided
     *
     * @param string $greeting   The current greeting
     * @param string $issueType  The type of issue
     *
     * @return string The greeting with issue context
     */
    private function addIssueTypeContext(string $greeting, string $issueType): string
    {
        if ($this->isValidString($issueType)) {
            $greeting .= sprintf(
                " I understand you're experiencing an issue with %s.",
                $issueType
            );
        }

        return $greeting;
    }

    /**
     * Check if a string is valid (not empty and not '0')
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is valid
     */
    private function isValidString(string $value): bool
    {
        return $value !== '' && $value !== '0';
    }
}
