<?php

use Boneblaze\SignupLfchdOrg\Services\Csrf;

$pageTitle = 'Questions | LFCHD Signup';

require dirname(__DIR__) . '/partials/header.php';

?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1">
            Registration Questions
        </h1>

        <div class="text-muted">
            <?= htmlspecialchars(
                $event['title'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a
            href="/admin/events/<?= (int) $event['id'] ?>/edit"
            class="btn btn-outline-secondary"
        >
            Edit Event
        </a>

        <a
            href="/admin"
            class="btn btn-outline-secondary"
        >
            Back to Events
        </a>
    </div>
</div>

<?php if (isset($_GET['created'])): ?>
    <div class="alert alert-success">
        Question added successfully.
    </div>
<?php endif; ?>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success">
        Question updated successfully.
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

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <strong>Add Question</strong>
    </div>

    <div class="card-body">
        <form
            method="post"
            action="/admin/events/<?= (int) $event['id'] ?>/questions"
        >
            <?= Csrf::field() ?>

            <div class="mb-3">
                <label class="form-label">
                    Question
                </label>

                <input
                    type="text"
                    name="question_text"
                    class="form-control"
                    maxlength="500"
                    required
                >
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">
                        Type
                    </label>

                    <select
                        name="question_type"
                        class="form-select"
                    >
                        <option value="text">
                            Text
                        </option>

                        <option value="textarea">
                            Long Text
                        </option>

                        <option value="select">
                            Dropdown
                        </option>

                        <option value="checkbox">
                            Checkbox
                        </option>
                    </select>
                </div>

                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input
                            type="checkbox"
                            name="required"
                            value="1"
                            class="form-check-input"
                            id="new-required"
                        >

                        <label
                            class="form-check-label"
                            for="new-required"
                        >
                            Required
                        </label>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3 mb-4 bg-light">
                <h3 class="h6 mb-3">
                    Conditional Display
                </h3>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label">
                            Show this question only when
                        </label>

                        <select
                            name="conditional_question_id"
                            class="form-select"
                        >
                            <option value="">
                                Always show
                            </option>

                            <?php foreach ($conditionQuestions as $conditionQuestion): ?>
                                <option
                                    value="<?= (int) $conditionQuestion['id'] ?>"
                                >
                                    <?= htmlspecialchars(
                                        $conditionQuestion['question_text'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">
                            Condition
                        </label>

                        <select
                            name="conditional_operator"
                            class="form-select"
                        >
                            <option value="equals">
                                Equals
                            </option>

                            <option value="not_equals">
                                Does Not Equal
                            </option>
                        </select>
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">
                            Value
                        </label>

                        <input
                            type="text"
                            name="conditional_value"
                            class="form-control"
                            placeholder="Example: Yes"
                        >
                    </div>
                </div>

                <div class="form-text mt-2">
                    Only Dropdown and Checkbox questions can control another question.
                    For checkboxes, use 1 for checked and 0 for unchecked.
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    Dropdown Options
                </label>

                <textarea
                    name="options"
                    class="form-control"
                    rows="4"
                    placeholder="Enter one option per line"
                ></textarea>

                <div class="form-text">
                    Only used for Dropdown questions.
                    Enter one choice per line.
                </div>
            </div>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Add Question
            </button>
        </form>
    </div>
</div>

<h2 class="h4 mb-3">
    Existing Questions
</h2>

<?php if (!$questions): ?>

    <div class="alert alert-info">
        No custom questions have been added yet.
    </div>

<?php else: ?>

    <?php foreach ($questions as $question): ?>

        <?php
        $optionsText = '';

        if (!empty($question['options_json'])) {
            $decoded = json_decode(
                $question['options_json'],
                true
            );

            if (is_array($decoded)) {
                $optionsText = implode(
                    PHP_EOL,
                    $decoded
                );
            }
        }
        ?>

        <div class="card shadow-sm mb-3">
            <div class="card-body">

                <form
                    method="post"
                    action="/admin/events/<?= (int) $event['id'] ?>/questions/<?= (int) $question['id'] ?>/edit"
                >
                    <?= Csrf::field() ?>

                    <div class="mb-3">
                        <label class="form-label">
                            Question
                        </label>

                        <input
                            type="text"
                            name="question_text"
                            class="form-control"
                            maxlength="500"
                            required
                            value="<?= htmlspecialchars(
                                $question['question_text'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">
                                Type
                            </label>

                            <select
                                name="question_type"
                                class="form-select"
                            >
                                <?php
                                $types = [
                                    'text' => 'Text',
                                    'textarea' => 'Long Text',
                                    'select' => 'Dropdown',
                                    'checkbox' => 'Checkbox',
                                ];
                                ?>

                                <?php foreach ($types as $value => $label): ?>
                                    <option
                                        value="<?= $value ?>"
                                        <?= $question['question_type'] === $value
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label">
                                Order
                            </label>

                            <input
                                type="number"
                                name="sort_order"
                                class="form-control"
                                min="0"
                                value="<?= (int) $question['sort_order'] ?>"
                            >
                        </div>

                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input
                                    type="checkbox"
                                    name="required"
                                    value="1"
                                    class="form-check-input"
                                    id="required-<?= (int) $question['id'] ?>"
                                    <?= (int) $question['required'] === 1
                                        ? 'checked'
                                        : '' ?>
                                >

                                <label
                                    class="form-check-label"
                                    for="required-<?= (int) $question['id'] ?>"
                                >
                                    Required
                                </label>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input
                                    type="checkbox"
                                    name="enabled"
                                    value="1"
                                    class="form-check-input"
                                    id="enabled-<?= (int) $question['id'] ?>"
                                    <?= (int) $question['enabled'] === 1
                                        ? 'checked'
                                        : '' ?>
                                >

                                <label
                                    class="form-check-label"
                                    for="enabled-<?= (int) $question['id'] ?>"
                                >
                                    Enabled
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded p-3 mb-4 bg-light">
                        <h3 class="h6 mb-3">
                            Conditional Display
                        </h3>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label class="form-label">
                                    Show this question only when
                                </label>

                                <select
                                    name="conditional_question_id"
                                    class="form-select"
                                >
                                    <option value="">
                                        Always show
                                    </option>

                                    <?php foreach ($conditionQuestions as $conditionQuestion): ?>

                                        <?php
                                        if (
                                            (int) $conditionQuestion['id']
                                            === (int) $question['id']
                                        ) {
                                            continue;
                                        }
                                        ?>

                                        <option
                                            value="<?= (int) $conditionQuestion['id'] ?>"
                                            <?= (
                                                isset($question['conditional_question_id'])
                                                && (int) $question['conditional_question_id']
                                                    === (int) $conditionQuestion['id']
                                            )
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= htmlspecialchars(
                                                $conditionQuestion['question_text'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </option>

                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-lg-3">
                                <label class="form-label">
                                    Condition
                                </label>

                                <select
                                    name="conditional_operator"
                                    class="form-select"
                                >
                                    <option
                                        value="equals"
                                        <?= (
                                            $question['conditional_operator']
                                            ?? ''
                                        ) === 'equals'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Equals
                                    </option>

                                    <option
                                        value="not_equals"
                                        <?= (
                                            $question['conditional_operator']
                                            ?? ''
                                        ) === 'not_equals'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Does Not Equal
                                    </option>
                                </select>
                            </div>

                            <div class="col-lg-3">
                                <label class="form-label">
                                    Value
                                </label>

                                <input
                                    type="text"
                                    name="conditional_value"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        (string) (
                                            $question['conditional_value']
                                            ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    placeholder="Example: Yes"
                                >
                            </div>
                        </div>

                        <div class="form-text mt-2">
                            Leave this set to Always show if this question should appear for everyone.
                            For checkboxes, use 1 for checked and 0 for unchecked.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Dropdown Options
                        </label>

                        <textarea
                            name="options"
                            class="form-control"
                            rows="4"
                        ><?= htmlspecialchars(
                            $optionsText,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></textarea>

                        <div class="form-text">
                            Only used for Dropdown questions.
                            Enter one choice per line.
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-outline-primary"
                    >
                        Save Question
                    </button>
                </form>

            </div>
        </div>

    <?php endforeach; ?>

<?php endif; ?>

<?php

require dirname(__DIR__) . '/partials/footer.php';

?>
