<?php

use Boneblaze\SignupLfchdOrg\Services\EntraAuth;
use Boneblaze\SignupLfchdOrg\Services\Csrf;

$authUser = EntraAuth::user();

$isAdministrator =
    EntraAuth::isAdministrator();

$pageTitle = 'Events | LFCHD Signup';

require __DIR__ . '/partials/header.php';

?>

<div class="container py-4 py-md-5">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">

        <div>
            <h1 class="mb-1">
                Events
            </h1>

            <div class="text-muted">
                <?php if ($isAdministrator): ?>
                    Manage signup events, appointment slots, and registrations.
                <?php else: ?>
                    View events and manage registrations.
                <?php endif; ?>
            </div>
        </div>

    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">
            Event deleted successfully.
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars(
                $_GET['error'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>
    <?php endif; ?>

    <?php if (!$events): ?>

        <div class="card shadow-sm">
            <div class="card-body text-center py-5">

                <h2 class="h4">
                    No events yet
                </h2>

                <?php if ($isAdministrator): ?>

                    <p class="text-muted">
                        Create your first signup event to get started.
                    </p>

                    <a
                        href="/admin/events/create"
                        class="btn btn-primary"
                    >
                        Create Event
                    </a>

                <?php else: ?>

                    <p class="text-muted mb-0">
                        There are currently no signup events available.
                    </p>

                <?php endif; ?>

            </div>
        </div>

    <?php else: ?>

        <!-- Desktop / tablet table -->
        <div class="card shadow-sm d-none d-md-block">

            <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Signups</th>
                        <th>Slots</th>
                        <th style="width:120px;">Actions</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($events as $event): ?>

                        <?php
                        $date = new DateTimeImmutable(
                            $event['event_date']
                        );

                        $start = new DateTimeImmutable(
                            $event['event_date']
                            . ' '
                            . $event['start_time']
                        );

                        $end = new DateTimeImmutable(
                            $event['event_date']
                            . ' '
                            . $event['end_time']
                        );

                        $publicUrl =
                            ($_ENV['APP_URL'] ?? '')
                            . '/event/'
                            . $event['public_slug'];

                        $statusClass = match ($event['status']) {
                            'open' => 'success',
                            'closed' => 'secondary',
                            'cancelled' => 'danger',
                            default => 'warning',
                        };
                        ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= htmlspecialchars(
                                        $event['title'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <?php if (
                                    $isAdministrator
                                    && !empty($event['admin_only'])
                                ): ?>
                                    <span
                                        class="badge text-bg-dark ms-1"
                                        title="This event is hidden from non-administrator users."
                                    >
                                        Admin Only
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($event['location'])): ?>

                                    <div class="small text-muted">
                                        <?= htmlspecialchars(
                                            $event['location'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </div>

                                <?php endif; ?>
                            </td>

                            <td>
                                <?= $date->format('M j, Y') ?>
                            </td>

                            <td>
                                <?= $start->format('g:i A') ?>
                                –
                                <?= $end->format('g:i A') ?>
                            </td>

                            <td>
                                <span class="badge text-bg-<?= $statusClass ?>">
                                    <?= ucfirst(
                                        htmlspecialchars(
                                            $event['status'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <?= (int) $event['registration_count'] ?>
                                /
                                <?= (int) $event['total_capacity'] ?>
                            </td>

                            <td>
                                <?= (int) $event['slot_count'] ?>
                            </td>

                            <td>
                                <div class="dropdown">
                                    <button
                                        class="btn btn-sm btn-outline-primary dropdown-toggle"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        Actions
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">

                                        <li>
                                            <a
                                                class="dropdown-item"
                                                href="/admin/events/<?= (int) $event['id'] ?>/registrations"
                                            >
                                                Registrations
                                            </a>
                                        </li>

                                        <li>
                                            <a
                                                class="dropdown-item"
                                                href="<?= htmlspecialchars(
                                                    $publicUrl,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                                target="_blank"
                                            >
                                                Public Page
                                            </a>
                                        </li>

                                        <li>
                                            <button
                                                type="button"
                                                class="dropdown-item"
                                                onclick="copySignupLink(
                                                    '<?= htmlspecialchars(
                                                        $publicUrl,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>'
                                                )"
                                            >
                                                Copy Signup Link
                                            </button>
                                        </li>

                                        <?php if ($isAdministrator): ?>

                                            <li><hr class="dropdown-divider"></li>

                                            <li>
                                                <a
                                                    class="dropdown-item"
                                                    href="/admin/events/<?= (int) $event['id'] ?>/edit"
                                                >
                                                    Edit Event
                                                </a>
                                            </li>

                                            <li>
                                                <a
                                                    class="dropdown-item"
                                                    href="/admin/events/<?= (int) $event['id'] ?>/slots"
                                                >
                                                    Manage Slots
                                                </a>
                                            </li>

                                            <li>
                                                <a
                                                    class="dropdown-item"
                                                    href="/admin/events/<?= (int) $event['id'] ?>/questions"
                                                >
                                                    Questions
                                                </a>
                                            </li>

                                            <li><hr class="dropdown-divider"></li>

                                            <?php if (
                                                $event['status'] === 'draft'
                                                || $event['status'] === 'closed'
                                            ): ?>

                                                <li>
                                                    <form
                                                        method="post"
                                                        action="/admin/events/<?= (int) $event['id'] ?>/open"
                                                    >
                                                        <?= Csrf::field() ?>

                                                        <button
                                                            type="submit"
                                                            class="dropdown-item text-success"
                                                            onclick="return confirm(
                                                                'Open registration for this event?'
                                                            );"
                                                        >
                                                            Open Registration
                                                        </button>
                                                    </form>
                                                </li>

                                            <?php endif; ?>

                                            <?php if ($event['status'] === 'open'): ?>

                                                <li>
                                                    <form
                                                        method="post"
                                                        action="/admin/events/<?= (int) $event['id'] ?>/close"
                                                    >
                                                        <?= Csrf::field() ?>

                                                        <button
                                                            type="submit"
                                                            class="dropdown-item text-warning"
                                                            onclick="return confirm(
                                                                'Close registration for this event? Existing registrations will remain.'
                                                            );"
                                                        >
                                                            Close Registration
                                                        </button>
                                                    </form>
                                                </li>

                                            <?php endif; ?>

                                            <li>
                                                <form
                                                    method="post"
                                                    action="/admin/events/<?= (int) $event['id'] ?>/duplicate"
                                                >
                                                    <?= Csrf::field() ?>

                                                    <button
                                                        type="submit"
                                                        class="dropdown-item"
                                                        onclick="return confirm('Duplicate this event?');"
                                                    >
                                                        Duplicate Event
                                                    </button>
                                                </form>
                                            </li>

                                            <?php if ($event['status'] === 'draft'): ?>

                                                <li><hr class="dropdown-divider"></li>

                                                <li>
                                                    <form
                                                        method="post"
                                                        action="/admin/events/<?= (int) $event['id'] ?>/delete"
                                                    >
                                                        <?= Csrf::field() ?>

                                                        <button
                                                            type="submit"
                                                            class="dropdown-item text-danger"
                                                            onclick="return confirm(
                                                                'Delete this draft event? This action cannot be undone.'
                                                            );"
                                                        >
                                                            Delete Event
                                                        </button>
                                                    </form>
                                                </li>

                                            <?php endif; ?>

                                        <?php endif; ?>

                                    </ul>
                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

            </table>

        </div>

        <!-- Mobile cards -->
        <div class="d-md-none">

            <?php foreach ($events as $event): ?>

                <?php
                $date = new DateTimeImmutable(
                    $event['event_date']
                );

                $start = new DateTimeImmutable(
                    $event['event_date']
                    . ' '
                    . $event['start_time']
                );

                $end = new DateTimeImmutable(
                    $event['event_date']
                    . ' '
                    . $event['end_time']
                );

                $publicUrl =
                    ($_ENV['APP_URL'] ?? '')
                    . '/event/'
                    . $event['public_slug'];

                $statusClass = match ($event['status']) {
                    'open' => 'success',
                    'closed' => 'secondary',
                    'cancelled' => 'danger',
                    default => 'warning',
                };
                ?>

                <div class="card shadow-sm mb-3">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">

                            <div>
                                <h2 class="h5 mb-1">
                                    <?= htmlspecialchars(
                                        $event['title'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </h2>

                                <?php if (!empty($event['location'])): ?>
                                    <div class="small text-muted">
                                        <?= htmlspecialchars(
                                            $event['location'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <span class="badge text-bg-<?= $statusClass ?>">
                                <?= ucfirst(
                                    htmlspecialchars(
                                        $event['status'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                ) ?>
                            </span>

                        </div>

                        <div class="row g-2 small mb-3">

                            <div class="col-6">
                                <div class="text-muted">Date</div>
                                <div class="fw-semibold">
                                    <?= $date->format('M j, Y') ?>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="text-muted">Time</div>
                                <div class="fw-semibold">
                                    <?= $start->format('g:i A') ?>
                                    –
                                    <?= $end->format('g:i A') ?>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="text-muted">Signups</div>
                                <div class="fw-semibold">
                                    <?= (int) $event['registration_count'] ?>
                                    /
                                    <?= (int) $event['total_capacity'] ?>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="text-muted">Slots</div>
                                <div class="fw-semibold">
                                    <?= (int) $event['slot_count'] ?>
                                </div>
                            </div>

                        </div>

                        <div class="dropdown d-grid">
                            <button
                                class="btn btn-outline-primary dropdown-toggle"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                Event Actions
                            </button>

                            <ul class="dropdown-menu w-100">

                                <li>
                                    <a
                                        class="dropdown-item"
                                        href="/admin/events/<?= (int) $event['id'] ?>/registrations"
                                    >
                                        Registrations
                                    </a>
                                </li>

                                <li>
                                    <a
                                        class="dropdown-item"
                                        href="<?= htmlspecialchars(
                                            $publicUrl,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        target="_blank"
                                    >
                                        Public Page
                                    </a>
                                </li>

                                <li>
                                    <button
                                        type="button"
                                        class="dropdown-item"
                                        onclick="copySignupLink(
                                            '<?= htmlspecialchars(
                                                $publicUrl,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>'
                                        )"
                                    >
                                        Copy Signup Link
                                    </button>
                                </li>

                                <?php if ($isAdministrator): ?>

                                    <li><hr class="dropdown-divider"></li>

                                    <li>
                                        <a
                                            class="dropdown-item"
                                            href="/admin/events/<?= (int) $event['id'] ?>/edit"
                                        >
                                            Edit Event
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            class="dropdown-item"
                                            href="/admin/events/<?= (int) $event['id'] ?>/slots"
                                        >
                                            Manage Slots
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            class="dropdown-item"
                                            href="/admin/events/<?= (int) $event['id'] ?>/questions"
                                        >
                                            Questions
                                        </a>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>

                                    <?php if (
                                        $event['status'] === 'draft'
                                        || $event['status'] === 'closed'
                                    ): ?>

                                        <li>
                                            <form
                                                method="post"
                                                action="/admin/events/<?= (int) $event['id'] ?>/open"
                                            >
                                                <?= Csrf::field() ?>

                                                <button
                                                    type="submit"
                                                    class="dropdown-item text-success"
                                                    onclick="return confirm(
                                                        'Open registration for this event?'
                                                    );"
                                                >
                                                    Open Registration
                                                </button>
                                            </form>
                                        </li>

                                    <?php endif; ?>

                                    <?php if ($event['status'] === 'open'): ?>

                                        <li>
                                            <form
                                                method="post"
                                                action="/admin/events/<?= (int) $event['id'] ?>/close"
                                            >
                                                <?= Csrf::field() ?>

                                                <button
                                                    type="submit"
                                                    class="dropdown-item text-warning"
                                                    onclick="return confirm(
                                                        'Close registration for this event? Existing registrations will remain.'
                                                    );"
                                                >
                                                    Close Registration
                                                </button>
                                            </form>
                                        </li>

                                    <?php endif; ?>

                                    <li>
                                        <form
                                            method="post"
                                            action="/admin/events/<?= (int) $event['id'] ?>/duplicate"
                                        >
                                            <?= Csrf::field() ?>

                                            <button
                                                type="submit"
                                                class="dropdown-item"
                                                onclick="return confirm('Duplicate this event?');"
                                            >
                                                Duplicate Event
                                            </button>
                                        </form>
                                    </li>

                                    <?php if ($event['status'] === 'draft'): ?>

                                        <li><hr class="dropdown-divider"></li>

                                        <li>
                                            <form
                                                method="post"
                                                action="/admin/events/<?= (int) $event['id'] ?>/delete"
                                            >
                                                <?= Csrf::field() ?>

                                                <button
                                                    type="submit"
                                                    class="dropdown-item text-danger"
                                                    onclick="return confirm(
                                                        'Delete this draft event? This action cannot be undone.'
                                                    );"
                                                >
                                                    Delete Event
                                                </button>
                                            </form>
                                        </li>

                                    <?php endif; ?>

                                <?php endif; ?>

                            </ul>
                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<script>
function copySignupLink(url) {
    navigator.clipboard.writeText(url)
        .then(() => {
            alert('Signup link copied to clipboard.');
        })
        .catch(() => {
            window.prompt('Copy this signup link:', url);
        });
}
</script>

<?php

require __DIR__ . '/partials/footer.php';

?>
