<?php

use Boneblaze\SignupLfchdOrg\Services\Csrf;
use Boneblaze\SignupLfchdOrg\Services\EntraAuth;

$authUser = EntraAuth::user();

$pageTitle = 'Edit Event | LFCHD Signup';

require dirname(__DIR__) . '/partials/header.php';
?>

<div class="container py-4 py-md-5">

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">

            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">

                <div>
                    <h1 class="mb-1">
                        Edit Event
                    </h1>

                    <div class="text-muted">
                        <?= htmlspecialchars($event['title']) ?>
                    </div>
                </div>

                <div class="lfchd-mobile-actions">
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

            </div>

            <?php if (isset($_GET['saved'])): ?>

                <div class="alert alert-success" role="alert">
                    Event updated successfully.
                </div>

            <?php endif; ?>

            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger" role="alert">
                    <strong>Please correct the following:</strong>

                    <ul class="mb-0 mt-2">

                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>

                    </ul>
                </div>

            <?php endif; ?>

            <div class="alert alert-warning" role="alert">
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

                <div class="card-body p-3 p-md-4">

                    <section aria-labelledby="event-details-heading">

                        <h2
                            id="event-details-heading"
                            class="h5 mb-3"
                        >
                            Event Details
                        </h2>

                        <div class="mb-3">
                            <label
                                for="title"
                                class="form-label"
                            >
                                Event Name
                            </label>

                            <input
                                type="text"
                                id="title"
                                name="title"
                                class="form-control"
                                required
                                value="<?= htmlspecialchars($event['title']) ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label
                                for="description"
                                class="form-label"
                            >
                                Description
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                class="form-control"
                                rows="4"
                            ><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label
                                for="location"
                                class="form-label"
                            >
                                Location
                            </label>

                            <input
                                type="text"
                                id="location"
                                name="location"
                                class="form-control"
                                value="<?= htmlspecialchars($event['location'] ?? '') ?>"
                            >
                        </div>

                    </section>

                    <hr class="my-4">

                    <section aria-labelledby="schedule-heading">

                        <h2
                            id="schedule-heading"
                            class="h5 mb-3"
                        >
                            Schedule
                        </h2>

                        <div class="row g-3">

                            <div class="col-12 col-md-4">
                                <label
                                    for="event_date"
                                    class="form-label"
                                >
                                    Event Date
                                </label>

                                <input
                                    type="date"
                                    id="event_date"
                                    name="event_date"
                                    class="form-control"
                                    required
                                    value="<?= htmlspecialchars($event['event_date']) ?>"
                                >
                            </div>

                            <div class="col-12 col-md-4">
                                <label
                                    for="start_time"
                                    class="form-label"
                                >
                                    Start Time
                                </label>

                                <input
                                    type="time"
                                    id="start_time"
                                    name="start_time"
                                    class="form-control"
                                    required
                                    value="<?= htmlspecialchars(
                                        substr($event['start_time'], 0, 5)
                                    ) ?>"
                                >
                            </div>

                            <div class="col-12 col-md-4">
                                <label
                                    for="end_time"
                                    class="form-label"
                                >
                                    End Time
                                </label>

                                <input
                                    type="time"
                                    id="end_time"
                                    name="end_time"
                                    class="form-control"
                                    required
                                    value="<?= htmlspecialchars(
                                        substr($event['end_time'], 0, 5)
                                    ) ?>"
                                >
                            </div>

                        </div>

                        <div class="row g-3 mt-0">

                            <div class="col-12 col-md-6">
                                <label
                                    for="interval_minutes"
                                    class="form-label"
                                >
                                    Interval
                                </label>

                                <select
                                    id="interval_minutes"
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

                            <div class="col-12 col-md-6">
                                <label
                                    for="default_capacity"
                                    class="form-label"
                                >
                                    Default Seats Per Time
                                </label>

                                <input
                                    type="number"
                                    id="default_capacity"
                                    name="default_capacity"
                                    class="form-control"
                                    min="1"
                                    max="999"
                                    required
                                    value="<?= (int) $event['default_capacity'] ?>"
                                >
                            </div>

                        </div>

                    </section>

                    <hr class="my-4">

                    <section aria-labelledby="registration-settings-heading">

                        <h2
                            id="registration-settings-heading"
                            class="h5 mb-3"
                        >
                            Registration Settings
                        </h2>

                        <div class="mb-3">
                            <label
                                for="status"
                                class="form-label"
                            >
                                Status
                            </label>

                            <select
                                id="status"
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
                                        <?= $event['status'] === $value ? 'selected' : '' ?>
                                    >
                                        <?= $label ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <div class="form-text">
                                Only open events will accept public registrations.
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

                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <label
                                    for="signup_open_at"
                                    class="form-label"
                                >
                                    Signup Opens
                                </label>

                                <input
                                    type="datetime-local"
                                    id="signup_open_at"
                                    name="signup_open_at"
                                    class="form-control"
                                    value="<?= htmlspecialchars($signupOpenValue) ?>"
                                >

                                <div class="form-text">
                                    Optional.
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label
                                    for="signup_close_at"
                                    class="form-label"
                                >
                                    Signup Closes
                                </label>

                                <input
                                    type="datetime-local"
                                    id="signup_close_at"
                                    name="signup_close_at"
                                    class="form-control"
                                    value="<?= htmlspecialchars($signupCloseValue) ?>"
                                >

                                <div class="form-text">
                                    Optional.
                                </div>
                            </div>

                        </div>

                    </section>

                </div>

                <div class="card-footer bg-white p-3 p-md-4">

                    <div class="lfchd-mobile-actions justify-content-between">

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

                </div>

            </form>

        </div>
    </div>

</div>

<?php

require dirname(__DIR__) . '/partials/footer.php';

?>
