<?php

use Boneblaze\SignupLfchdOrg\Services\Csrf;
use Boneblaze\SignupLfchdOrg\Services\EntraAuth;

$authUser = EntraAuth::user();

$pageTitle = 'Edit Event | LFCHD Signup';

require dirname(__DIR__) . '/partials/header.php';
?>

<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>
                    <h1 class="mb-1">
                        Edit Event
                    </h1>

                    <div class="text-muted">
                        <?= htmlspecialchars($event['title']) ?>
                    </div>
                </div>

                <a
                    href="/admin/events/<?= (int) $event['id'] ?>/slots"
                    class="btn btn-outline-secondary"
                >
                    Manage Slots
                </a>

                <a
                    href="/admin/events/<?= (int) $event['id'] ?>/questions"
                    class="btn btn-outline-info"
                >
                    Questions
                </a>

            </div>

            <?php if (isset($_GET['saved'])): ?>

                <div class="alert alert-success">
                    Event updated successfully.
                </div>

            <?php endif; ?>

            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger">
                    <ul class="mb-0">

                        <?php foreach ($errors as $error): ?>
                            <li>
                                <?= htmlspecialchars($error) ?>
                            </li>
                        <?php endforeach; ?>

                    </ul>
                </div>

            <?php endif; ?>

            <div class="alert alert-warning">
                Changing the date, start time, end time, interval, or default
                capacity does not currently regenerate existing time slots.
                Existing slots must be managed separately.
            </div>

            <form
                method="post"
                action="/admin/events/<?= (int) $event['id'] ?>/edit"
                class="card shadow-sm"
            >

                <?= Csrf::field() ?>

                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label">
                            Event Name
                        </label>

                        <input
                            type="text"
                            name="title"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($event['title']) ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"
                        ><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Location
                        </label>

                        <input
                            type="text"
                            name="location"
                            class="form-control"
                            value="<?= htmlspecialchars($event['location'] ?? '') ?>"
                        >
                    </div>

                    <hr>

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Event Date
                            </label>

                            <input
                                type="date"
                                name="event_date"
                                class="form-control"
                                required
                                value="<?= htmlspecialchars($event['event_date']) ?>"
                            >
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Start Time
                            </label>

                            <input
                                type="time"
                                name="start_time"
                                class="form-control"
                                required
                                value="<?= htmlspecialchars(
                                    substr($event['start_time'], 0, 5)
                                ) ?>"
                            >
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                End Time
                            </label>

                            <input
                                type="time"
                                name="end_time"
                                class="form-control"
                                required
                                value="<?= htmlspecialchars(
                                    substr($event['end_time'], 0, 5)
                                ) ?>"
                            >
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Interval
                            </label>

                            <select
                                name="interval_minutes"
                                class="form-select"
                                required
                            >

                                <?php foreach ([5, 10, 15, 20, 30, 45, 60] as $interval): ?>

                                    <option
                                        value="<?= $interval ?>"
                                        <?= (int) $event['interval_minutes'] === $interval
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= $interval ?> minutes
                                    </option>

                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Default Seats Per Time
                            </label>

                            <input
                                type="number"
                                name="default_capacity"
                                class="form-control"
                                min="1"
                                max="999"
                                required
                                value="<?= (int) $event['default_capacity'] ?>"
                            >
                        </div>

                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label">
                            Status
                        </label>

                        <select
                            name="status"
                            class="form-select"
                        >

                            <?php foreach ([
                                'draft' => 'Draft',
                                'open' => 'Open',
                                'closed' => 'Closed',
                                'cancelled' => 'Cancelled',
                            ] as $value => $label): ?>

                                <option
                                    value="<?= $value ?>"
                                    <?= $event['status'] === $value
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= $label ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <div class="form-text">
                            Only open events will eventually accept public
                            registrations.
                        </div>
                    </div>

                    <?php
                    $signupOpenValue = '';

                    if (!empty($event['signup_open_at'])) {
                        $signupOpenValue = (
                            new DateTimeImmutable($event['signup_open_at'])
                        )->format('Y-m-d\TH:i');
                    }

                    $signupCloseValue = '';

                    if (!empty($event['signup_close_at'])) {
                        $signupCloseValue = (
                            new DateTimeImmutable($event['signup_close_at'])
                        )->format('Y-m-d\TH:i');
                    }
                    ?>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Signup Opens
                            </label>

                            <input
                                type="datetime-local"
                                name="signup_open_at"
                                class="form-control"
                                value="<?= htmlspecialchars($signupOpenValue) ?>"
                            >

                            <div class="form-text">
                                Optional.
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Signup Closes
                            </label>

                            <input
                                type="datetime-local"
                                name="signup_close_at"
                                class="form-control"
                                value="<?= htmlspecialchars($signupCloseValue) ?>"
                            >

                            <div class="form-text">
                                Optional.
                            </div>
                        </div>

                    </div>

                </div>

                <div class="card-footer d-flex justify-content-between">

                    <a
                        href="/admin"
                        class="btn btn-outline-secondary"
                    >
                        Back to Events
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Changes
                    </button>

                </div>

            </form>

        </div>
    </div>

</div>

<?php

require dirname(__DIR__) . '/partials/footer.php';

?>