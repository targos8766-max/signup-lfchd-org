<?php

use Boneblaze\SignupLfchdOrg\Services\EntraAuth;
use Boneblaze\SignupLfchdOrg\Services\Csrf;

$authUser = EntraAuth::user();

$isAdministrator =
    EntraAuth::isAdministrator();

$pageTitle = 'Events | LFCHD Signup';

require __DIR__ . '/partials/header.php';

?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

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

        <div class="card shadow-sm">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Signups</th>
                        <th>Slots</th>
                        <th style="width:260px;">
                            Actions
                        </th>
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

                                <span
                                    class="badge text-bg-<?= $statusClass ?>"
                                >
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

                                <div class="d-flex gap-2 flex-wrap">

                                    <?php if ($isAdministrator): ?>

                                        <a
                                            href="/admin/events/<?= (int) $event['id'] ?>/slots"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Slots
                                        </a>

                                    <?php endif; ?>

                                    <a
                                        href="<?= htmlspecialchars(
                                            $publicUrl,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        target="_blank"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        Public Page
                                    </a>

                                    <?php if ($isAdministrator): ?>

                                        <a
                                            href="/admin/events/<?= (int) $event['id'] ?>/edit"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Edit
                                        </a>

                                    <?php endif; ?>

                                    <a
                                        href="/admin/events/<?= (int) $event['id'] ?>/registrations"
                                        class="btn btn-sm btn-outline-success"
                                    >
                                        Registrations
                                    </a>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        onclick="copySignupLink(
                                            '<?= htmlspecialchars(
                                                $publicUrl,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>'
                                        )"
                                    >
                                        Copy Link
                                    </button>

                                    <?php if ($isAdministrator): ?>

                                        <?php if (
                                            $event['status'] === 'draft'
                                            || $event['status'] === 'closed'
                                        ): ?>

                                            <form
                                                method="post"
                                                action="/admin/events/<?= (int) $event['id'] ?>/open"
                                                class="d-inline"
                                            >
                                                <?= Csrf::field() ?>

                                                <button
                                                    type="submit"
                                                    class="btn btn-outline-success btn-sm"
                                                    onclick="return confirm(
                                                        'Open registration for this event?'
                                                    );"
                                                >
                                                    Open Registration
                                                </button>
                                            </form>

                                        <?php endif; ?>

                                        <?php if ($event['status'] === 'open'): ?>

                                            <form
                                                method="post"
                                                action="/admin/events/<?= (int) $event['id'] ?>/close"
                                                class="d-inline"
                                            >
                                                <?= Csrf::field() ?>

                                                <button
                                                    type="submit"
                                                    class="btn btn-outline-warning btn-sm"
                                                    onclick="return confirm(
                                                        'Close registration for this event? Existing registrations will remain.'
                                                    );"
                                                >
                                                    Close Registration
                                                </button>
                                            </form>

                                        <?php endif; ?>

                                        <a
                                            href="/admin/events/<?= (int) $event['id'] ?>/questions"
                                            class="btn btn-outline-info btn-sm"
                                        >
                                            Questions
                                        </a>

                                        <form
                                            method="post"
                                            action="/admin/events/<?= (int) $event['id'] ?>/duplicate"
                                            class="d-inline"
                                        >
                                            <?= Csrf::field() ?>

                                            <button
                                                type="submit"
                                                class="btn btn-outline-secondary btn-sm"
                                                onclick="return confirm('Duplicate this event?');"
                                            >
                                                Duplicate
                                            </button>
                                        </form>

                                        <?php if ($event['status'] === 'draft'): ?>

                                            <form
                                                method="post"
                                                action="/admin/events/<?= (int) $event['id'] ?>/delete"
                                                class="d-inline"
                                            >
                                                <?= Csrf::field() ?>

                                                <button
                                                    type="submit"
                                                    class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm(
                                                        'Delete this draft event? This action cannot be undone.'
                                                    );"
                                                >
                                                    Delete
                                                </button>
                                            </form>

                                        <?php endif; ?>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

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
