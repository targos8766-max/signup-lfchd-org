<?php

use Boneblaze\SignupLfchdOrg\Services\Csrf;
use Boneblaze\SignupLfchdOrg\Services\EntraAuth;

$authUser = EntraAuth::user();

$pageTitle = 'Manage Slots | LFCHD Signup';

require dirname(__DIR__) . '/partials/header.php';
?>

<div class="container py-4 py-md-5">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">

        <div>
            <h1 class="mb-1">
                <?= htmlspecialchars($event['title']) ?>
            </h1>

            <div class="text-muted">
                Manage Time Slots
            </div>
        </div>

        <div class="lfchd-mobile-actions">
            <a
                href="/admin/events/create"
                class="btn btn-outline-secondary"
            >
                Create Another Event
            </a>
        </div>

    </div>

    <?php if (isset($_GET['saved'])): ?>

        <div class="alert alert-success" role="alert">
            Time slots saved successfully.
        </div>

    <?php endif; ?>

    <div class="card shadow-sm">

        <form
            method="post"
            action="/admin/events/<?= (int) $event['id'] ?>/slots"
        >

            <?= Csrf::field() ?>

            <?php if (empty($slots)): ?>

                <div class="card-body p-3 p-md-4">
                    <div class="alert alert-info mb-0">
                        No time slots are currently available for this event.
                    </div>
                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-striped align-middle mb-0">

                        <thead>
                            <tr>
                                <th scope="col">Time</th>
                                <th scope="col" style="width:180px;">
                                    Seats
                                </th>
                                <th scope="col" style="width:130px;">
                                    Enabled
                                </th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($slots as $slot): ?>

                            <?php
                            $start = new DateTimeImmutable(
                                $slot['start_datetime']
                            );

                            $end = new DateTimeImmutable(
                                $slot['end_datetime']
                            );

                            $slotId = (int) $slot['id'];
                            ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= $start->format('g:i A') ?>
                                        –
                                        <?= $end->format('g:i A') ?>
                                    </strong>
                                </td>

                                <td>
                                    <label
                                        for="capacity-<?= $slotId ?>"
                                        class="visually-hidden"
                                    >
                                        Seats for
                                        <?= $start->format('g:i A') ?>
                                        to
                                        <?= $end->format('g:i A') ?>
                                    </label>

                                    <input
                                        type="number"
                                        id="capacity-<?= $slotId ?>"
                                        name="capacity[<?= $slotId ?>]"
                                        value="<?= (int) $slot['capacity'] ?>"
                                        min="1"
                                        max="999"
                                        class="form-control"
                                    >
                                </td>

                                <td>

                                    <div class="form-check form-switch">

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="enabled-<?= $slotId ?>"
                                            name="enabled[<?= $slotId ?>]"
                                            value="1"
                                            <?= $slot['enabled'] ? 'checked' : '' ?>
                                        >

                                        <label
                                            class="form-check-label visually-hidden"
                                            for="enabled-<?= $slotId ?>"
                                        >
                                            Enable
                                            <?= $start->format('g:i A') ?>
                                            to
                                            <?= $end->format('g:i A') ?>
                                        </label>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

            <div class="card-footer bg-white p-3 p-md-4">

                <div class="lfchd-mobile-actions justify-content-end">

                    <button
                        type="submit"
                        class="btn btn-primary"
                        <?= empty($slots) ? 'disabled' : '' ?>
                    >
                        Save Time Slots
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>

<?php

require dirname(__DIR__) . '/partials/footer.php';

?>
