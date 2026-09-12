<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Services;

final class PiiRedactor
{
    public const PLACEHOLDER_PATTERN = '/\[REDACTED_(?:NAME|EMAIL|PHONE|URL|PATH|FILE|DOB|DATE|ADDRESS)\]/';

    public const CONTACT_CUES_PATTERN = '/\b(?:li[êe]n\s+h[ệe](?:\s+qua)?|s[đd]t|s[ốo]\s+[đd]i[ệe]n\s+tho[ạa]i|phone|tel|mobile|hotline|call(?:\s+me)?|contact(?:\s+me)?|zalo|inbox|g[ọo]i(?:\s+cho\s+t[ôo]i)?|nh[ắa]n(?:\s+tin)?|email|mail|qua\s+s[ốo]|qua\s+s[đd]t|qua|s[ốo]|ho[ặa]c|vui\s+l[òo]ng|xin\s+li[êe]n\s+h[ệe]|chi\s+ti[ếe]t|theo\s+s[ốo]|v[àa])\b[:\s]*/ui';

    /**
     * Redacts PII patterns from free-text strings:
     * - Email addresses
     * - Phone numbers (VN and international)
     * - URLs and CV/file storage paths
     * - Date of birth / DOB mentions (only with DOB context or matching known DOB)
     * - Known candidate full name (with unicode word boundary to avoid substring replacement)
     * - Exact street addresses / house numbers
     *
     * Note: Employment & project date ranges (e.g. 2021-2023, 01/2022 - 05/2023) are preserved!
     *
     * Returns trimmed string, or null if string is empty or contains no useful data after redaction.
     */
    public function redact(?string $text, ?string $knownFullName = null, ?string $knownDob = null): ?string
    {
        if ($text === null) {
            return null;
        }

        $cleaned = trim($text);
        if ($cleaned === '') {
            return null;
        }

        // 1. Redact known full name if provided (using unicode boundaries so "An" won't replace "Angular" or "Management")
        if (!empty($knownFullName)) {
            $nameTrimmed = trim($knownFullName);
            if (mb_strlen($nameTrimmed) >= 2) {
                $quoted = preg_quote($nameTrimmed, '/');
                $cleaned = (string)preg_replace('/(?<!\p{L})' . $quoted . '(?!\p{L})/ui', '[REDACTED_NAME]', $cleaned);
            }
        }

        // 2. Redact emails
        $cleaned = (string)preg_replace(
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/u',
            '[REDACTED_EMAIL]',
            $cleaned
        );

        // 3. Redact phone numbers:
        // Vietnamese standard (03x, 05x, 07x, 08x, 09x, 02x, +84) and general international numbers
        $cleaned = (string)preg_replace(
            '/(?:\+?84|0)(?:3[2-9]|5[25689]|7[06-9]|8[1-9]|9[0-9]|2\d)\d{7}\b/',
            '[REDACTED_PHONE]',
            $cleaned
        );
        $cleaned = (string)preg_replace(
            '/\b(?:\+?\d{1,3}[-.\s]?)?\(?\d{2,4}\)?[-.\s]?\d{3,4}[-.\s]?\d{3,4}\b/',
            '[REDACTED_PHONE]',
            $cleaned
        );

        // 4. Redact URLs and CV/file storage paths
        $cleaned = (string)preg_replace(
            '/\bhttps?:\/\/[^\s<>"\'\)]+/iu',
            '[REDACTED_URL]',
            $cleaned
        );
        $cleaned = (string)preg_replace(
            '/\b(?:storage|app|cvs|uploads)[\/\\\\][a-zA-Z0-9_\-\.\/\\\\]+\.(?:pdf|docx?|jpg|jpeg|png)\b/iu',
            '[REDACTED_PATH]',
            $cleaned
        );
        $cleaned = (string)preg_replace(
            '/\b[a-zA-Z0-9_\-]+\.(?:pdf|docx?)\b/iu',
            '[REDACTED_FILE]',
            $cleaned
        );

        // 5. Redact Date of Birth identifiers (ONLY with explicit DOB context or matching known DOB)
        $cleaned = (string)preg_replace(
            '/(?:sinh\s+ng[àa]y|ng[àa]y\s+sinh|dob|date\s+of\s+birth|d\.o\.b|n[aă]m\s+sinh|b-day|birth\s*date)[:\s]*\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}/ui',
            '[REDACTED_DOB]',
            $cleaned
        );

        if (!empty($knownDob)) {
            $dobTrimmed = trim($knownDob);
            if ($dobTrimmed !== '') {
                $quotedDob = preg_quote($dobTrimmed, '/');
                $cleaned = (string)preg_replace('/(?<!\d)' . $quotedDob . '(?!\d)/u', '[REDACTED_DOB]', $cleaned);
                // Also check d/m/Y variation if Y-m-d format
                if (preg_match('/^(\d{4})[-\/\.](\d{1,2})[-\/\.](\d{1,2})$/', $dobTrimmed, $m)) {
                    $dmy = sprintf('%02d/%02d/%04d', (int)$m[3], (int)$m[2], (int)$m[1]);
                    $cleaned = (string)preg_replace('/(?<!\d)' . preg_quote($dmy, '/') . '(?!\d)/u', '[REDACTED_DOB]', $cleaned);
                }
            }
        }

        // 6. Redact exact street addresses and residential markers
        $cleaned = (string)preg_replace(
            '/\b(?:s[ốo]\s+\d+[a-zA-Z]?|ng[õo]\s+\d+|ng[áa]ch\s+\d+|h[ẻe]m\s+\d+|ki[ệê]t\s+\d+)[^,;\n]*(?: đường| phố| quận| huyện| tp| p\.| q\.)?[^,;\n]*/ui',
            '[REDACTED_ADDRESS]',
            $cleaned
        );

        // 7. Collapse repeated whitespaces
        $cleaned = (string)preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = trim($cleaned);

        // 8. Check if remaining text has useful content beyond placeholders and boilerplate contact cues
        $stripped = self::stripPlaceholdersAndContact($cleaned);

        // If no meaningful text remains (e.g. was solely a phone/email/name/contact callout), return null
        if ($stripped === '' || mb_strlen($stripped) < 2) {
            return null;
        }

        return $cleaned;
    }

