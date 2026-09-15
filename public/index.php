<?php

declare(strict_types=1);

use Boneblaze\SignupLfchdOrg\Services\EntraAuth;
use Boneblaze\SignupLfchdOrg\Services\Csrf;
use Boneblaze\SignupLfchdOrg\Controllers\Admin\EventController;
use Boneblaze\SignupLfchdOrg\Controllers\Admin\SlotController;
use Boneblaze\SignupLfchdOrg\Controllers\Admin\DashboardController;
use Boneblaze\SignupLfchdOrg\Controllers\Public\EventController as PublicEventController;
use Boneblaze\SignupLfchdOrg\Controllers\Admin\RegistrationController;
use Boneblaze\SignupLfchdOrg\Controllers\Admin\QuestionController;
use Boneblaze\SignupLfchdOrg\Controllers\Webhook\TwilioController;
use Dotenv\Dotenv;

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_samesite', 'Lax');

session_start();

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$path = parse_url(
    $_SERVER['REQUEST_URI'],
    PHP_URL_PATH
);

$method = $_SERVER['REQUEST_METHOD'];

$isTwilioWebhook =
    $method === 'POST'
    && $path === '/webhooks/twilio/incoming';

if (
    $method === 'POST'
    && !$isTwilioWebhook
) {
    Csrf::enforce();
}

/*
 * Twilio webhook.
 *
 * This route is intentionally exempt from the application's CSRF
 * protection because Twilio cannot provide our session CSRF token.
 * TwilioController independently validates the X-Twilio-Signature.
 */
if ($isTwilioWebhook) {
    (new TwilioController())->incoming();
    exit;
}

/*
 * Authentication routes
 */
if (
    $method === 'GET'
    && $path === '/login'
) {
    $auth = new EntraAuth();
    $auth->authenticate();

    $returnUrl =
        $_SESSION['login_return_url']
        ?? '/admin';

    unset($_SESSION['login_return_url']);

    header('Location: ' . $returnUrl);
    exit;
}

if (
    $method === 'GET'
    && $path === '/auth/callback'
) {
    $auth = new EntraAuth();
    $auth->authenticate();

    $returnUrl =
        $_SESSION['login_return_url']
        ?? '/admin';

    unset($_SESSION['login_return_url']);

    header('Location: ' . $returnUrl);
    exit;
}

if (
    $method === 'GET'
    && $path === '/logout'
) {
    EntraAuth::logout();

    header('Location: /');
    exit;
}

/*
 * Public legal/compliance pages
 */
if (
    $method === 'GET'
    && $path === '/privacy'
) {
    require dirname(__DIR__) . '/templates/public/privacy.php';
    exit;
}

if (
    $method === 'GET'
    && $path === '/terms'
) {
    require dirname(__DIR__) . '/templates/public/terms.php';
    exit;
}

/*
 * Public landing page
 */
if (
    $method === 'GET'
    && $path === '/'
) {
    require dirname(__DIR__) . '/templates/public/home.php';
    exit;
}

/*
 * Public routes
 */

if (
    preg_match(
        '#^/event/([A-Za-z0-9]+)$#',
        $path,
        $matches
    )
    && $method === 'GET'
) {
    (new PublicEventController())->show(
        $matches[1]
    );

    exit;
}

if (
    preg_match(
        '#^/event/([A-Za-z0-9]+)/register$#',
        $path,
        $matches
    )
    && $method === 'POST'
) {
    (new PublicEventController())->register(
        $matches[1]
    );

    exit;
}

if (
    preg_match(
        '#^/event/([A-Za-z0-9]+)/confirmation/([A-Za-z0-9]+)$#',
        $path,
        $matches
    )
    && $method === 'GET'
) {
    (new PublicEventController())->confirmation(
        $matches[1],
        $matches[2]
    );

    exit;
}

if (
    preg_match(
        '#^/event/([A-Za-z0-9]+)/confirmation/([A-Za-z0-9]+)/cancel$#',
        $path,
        $matches
    )
    && $method === 'POST'
) {
    (new PublicEventController())->cancel(
        $matches[1],
        $matches[2]
    );

    exit;
}

/*
 * All /admin routes require an authenticated user.
 */
if (str_starts_with($path, '/admin')) {
    EntraAuth::requireLogin();
}

/*
 * Dashboard - available to both roles.
 */
if (
    $path === '/admin'
    && $method === 'GET'
) {
    (new DashboardController())->index();
    exit;
}

/*
 * Registration routes - Administrator OR Registration Manager.
 */
if (
    preg_match(
        '#^/admin/events/(\d+)/registrations$#',
        $path,
        $matches
    )
    && $method === 'GET'
) {
    EntraAuth::requireRegistrationManager();

    (new RegistrationController())->index(
        (int) $matches[1]
    );

    exit;
}

