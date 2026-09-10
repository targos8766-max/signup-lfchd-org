<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Services;

use JakubOnderka\OpenIDConnectClient;
use RuntimeException;

class EntraAuth
{
    private const ROLE_ADMIN = 'Signup.Administrator';
    private const ROLE_REGISTRATION_MANAGER = 'Signup.RegistrationManager';

    private OpenIDConnectClient $oidc;

    public function __construct()
    {
        $tenantId = $_ENV['ENTRA_TENANT_ID'] ?? '';
        $clientId = $_ENV['ENTRA_CLIENT_ID'] ?? '';
        $clientSecret = $_ENV['ENTRA_CLIENT_SECRET'] ?? '';
        $redirectUri = $_ENV['ENTRA_REDIRECT_URI'] ?? '';

        if (
            $tenantId === ''
            || $clientId === ''
            || $clientSecret === ''
            || $redirectUri === ''
        ) {
            throw new RuntimeException(
                'Microsoft Entra configuration is incomplete.'
            );
        }

        $providerUrl =
            'https://login.microsoftonline.com/'
            . $tenantId
            . '/v2.0';

        $this->oidc = new OpenIDConnectClient(
            $providerUrl,
            $clientId,
            $clientSecret
        );

        $this->oidc->setRedirectURL($redirectUri);

        $this->oidc->addScope([
            'openid',
            'profile',
            'email',
        ]);
    }

    public function authenticate(): void
    {
        $this->oidc->authenticate();

        $claims = $this->oidc->getVerifiedClaims();

        if ($claims === null) {
            throw new RuntimeException(
                'Unable to retrieve Microsoft Entra claims.'
            );
        }

        $roles = $claims->roles ?? [];

        if (is_string($roles)) {
            $roles = [$roles];
        }

        if (!is_array($roles)) {
            $roles = [];
        }

        $authorized =
            in_array(
                self::ROLE_ADMIN,
                $roles,
                true
            )
            || in_array(
                self::ROLE_REGISTRATION_MANAGER,
                $roles,
                true
            );

        if (!$authorized) {
            http_response_code(403);

            echo '<h1>Access Denied</h1>';
            echo '<p>Your Microsoft account is authenticated, but is not authorized to access this application.</p>';

            exit;
        }

        session_regenerate_id(true);

        $_SESSION['auth_user'] = [
            'oid' => $claims->oid ?? null,
            'name' => $claims->name ?? null,
            'email' =>
                $claims->preferred_username
                ?? $claims->email
                ?? null,
            'roles' => $roles,
        ];
    }

    public static function check(): bool
    {
        return isset($_SESSION['auth_user'])
            && is_array($_SESSION['auth_user']);
    }

    public static function user(): ?array
    {
        return self::check()
            ? $_SESSION['auth_user']
            : null;
    }

    public static function roles(): array
    {
        if (!self::check()) {
            return [];
        }

        $roles =
            $_SESSION['auth_user']['roles']
            ?? [];

        return is_array($roles)
            ? $roles
            : [];
    }

    public static function hasRole(string $role): bool
    {
        return in_array(
            $role,
            self::roles(),
            true
        );
    }

    public static function isAdministrator(): bool
    {
        return self::hasRole(
            self::ROLE_ADMIN
        );
    }

    public static function isRegistrationManager(): bool
    {
        return self::hasRole(
            self::ROLE_REGISTRATION_MANAGER
        );
    }

    public static function canManageRegistrations(): bool
    {
        return self::isAdministrator()
            || self::isRegistrationManager();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            $_SESSION['login_return_url'] =
                $_SERVER['REQUEST_URI']
                ?? '/admin';

            header('Location: /login');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();

        if (!self::isAdministrator()) {
            http_response_code(403);

            echo '<h1>Access Denied</h1>';
            echo '<p>You do not have permission to manage event configuration.</p>';

            exit;
        }
    }

    public static function requireRegistrationManager(): void
    {
        self::requireLogin();

        if (!self::canManageRegistrations()) {
            http_response_code(403);

            echo '<h1>Access Denied</h1>';
            echo '<p>You do not have permission to manage registrations.</p>';

            exit;
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
