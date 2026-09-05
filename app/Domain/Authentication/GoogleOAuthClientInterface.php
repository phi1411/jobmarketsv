<?php

namespace JobMarket\Domain\Authentication;

interface GoogleOAuthClientInterface
{
    /**
     * Get Google OAuth 2.0 authorization URL.
     */
    public function getAuthorizationUrl(string $state, string $nonce): string;

    /**
     * Exchange authorization code for token response.
     * Returns array containing 'access_token', 'id_token', etc.
     */
    public function exchangeCode(string $code): array;

    /**
     * Verify ID token and validate standard OIDC claims.
     * Validates signature, iss, aud, exp, nonce, sub, email_verified.
     * Returns decoded claims array.
     */
    public function verifyIdToken(string $idToken, string $expectedNonce): array;
}
