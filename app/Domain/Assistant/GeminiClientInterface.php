<?php

namespace JobMarket\Domain\Assistant;

interface GeminiClientInterface
{
    /**
     * Send contents to Gemini API and receive structured response.
     *
     * @param array $contents Array of contents: [['role' => 'user'|'model', 'parts' => [['text' => '...']]]]
     * @param string|null $systemInstruction Fixed server-owned system prompt
     * @return GeminiResponse
     * @throws Exceptions\GeminiException
     */
    public function generateContent(array $contents, ?string $systemInstruction = null): GeminiResponse;

    /**
     * Check if the Gemini client is configured and enabled.
     */
    public function isAvailable(): bool;
}