    /**
     * Checks if string contains any [REDACTED_*] placeholder.
     */
    public static function containsPlaceholder(?string $text): bool
    {
        if ($text === null || $text === '') {
            return false;
        }
        return (bool)preg_match(self::PLACEHOLDER_PATTERN, $text);
    }

    /**
     * Strips all [REDACTED_*] placeholders and boilerplate contact cues from text,
     * returning clean string without leading/trailing punctuation or whitespace.
     */
    public static function stripPlaceholdersAndContact(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $stripped = (string)preg_replace(self::PLACEHOLDER_PATTERN, ' ', $text);
        $stripped = (string)preg_replace(self::CONTACT_CUES_PATTERN, ' ', $stripped);
        $stripped = (string)preg_replace(self::CONTACT_CUES_PATTERN, ' ', $stripped);
        $stripped = (string)preg_replace('/\s+/', ' ', $stripped);
        $stripped = trim($stripped, " \t\n\r\0\x0B,.;:-_()[]/\\");

        return $stripped;
    }

    /**
     * Determines if a token consists solely of placeholders, contact cues, or phone/email.
     */
    public static function isPlaceholderOrContact(?string $token): bool
    {
        if ($token === null) {
            return true;
        }

        $trimmed = trim($token);
        if ($trimmed === '') {
            return true;
        }

        if (self::containsPlaceholder($trimmed)) {
            $clean = self::stripPlaceholdersAndContact($trimmed);
            if ($clean === '' || mb_strlen($clean) < 2) {
                return true;
            }
        }

        // Check if token is raw phone number
        if (preg_match('/^(?:\+?84|0)[\d\s\.\-]{8,}$/', $trimmed)) {
            return true;
        }

        // Check if token is email
        if (preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $trimmed)) {
            return true;
        }

        // Check if token after contact cues stripping is empty
        $clean = self::stripPlaceholdersAndContact($trimmed);
        return ($clean === '' || mb_strlen($clean) < 2);
    }
}
