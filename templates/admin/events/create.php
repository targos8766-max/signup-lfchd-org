<?php

use Boneblaze\SignupLfchdOrg\Services\Csrf;
use Boneblaze\SignupLfchdOrg\Services\EntraAuth;

$authUser = EntraAuth::user();

$pageTitle = 'Create Event | LFCHD Signup';

require dirname(__DIR__) . '/partials/header.php';

?>

<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <h1 class="mb-4">Create Event</h1>

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

            <form
                method="post"
                action="/admin/events/create"
                class="card shadow-sm"
            >

                <?php // echo Csrf::field() ?>
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
                            value="<?= htmlspecialchars($title ?? '') ?>"
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
                        ><?= htmlspecialchars($description ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Location
                        </label>

                        <input
                            type="text"
                            name="location"
                            class="form-control"
                            value="<?= htmlspecialchars($location ?? '') ?>"
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
                                value="<?= htmlspecialchars($eventDate ?? '') ?>"
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
                                value="<?= htmlspecialchars($startTime ?? '') ?>"
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
                                value="<?= htmlspecialchars($endTime ?? '') ?>"
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

                                <?php
                                $selectedInterval =
                                    (int) ($intervalMinutes ?? 15);
                                ?>

                                <?php foreach ([5, 10, 15, 20, 30, 45, 60] as $interval): ?>

                                    <option
                                        value="<?= $interval ?>"
                                        <?= $selectedInterval === $interval
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
                                value="<?= htmlspecialchars(
                                    (string) ($defaultCapacity ?? 1)
                                ) ?>"
                            >
                        </div>

                    </div>

                </div>

                <div class="card-footer text-end">
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Create Event
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

<?php

require dirname(__DIR__) . '/partials/footer.php';

?>