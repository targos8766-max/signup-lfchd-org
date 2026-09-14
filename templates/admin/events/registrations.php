<?php

use Boneblaze\SignupLfchdOrg\Services\Csrf;

$pageTitle = 'Registrations | LFCHD Signup';

require dirname(__DIR__) . '/partials/header.php';
?>

<div class="container py-4 py-md-5">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
        <div>
            <h1 class="mb-1">
                <?= htmlspecialchars(
                    $event['title'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h1>

            <div class="text-muted">Registration List</div>
        </div>

        <div class="lfchd-mobile-actions">
            <a
                href="/admin/events/<?= (int) $event['id'] ?>/registrations/export"
                class="btn btn-success"
            >
                Export CSV
            </a>

            <a
                href="/admin/events/<?= (int) $event['id'] ?>/slots"
                class="btn btn-outline-secondary"
            >
                Manage Slots
            </a>

            <a href="/admin" class="btn btn-outline-secondary">
                Back to Events
            </a>
        </div>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">
            Registration updated successfully.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['cancelled'])): ?>
        <div class="alert alert-success">
            Registration cancelled successfully.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['restored'])): ?>
        <div class="alert alert-success">
            Registration restored successfully.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['resent'])): ?>
        <div class="alert alert-success">
            Confirmation email resent successfully.
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

    <?php
    $eventDate = new DateTimeImmutable($event['event_date']);
    $eventStart = new DateTimeImmutable(
        $event['event_date'] . ' ' . $event['start_time']
    );
    $eventEnd = new DateTimeImmutable(
        $event['event_date'] . ' ' . $event['end_time']
    );
    ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="text-muted small">Date</div>
                    <div class="fw-semibold">
                        <?= $eventDate->format('F j, Y') ?>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="text-muted small">Time</div>
                    <div class="fw-semibold">
                        <?= $eventStart->format('g:i A') ?>
                        –
                        <?= $eventEnd->format('g:i A') ?>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="text-muted small">Status</div>
                    <div class="fw-semibold">
                        <?= ucfirst(
                            htmlspecialchars(
                                $event['status'],
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        ) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">
                        Confirmed Registrations
                    </div>
                    <div class="display-6">
                        <?= $totalConfirmed ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">
                        Cancelled Registrations
                    </div>
                    <div class="display-6">
                        <?= $totalCancelled ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Time Slots</div>
                    <div class="display-6">
                        <?= count($slots) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$slots): ?>
        <div class="alert alert-info">
            This event does not have any time slots.
        </div>
    <?php endif; ?>

    <?php foreach ($slots as $slot): ?>
        <?php
        $slotStart = new DateTimeImmutable($slot['start_datetime']);
        $slotEnd = new DateTimeImmutable($slot['end_datetime']);
        $slotRegistrations =
            $registrationsBySlot[(int) $slot['id']] ?? [];

        $confirmedCount = 0;

        foreach ($slotRegistrations as $registration) {
            if ($registration['status'] === 'confirmed') {
                $confirmedCount++;
            }
        }

        $capacity = (int) $slot['capacity'];
        $remaining = max(0, $capacity - $confirmedCount);
        ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <strong>
                        <?= $slotStart->format('g:i A') ?>
                        –
                        <?= $slotEnd->format('g:i A') ?>
                    </strong>

                    <?php if (!(int) $slot['enabled']): ?>
                        <span class="badge text-bg-secondary ms-2">
                            Disabled
                        </span>
                    <?php endif; ?>
                </div>

                <div class="text-muted small">
                    <?= $confirmedCount ?> / <?= $capacity ?> filled
                    <span class="ms-2">
                        <?= $remaining ?> remaining
                    </span>
                </div>
            </div>

            <?php if (!$slotRegistrations): ?>
                <div class="card-body text-muted">
                    No registrations for this time.
                </div>
            <?php else: ?>

                <div class="d-none d-lg-block">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <?php if ($questions): ?>
                                    <th>Custom Answers</th>
                                <?php endif; ?>
                                <th>Actions</th>
                            </tr>
                            </thead>

                            <tbody>
                            <?php foreach ($slotRegistrations as $registration): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?= htmlspecialchars(
                                                $registration['last_name']
                                                . ', '
                                                . $registration['first_name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $registration['email'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $registration['phone'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?php
                                        $statusClass =
                                            $registration['status'] === 'confirmed'
                                                ? 'success'
                                                : 'secondary';
                                        ?>

                                        <span class="badge text-bg-<?= $statusClass ?>">
                                            <?= ucfirst(
                                                htmlspecialchars(
                                                    $registration['status'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                )
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= (
                                            new DateTimeImmutable(
                                                $registration['created_at']
                                            )
                                        )->format('M j, Y g:i A') ?>
                                    </td>

                                    <?php if ($questions): ?>
                                        <td style="min-width: 280px;">
                                            <?php
                                            $answers = $registration['answers'] ?? [];
                                            $hasAnswers = false;

                                            foreach ($questions as $question) {
                                                $questionId = (int) $question['id'];

                                                if (
                                                    array_key_exists(
                                                        $questionId,
                                                        $answers
                                                    )
                                                ) {
                                                    $hasAnswers = true;
                                                    break;
                                                }
                                            }
                                            ?>

                                            <?php if (!$hasAnswers): ?>
                                                <span class="text-muted">—</span>
                                            <?php else: ?>
                                                <details>
                                                    <summary
                                                        class="text-primary"
                                                        style="cursor: pointer;"
                                                    >
                                                        View Answers
                                                    </summary>

                                                    <div class="mt-2">
                                                        <?php foreach ($questions as $question): ?>
                                                            <?php
                                                            $questionId = (int) $question['id'];

                                                            if (
                                                                !array_key_exists(
                                                                    $questionId,
                                                                    $answers
                                                                )
                                                            ) {
                                                                continue;
                                                            }

                                                            $answer = $answers[$questionId];

                                                            if (
                                                                $question['question_type']
                                                                === 'checkbox'
                                                            ) {
                                                                $decodedAnswer = json_decode(
                                                                    (string) $answer,
                                                                    true
                                                                );

                                                                if (is_array($decodedAnswer)) {
                                                                    $answer = implode(', ', $decodedAnswer);
                                                                } elseif ((string) $answer === '1') {
                                                                    $answer = 'Yes';
                                                                } elseif ((string) $answer === '0') {
                                                                    $answer = 'No';
                                                                }
                                                            }
                                                            ?>

                                                            <div class="mb-2">
                                                                <div class="fw-semibold small">
                                                                    <?= htmlspecialchars(
                                                                        $question['question_text'],
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>
                                                                </div>

                                                                <div>
                                                                    <?= nl2br(
                                                                        htmlspecialchars(
                                                                            (string) $answer,
                                                                            ENT_QUOTES,
                                                                            'UTF-8'
                                                                        )
                                                                    ) ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </details>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>

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
                                                        href="/admin/events/<?= (int) $event['id'] ?>/registrations/<?= (int) $registration['id'] ?>/edit"
                                                    >
                                                        Edit
                                                    </a>
                                                </li>

                                                <?php if (!empty($registration['email'])): ?>
                                                    <li>
                                                        <form
                                                            method="post"
                                                            action="/admin/events/<?= (int) $event['id'] ?>/registrations/<?= (int) $registration['id'] ?>/resend-confirmation"
                                                            onsubmit="return confirm('Resend the confirmation email to <?= htmlspecialchars(
                                                                $registration['email'],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>?');"
                                                        >
                                                            <?= Csrf::field() ?>

                                                            <button
                                                                type="submit"
                                                                class="dropdown-item"
                                                            >
                                                                Resend Confirmation
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>

                                                <li><hr class="dropdown-divider"></li>

                                                <?php if ($registration['status'] === 'confirmed'): ?>
                                                    <li>
                                                        <form
                                                            method="post"
                                                            action="/admin/events/<?= (int) $event['id'] ?>/registrations/<?= (int) $registration['id'] ?>/cancel"
                                                            onsubmit="return confirm('Cancel this registration?');"
                                                        >
                                                            <?= Csrf::field() ?>

                                                            <button
                                                                type="submit"
                                                                class="dropdown-item text-danger"
                                                            >
                                                                Cancel Registration
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php else: ?>
                                                    <li>
                                                        <form
                                                            method="post"
                                                            action="/admin/events/<?= (int) $event['id'] ?>/registrations/<?= (int) $registration['id'] ?>/restore"
                                                        >
                                                            <?= Csrf::field() ?>

                                                            <button
                                                                type="submit"
                                                                class="dropdown-item text-success"
                                                            >
                                                                Restore Registration
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-lg-none p-3">
                    <?php foreach ($slotRegistrations as $registration): ?>
                        <?php
                        $statusClass =
                            $registration['status'] === 'confirmed'
                                ? 'success'
                                : 'secondary';
                        ?>
                        <div class="card border mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between gap-2 mb-3">
                                    <div>
                                        <h3 class="h6 mb-1">
                                            <?= htmlspecialchars(
                                                $registration['first_name']
                                                . ' '
                                                . $registration['last_name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </h3>

                                        <span class="badge text-bg-<?= $statusClass ?>">
                                            <?= ucfirst(
                                                htmlspecialchars(
                                                    $registration['status'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                )
                                            ) ?>
                                        </span>
                                    </div>

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
                                                    href="/admin/events/<?= (int) $event['id'] ?>/registrations/<?= (int) $registration['id'] ?>/edit"
                                                >
                                                    Edit
                                                </a>
                                            </li>

                                            <?php if (!empty($registration['email'])): ?>
                                                <li>
                                                    <form
                                                        method="post"
                                                        action="/admin/events/<?= (int) $event['id'] ?>/registrations/<?= (int) $registration['id'] ?>/resend-confirmation"
                                                        onsubmit="return confirm('Resend the confirmation email to <?= htmlspecialchars(
                                                            $registration['email'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>?');"
                                                    >
                                                        <?= Csrf::field() ?>

                                                        <button
                                                            type="submit"
                                                            class="dropdown-item"
                                                        >
                                                            Resend Confirmation
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>

                                            <li><hr class="dropdown-divider"></li>

                                            <?php if ($registration['status'] === 'confirmed'): ?>
                                                <li>
                                                    <form
                                                        method="post"
                                                        action="/admin/events/<?= (int) $event['id'] ?>/registrations/<?= (int) $registration['id'] ?>/cancel"
                                                        onsubmit="return confirm('Cancel this registration?');"
                                                    >
                                                        <?= Csrf::field() ?>

                                                        <button
                                                            type="submit"
                                                            class="dropdown-item text-danger"
                                                        >
                                                            Cancel Registration
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php else: ?>
                                                <li>
                                                    <form
                                                        method="post"
                                                        action="/admin/events/<?= (int) $event['id'] ?>/registrations/<?= (int) $registration['id'] ?>/restore"
                                                    >
                                                        <?= Csrf::field() ?>

                                                        <button
                                                            type="submit"
                                                            class="dropdown-item text-success"
                                                        >
                                                            Restore Registration
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>

                                <?php if (!empty($registration['email'])): ?>
                                    <div class="mb-2">
                                        <div class="text-muted small">Email</div>
                                        <div class="text-break">
                                            <?= htmlspecialchars(
                                                $registration['email'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($registration['phone'])): ?>
                                    <div class="mb-2">
                                        <div class="text-muted small">Phone</div>
                                        <div>
                                            <?= htmlspecialchars(
                                                $registration['phone'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-2">
                                    <div class="text-muted small">Registered</div>
                                    <div>
                                        <?= (
                                            new DateTimeImmutable(
                                                $registration['created_at']
                                            )
                                        )->format('M j, Y g:i A') ?>
                                    </div>
                                </div>

                                <?php if ($questions): ?>
                                    <?php
                                    $answers = $registration['answers'] ?? [];
                                    $hasAnswers = false;

                                    foreach ($questions as $question) {
                                        if (
                                            array_key_exists(
                                                (int) $question['id'],
                                                $answers
                                            )
                                        ) {
                                            $hasAnswers = true;
                                            break;
                                        }
                                    }
                                    ?>

                                    <?php if ($hasAnswers): ?>
                                        <details class="mt-3">
                                            <summary
                                                class="text-primary"
                                                style="cursor:pointer;"
                                            >
                                                Custom Answers
                                            </summary>

                                            <div class="mt-2">
                                                <?php foreach ($questions as $question): ?>
                                                    <?php
                                                    $questionId = (int) $question['id'];

                                                    if (
                                                        !array_key_exists(
                                                            $questionId,
                                                            $answers
                                                        )
                                                    ) {
                                                        continue;
                                                    }

                                                    $answer = $answers[$questionId];

                                                    if (
                                                        $question['question_type']
                                                        === 'checkbox'
                                                    ) {
                                                        $decodedAnswer = json_decode(
                                                            (string) $answer,
                                                            true
                                                        );

                                                        if (is_array($decodedAnswer)) {
                                                            $answer = implode(', ', $decodedAnswer);
                                                        } elseif ((string) $answer === '1') {
                                                            $answer = 'Yes';
                                                        } elseif ((string) $answer === '0') {
                                                            $answer = 'No';
                                                        }
                                                    }
                                                    ?>

                                                    <div class="mb-2">
                                                        <div class="fw-semibold small">
                                                            <?= htmlspecialchars(
                                                                $question['question_text'],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>
                                                        </div>

                                                        <div>
                                                            <?= nl2br(
                                                                htmlspecialchars(
                                                                    (string) $answer,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                )
                                                            ) ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </details>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>
    <?php endforeach; ?>

</div>

<?php
require dirname(__DIR__) . '/partials/footer.php';
?>
