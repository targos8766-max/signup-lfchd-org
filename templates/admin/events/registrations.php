<?php

use Boneblaze\SignupLfchdOrg\Services\Csrf;

$pageTitle = 'Registrations | LFCHD Signup';

require dirname(__DIR__) . '/partials/header.php';
?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-start mb-4">
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

        <div class="d-flex gap-2 flex-wrap">
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
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">Date</div>
                    <div class="fw-semibold">
                        <?= $eventDate->format('F j, Y') ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small">Time</div>
                    <div class="fw-semibold">
                        <?= $eventStart->format('g:i A') ?>
                        –
                        <?= $eventEnd->format('g:i A') ?>
                    </div>
                </div>

                <div class="col-md-4">
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
        <div class="col-md-4">
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

        <div class="col-md-4">
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

        <div class="col-md-4">
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
            <div
                class="card-header d-flex justify-content-between align-items-center"
            >
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

                <div class="text-muted">
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

                                    <span
                                        class="badge text-bg-<?= $statusClass ?>"
                                    >
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
                                        $answers =
                                            $registration['answers'] ?? [];
                                        $hasAnswers = false;

                                        foreach ($questions as $question) {
                                            $questionId =
                                                (int) $question['id'];

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
                                                        $questionId =
                                                            (int) $question['id'];

                                                        if (
                                                            !array_key_exists(
                                                                $questionId,
                                                                $answers
                                                            )
                                                        ) {
                                                            continue;
                                                        }

                                                        $answer =
                                                            $answers[$questionId];

                                                        if (
                                                            $question['question_type']
                                                            === 'checkbox'
                                                        ) {
                                                            if ((string) $answer === '1') {
                                                                $answer = 'Yes';
                                                            } elseif ((string) $answer === '0') {
                                                                $answer = 'No';
                                                            }
                                                        }
                                                        ?>

                                                        <div class="mb-2">
                                                            <div
                                                                class="fw-semibold small"
                                                            >
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
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a
                                            href="/admin/events/<?= (int) $event['id'] ?>/registrations/<?= (int) $registration['id'] ?>/edit"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Edit
                                        </a>

                                        <?php if (!empty($registration['email'])): ?>
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
                                                    class="btn btn-sm btn-outline-secondary"
                                                >
                                                    Resend Confirmation
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($registration['status'] === 'confirmed'): ?>
                                            <form
                                                method="post"
                                                action="/admin/events/<?= (int) $event['id'] ?>/registrations/<?= (int) $registration['id'] ?>/cancel"
                                                onsubmit="return confirm('Cancel this registration?');"
                                            >
                                                <?= Csrf::field() ?>

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                >
                                                    Cancel
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form
                                                method="post"
                                                action="/admin/events/<?= (int) $event['id'] ?>/registrations/<?= (int) $registration['id'] ?>/restore"
                                            >
                                                <?= Csrf::field() ?>

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-success"
                                                >
                                                    Restore
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

</div>

<?php
require dirname(__DIR__) . '/partials/footer.php';
?>
