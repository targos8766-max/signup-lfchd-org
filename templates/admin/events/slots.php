<?php

use Boneblaze\SignupLfchdOrg\Services\Csrf;
use Boneblaze\SignupLfchdOrg\Services\EntraAuth;

$authUser = EntraAuth::user();

$pageTitle = 'Manage Slots | LFCHD Signup';

require dirname(__DIR__) . '/partials/header.php';
?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h1 class="mb-1">
                <?= htmlspecialchars($event['title']) ?>
            </h1>

            <div class="text-muted">
                Manage Time Slots
            </div>
        </div>

        <a
            href="/admin/events/create"
            class="btn btn-outline-secondary"
        >
            Create Another Event
        </a>

    </div>

    <?php if (isset($_GET['saved'])): ?>

        <div class="alert alert-success">
            Time slots saved successfully.
        </div>

    <?php endif; ?>

    <div class="card shadow-sm">

        <form
            method="post"
            action="/admin/events/<?= (int) $event['id'] ?>/slots"
        >

            <?= Csrf::field() ?>

            <div class="table-responsive">

                <table class="table table-striped align-middle mb-0">

                    <thead>
                        <tr>
                            <th>Time</th>
                            <th style="width:180px;">
                                Seats
                            </th>
                            <th style="width:130px;">
                                Enabled
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($slots as $slot): ?>

                        <tr>

                            <td>
                                <?php
                                $start = new DateTimeImmutable(
                                    $slot['start_datetime']
                                );

                                $end = new DateTimeImmutable(
                                    $slot['end_datetime']
                                );
                                ?>

                                <?= $start->format('g:i A') ?>
                                –
                                <?= $end->format('g:i A') ?>
                            </td>

                            <td>
                                <input
                                    type="number"
                                    name="capacity[<?= (int) $slot['id'] ?>]"
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
                                        name="enabled[<?= (int) $slot['id'] ?>]"
                                        value="1"
                                        <?= $slot['enabled']
                                            ? 'checked'
                                            : '' ?>
                                    >

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <div class="card-footer text-end">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save Time Slots
                </button>

            </div>

        </form>

    </div>

</div>

<?php

require dirname(__DIR__) . '/partials/footer.php';

?>