if (
    preg_match(
        '#^/admin/events/(\d+)/registrations/export$#',
        $path,
        $matches
    )
    && $method === 'GET'
) {
    EntraAuth::requireRegistrationManager();

    (new RegistrationController())->exportCsv(
        (int) $matches[1]
    );

    exit;
}

if (
    preg_match(
        '#^/admin/events/(\d+)/registrations/(\d+)/edit$#',
        $path,
        $matches
    )
) {
    EntraAuth::requireRegistrationManager();

    $controller =
        new RegistrationController();

    $eventId =
        (int) $matches[1];

    $registrationId =
        (int) $matches[2];

    if ($method === 'GET') {
        $controller->edit(
            $eventId,
            $registrationId
        );

        exit;
    }

    if ($method === 'POST') {
        $controller->update(
            $eventId,
            $registrationId
        );

        exit;
    }
}

if (
    preg_match(
        '#^/admin/events/(\d+)/registrations/(\d+)/resend-confirmation$#',
        $path,
        $matches
    )
    && $method === 'POST'
) {
    EntraAuth::requireRegistrationManager();

    (new RegistrationController())->resendConfirmation(
        (int) $matches[1],
        (int) $matches[2]
    );

    exit;
}

if (
    preg_match(
        '#^/admin/events/(\d+)/registrations/(\d+)/cancel$#',
        $path,
        $matches
    )
    && $method === 'POST'
) {
    EntraAuth::requireRegistrationManager();

    (new RegistrationController())->cancel(
        (int) $matches[1],
        (int) $matches[2]
    );

    exit;
}

if (
    preg_match(
        '#^/admin/events/(\d+)/registrations/(\d+)/restore$#',
        $path,
        $matches
    )
    && $method === 'POST'
) {
    EntraAuth::requireRegistrationManager();

    (new RegistrationController())->restore(
        (int) $matches[1],
        (int) $matches[2]
    );

    exit;
}

/*
 * Administrator-only routes.
 */
if (
    $path === '/admin/events/create'
    && $method === 'GET'
) {
    EntraAuth::requireAdmin();

    (new EventController())->create();
    exit;
}

if (
    $path === '/admin/events/create'
    && $method === 'POST'
) {
    EntraAuth::requireAdmin();

    (new EventController())->store();
    exit;
}

if (
    preg_match(
        '#^/admin/events/(\d+)/edit$#',
        $path,
        $matches
    )
) {
    EntraAuth::requireAdmin();

    $eventId = (int) $matches[1];

    $controller = new EventController();

    if ($method === 'GET') {
        $controller->edit($eventId);
        exit;
    }

    if ($method === 'POST') {
        $controller->update($eventId);
        exit;
    }
}

if (
    preg_match(
        '#^/admin/events/(\d+)/slots$#',
        $path,
        $matches
    )
) {
    EntraAuth::requireAdmin();

    $eventId = (int) $matches[1];

    $controller = new SlotController();

    if ($method === 'GET') {
        $controller->index($eventId);
        exit;
    }

    if ($method === 'POST') {
        $controller->update($eventId);
        exit;
    }
}

if (
    preg_match(
        '#^/admin/events/(\d+)/questions$#',
        $path,
        $matches
    )
) {
    EntraAuth::requireAdmin();

    $eventId = (int) $matches[1];

    $controller = new QuestionController();

    if ($method === 'GET') {
        $controller->index($eventId);
        exit;
    }

    if ($method === 'POST') {
        $controller->store($eventId);
        exit;
    }
}

if (
    preg_match(
        '#^/admin/events/(\d+)/questions/(\d+)/edit$#',
        $path,
        $matches
    )
    && $method === 'POST'
) {
    EntraAuth::requireAdmin();

    (new QuestionController())->update(
        (int) $matches[1],
        (int) $matches[2]
    );

    exit;
}

if (
    preg_match(
        '#^/admin/events/(\d+)/open$#',
        $path,
        $matches
    )
    && $method === 'POST'
) {
    EntraAuth::requireAdmin();

    (new EventController())->open(
        (int) $matches[1]
    );

    exit;
}

if (
    preg_match(
        '#^/admin/events/(\d+)/close$#',
        $path,
        $matches
    )
    && $method === 'POST'
) {
    EntraAuth::requireAdmin();

    (new EventController())->close(
        (int) $matches[1]
    );

    exit;
}

if (
    preg_match(
        '#^/admin/events/(\d+)/duplicate$#',
        $path,
        $matches
    )
    && $method === 'POST'
) {
    EntraAuth::requireAdmin();

    (new EventController())->duplicate(
        (int) $matches[1]
    );

    exit;
}

if (
    preg_match(
        '#^/admin/events/(\d+)/delete$#',
        $path,
        $matches
    )
    && $method === 'POST'
) {
    EntraAuth::requireAdmin();

    (new EventController())->delete(
        (int) $matches[1]
    );

    exit;
}

http_response_code(404);

echo '<h1>404</h1>';
echo '<p>Page not found.</p>';
