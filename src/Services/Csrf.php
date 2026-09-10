<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Services;

class Csrf
{
    private static function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function token(): string
    {
        self::ensureSession();

        if (
            !isset($_SESSION['csrf_token'])
            || !is_string($_SESSION['csrf_token'])
        ) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="'
            . htmlspecialchars(
                self::token(),
                ENT_QUOTES,
                'UTF-8'
            )
            . '">';
    }

    public static function validate(?string $token): bool
    {
        self::ensureSession();

        if (
            !isset($_SESSION['csrf_token'])
            || !is_string($_SESSION['csrf_token'])
            || $token === null
        ) {
            return false;
        }

        return hash_equals(
            $_SESSION['csrf_token'],
            $token
        );
    }

    public static function enforce(): void
    {
        $token = $_POST['_csrf'] ?? null;

        if (
            !is_string($token)
            || !self::validate($token)
        ) {
            http_response_code(419);

            echo '<h1>Request Expired</h1>';
            echo '<p>Please go back, refresh the page, and try again.</p>';

            exit;
        }
    }
}