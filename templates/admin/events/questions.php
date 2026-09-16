<?php

use Boneblaze\SignupLfchdOrg\Services\Csrf;

$pageTitle = 'Questions | LFCHD Signup';

require dirname(__DIR__) . '/partials/header.php';

?>

<div class="container py-4 py-md-5">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
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

        <div class="lfchd-mobile-actions">
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

        <div class="card-body p-3 p-md-4">
            <form
                method="post"
                action="/admin/events/<?= (int) $event['id'] ?>/questions"
            >
                <?= Csrf::field() ?>

                <div class="mb-3">
                    <label class="form-label">
                        Question - English
                    </label>

                    <textarea
                        name="question_text"
                        class="form-control question-text-field"
                        maxlength="500"
                        rows="2"
                        required
                    ></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Question - Spanish
                    </label>

                    <textarea
                        name="question_text_es"
                        class="form-control question-text-field"
                        maxlength="500"
                        rows="2"
                    ></textarea>

                    <div class="form-text">
                        Optional. If blank, the public form will use the English question.
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label">
                            Type
                        </label>

                        <select
                            name="question_type"
                            class="form-select question-type-select"
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

                    <div class="col-12 col-md-4 d-flex align-items-end">
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

                <div class="dropdown-options-fields mt-3">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">
                                Options - English
                            </label>

                            <textarea
                                name="options"
                                class="form-control"
                                rows="4"
                                placeholder="Enter one option per line"
                            ></textarea>

                            <div class="form-text">
                                For Dropdown questions, enter at least two choices. For Checkbox questions, leave blank for a single yes/no checkbox or enter one choice per line for multiple checkboxes.
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">
                                Options - Spanish
                            </label>

                            <textarea
                                name="options_es"
                                class="form-control"
                                rows="4"
                                placeholder="Enter one translated option per line"
                            ></textarea>

                            <div class="form-text">
                                Optional. If used, enter the same number of translated choices and keep them
                                in the same order as the English options.
                            </div>
                        </div>
                    </div>
                </div>

                <details class="border rounded mb-4 bg-light mt-4">
                    <summary class="p-3 fw-semibold" style="cursor: pointer;">
                        Conditional Display
                    </summary>

                    <div class="p-3 pt-0">
                        <div class="row g-3">
                            <div class="col-12 col-lg-6">
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

                            <div class="col-12 col-md-6 col-lg-3">
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

                            <div class="col-12 col-md-6 col-lg-3">
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
                            Conditions continue to use the stored English answer value.
                            Only Dropdown and Checkbox questions can control another question.
                            For a single checkbox, use 1 for checked and 0 for unchecked.
                            Multi-option checkbox conditions will use the English option value.
                        </div>
                    </div>
                </details>


                <details class="border rounded mb-4 bg-light">
                    <summary class="p-3 fw-semibold" style="cursor: pointer;">
                        Registration Qualification
                    </summary>

                    <div class="p-3 pt-0">
                        <div class="form-check mb-3">
                            <input
                                type="checkbox"
                                name="blocks_registration"
                                value="1"
                                class="form-check-input qualification-toggle"
                                id="new-blocks-registration"
                            >
                            <label
                                class="form-check-label"
                                for="new-blocks-registration"
                            >
                                Block registration based on this answer
                            </label>
                        </div>

                        <div class="qualification-fields">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Condition</label>
                                    <select name="blocking_operator" class="form-select">
                                        <option value="equals">Equals</option>
                                        <option value="not_equals">Does Not Equal</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-8">
                                    <label class="form-label">Value</label>
                                    <input
                                        type="text"
                                        name="blocking_value"
                                        class="form-control"
                                        placeholder="Example: No"
                                    >
                                </div>
                                <div class="col-12 col-lg-6">
                                    <label class="form-label">Blocking Message - English</label>
                                    <textarea
                                        name="blocking_message"
                                        class="form-control"
                                        rows="3"
                                        maxlength="2000"
                                    ></textarea>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <label class="form-label">Blocking Message - Spanish</label>
                                    <textarea
                                        name="blocking_message_es"
                                        class="form-control"
                                        rows="3"
                                        maxlength="2000"
                                    ></textarea>
                                    <div class="form-text">
                                        Optional. If blank, the public form will use the English blocking message.
                                    </div>
                                </div>
                            </div>

                            <div class="form-text mt-2">
                                The value uses the stored English answer. For Dropdown and multi-option Checkbox
                                questions, enter an English option exactly as configured above. For a single
                                Checkbox, use 1 for checked and 0 for unchecked.
                            </div>
                        </div>
                    </div>
                </details>

                <div class="lfchd-mobile-actions">
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Add Question
                    </button>
                </div>
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
            $optionsTextEs = '';

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

            if (!empty($question['options_json_es'])) {
                $decodedEs = json_decode(
                    $question['options_json_es'],
                    true
                );

                if (is_array($decodedEs)) {
                    $optionsTextEs = implode(
                        PHP_EOL,
                        $decodedEs
                    );
                }
            }
            ?>

            <div class="card shadow-sm mb-3">
                <div class="card-body p-3 p-md-4">

                    <form
                        method="post"
                        action="/admin/events/<?= (int) $event['id'] ?>/questions/<?= (int) $question['id'] ?>/edit"
                    >
                        <?= Csrf::field() ?>

                        <div class="mb-3">
                            <label class="form-label">
                                Question - English
                            </label>

                            <textarea
                                name="question_text"
                                class="form-control question-text-field"
                                maxlength="500"
                                rows="2"
                                required
                            ><?= htmlspecialchars(
                                $question['question_text'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Question - Spanish
                            </label>

                            <textarea
                                name="question_text_es"
                                class="form-control question-text-field"
                                maxlength="500"
                                rows="2"
                            ><?= htmlspecialchars(
                                $question['question_text_es'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label class="form-label">
                                    Type
                                </label>

                                <select
                                    name="question_type"
                                    class="form-select question-type-select"
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

                            <div class="col-12 col-md-2">
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

                            <div class="col-12 col-md-3 d-flex align-items-end">
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

                            <div class="col-12 col-md-3 d-flex align-items-end">
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

                        <div class="dropdown-options-fields mt-3">
                            <div class="row g-3">
                                <div class="col-12 col-lg-6">
                                    <label class="form-label">
                                        Options - English
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
                                </div>

                                <div class="col-12 col-lg-6">
                                    <label class="form-label">
                                        Options - Spanish
                                    </label>

                                    <textarea
                                        name="options_es"
                                        class="form-control"
                                        rows="4"
                                    ><?= htmlspecialchars(
                                        $optionsTextEs,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?></textarea>

                                    <div class="form-text">
                                        If used, the number and order of Spanish options must
                                        match the English options.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <details class="border rounded mb-4 bg-light mt-4">
                            <summary class="p-3 fw-semibold" style="cursor: pointer;">
                                Conditional Display
                            </summary>

                            <div class="p-3 pt-0">
                                <div class="row g-3">
                                    <div class="col-12 col-lg-6">
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

                                    <div class="col-12 col-md-6 col-lg-3">
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

                                    <div class="col-12 col-md-6 col-lg-3">
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
                                    Conditions continue to use the stored English answer value.
                                    For a single checkbox, use 1 for checked and 0 for unchecked.
                                    Multi-option checkbox conditions will use the English option value.
                                </div>
                            </div>
                        </details>


                        <details class="border rounded mb-4 bg-light">
                            <summary class="p-3 fw-semibold" style="cursor: pointer;">
                                Registration Qualification
                            </summary>

                            <div class="p-3 pt-0">
                                <div class="form-check mb-3">
                                    <input
                                        type="checkbox"
                                        name="blocks_registration"
                                        value="1"
                                        class="form-check-input qualification-toggle"
                                        id="blocks-registration-<?= (int) $question['id'] ?>"
                                        <?= (int) ($question['blocks_registration'] ?? 0) === 1
                                            ? 'checked'
                                            : '' ?>
                                    >
                                    <label
                                        class="form-check-label"
                                        for="blocks-registration-<?= (int) $question['id'] ?>"
                                    >
                                        Block registration based on this answer
                                    </label>
                                </div>

                                <div class="qualification-fields">
                                    <div class="row g-3">
                                        <div class="col-12 col-md-4">
                                            <label class="form-label">Condition</label>
                                            <select name="blocking_operator" class="form-select">
                                                <option
                                                    value="equals"
                                                    <?= ($question['blocking_operator'] ?? 'equals') === 'equals'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Equals
                                                </option>
                                                <option
                                                    value="not_equals"
                                                    <?= ($question['blocking_operator'] ?? '') === 'not_equals'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Does Not Equal
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-12 col-md-8">
                                            <label class="form-label">Value</label>
                                            <input
                                                type="text"
                                                name="blocking_value"
                                                class="form-control"
                                                value="<?= htmlspecialchars(
                                                    (string) ($question['blocking_value'] ?? ''),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                                placeholder="Example: No"
                                            >
                                        </div>

                                        <div class="col-12 col-lg-6">
                                            <label class="form-label">
                                                Blocking Message - English
                                            </label>
                                            <textarea
                                                name="blocking_message"
                                                class="form-control"
                                                rows="3"
                                                maxlength="2000"
                                            ><?= htmlspecialchars(
                                                (string) ($question['blocking_message'] ?? ''),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?></textarea>
                                        </div>

                                        <div class="col-12 col-lg-6">
                                            <label class="form-label">
                                                Blocking Message - Spanish
                                            </label>
                                            <textarea
                                                name="blocking_message_es"
                                                class="form-control"
                                                rows="3"
                                                maxlength="2000"
                                            ><?= htmlspecialchars(
                                                (string) ($question['blocking_message_es'] ?? ''),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?></textarea>
                                            <div class="form-text">
                                                Optional. If blank, the public form will use the English blocking message.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-text mt-2">
                                        The value uses the stored English answer. For Dropdown and multi-option Checkbox
                                        questions, enter an English option exactly as configured above. For a single
                                        Checkbox, use 1 for checked and 0 for unchecked.
                                    </div>
                                </div>
                            </div>
                        </details>

                        <div class="lfchd-mobile-actions">
                            <button
                                type="submit"
                                class="btn btn-outline-primary"
                            >
                                Save Question
                            </button>
                        </div>
                    </form>

                </div>
            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach(function (form) {
        const typeSelect = form.querySelector('.question-type-select');
        const optionsFields = form.querySelector('.dropdown-options-fields');
        const questionTextFields = form.querySelectorAll('.question-text-field');
        const qualificationToggle = form.querySelector('.qualification-toggle');
        const qualificationFields = form.querySelector('.qualification-fields');

        if (!typeSelect) {
            return;
        }

        function updateQuestionFields() {
            const type = typeSelect.value;
            const usesOptions =
                type === 'select'
                || type === 'checkbox';

            if (optionsFields) {
                optionsFields.classList.toggle(
                    'd-none',
                    !usesOptions
                );

                optionsFields
                    .querySelectorAll('textarea')
                    .forEach(function (field) {
                        field.disabled = !usesOptions;
                    });
            }

            questionTextFields.forEach(function (field) {
                field.rows = type === 'textarea' ? 5 : 2;
            });
        }

        function updateQualificationFields() {
            if (!qualificationFields) {
                return;
            }

            const enabled =
                qualificationToggle
                && qualificationToggle.checked;

            qualificationFields.classList.toggle(
                'd-none',
                !enabled
            );

            qualificationFields
                .querySelectorAll('input, select, textarea')
                .forEach(function (field) {
                    field.disabled = !enabled;
                });
        }

        typeSelect.addEventListener(
            'change',
            updateQuestionFields
        );

        if (qualificationToggle) {
            qualificationToggle.addEventListener(
                'change',
                updateQualificationFields
            );
        }

        updateQuestionFields();
        updateQualificationFields();
    });
});
</script>

<?php

require dirname(__DIR__) . '/partials/footer.php';

?>
