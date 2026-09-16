<?php

use Boneblaze\SignupLfchdOrg\Services\Csrf;

$pageTitle = 'Questions | LFCHD Signup';
require dirname(__DIR__) . '/partials/header.php';

$sectionNameById = [];
foreach ($sections as $section) {
    $sectionNameById[(int) $section['id']] = $section['title'];
}

function qh(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function qoptions(?string $json): string
{
    if (!$json) {
        return '';
    }

    $decoded = json_decode($json, true);
    return is_array($decoded) ? implode(PHP_EOL, $decoded) : '';
}
?>

<div class="container py-4 py-md-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Registration Questions</h1>
            <div class="text-muted"><?= qh($event['title']) ?></div>
        </div>
        <div class="lfchd-mobile-actions">
            <a href="/admin/events/<?= (int) $event['id'] ?>/edit" class="btn btn-outline-secondary">Edit Event</a>
            <a href="/admin" class="btn btn-outline-secondary">Back to Events</a>
        </div>
    </div>

    <?php if (isset($_GET['created'])): ?><div class="alert alert-success">Question added successfully.</div><?php endif; ?>
    <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Question updated successfully.</div><?php endif; ?>
    <?php if (isset($_GET['section_created'])): ?><div class="alert alert-success">Question section added successfully.</div><?php endif; ?>
    <?php if (isset($_GET['section_saved'])): ?><div class="alert alert-success">Question section updated successfully.</div><?php endif; ?>
    <?php if (isset($_GET['section_deleted'])): ?><div class="alert alert-success">Question section deleted. Questions assigned to it are now unsectioned.</div><?php endif; ?>
    <?php if (isset($_GET['question_copied'])): ?><div class="alert alert-success">Question copied successfully.</div><?php endif; ?>
    <?php if (isset($_GET['question_deleted'])): ?><div class="alert alert-success">Question deleted successfully.</div><?php endif; ?>
    <?php if (isset($_GET['section_duplicated'])): ?><div class="alert alert-success">Section and its questions duplicated successfully.</div><?php endif; ?>
    <?php if (!empty($_GET['error'])): ?><div class="alert alert-danger"><?= qh($_GET['error']) ?></div><?php endif; ?>


    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h2 class="h5 mb-1">Form Builder</h2>
            <div class="text-muted small">Open one section, then open only the question you want to edit.</div>
        </div>
        <details class="position-relative">
            <summary class="btn btn-primary">+ New Section</summary>
            <div class="card shadow position-absolute end-0 mt-2" style="z-index:20; width:min(700px, 90vw);">
                <div class="card-body">
                    <form method="post" action="/admin/events/<?= (int) $event['id'] ?>/question-sections">
                        <?= Csrf::field() ?>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Section Title - English</label>
                                <input type="text" name="title" class="form-control" maxlength="255" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Section Title - Spanish</label>
                                <input type="text" name="title_es" class="form-control" maxlength="255">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Instructions - English</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Instructions - Spanish</label>
                                <textarea name="description_es" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Create Section</button>
                    </form>
                </div>
            </div>
        </details>
    </div>

    <?php
    $builderGroups = [];
    foreach ($sections as $section) {
        $builderGroups[(int)$section['id']] = [
            'section' => $section,
            'questions' => [],
        ];
    }
    $builderGroups[0] = [
        'section' => null,
        'questions' => [],
    ];

    foreach ($questions as $builderQuestion) {
        $sid = (int)($builderQuestion['section_id'] ?? 0);
        if (!isset($builderGroups[$sid])) {
            $sid = 0;
        }
        $builderGroups[$sid]['questions'][] = $builderQuestion;
    }

    // Put unsectioned questions last.
    $unsectionedGroup = $builderGroups[0];
    unset($builderGroups[0]);
    $builderGroups[0] = $unsectionedGroup;
    ?>

    <div class="accordion" id="section-builder">
        <?php foreach ($builderGroups as $builderSectionId => $builderGroup): ?>
            <?php
            $builderSection = $builderGroup['section'];
            $builderQuestions = $builderGroup['questions'];
            $sectionTitle = $builderSection
                ? (string)$builderSection['title']
                : 'Additional Information / Unsectioned';
            ?>
            <div class="accordion-item mb-3 border rounded overflow-hidden">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed fw-semibold"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#builder-section-<?= (int)$builderSectionId ?>"
                        aria-expanded="false"
                    >
                        <span class="me-auto"><?= qh($sectionTitle) ?></span>
                        <span class="badge text-bg-secondary me-3">
                            <?= count($builderQuestions) ?> question<?= count($builderQuestions) === 1 ? '' : 's' ?>
                        </span>
                    </button>
                </h2>

                <div
                    id="builder-section-<?= (int)$builderSectionId ?>"
                    class="accordion-collapse collapse"
                    data-bs-parent="#section-builder"
                >
                    <div class="accordion-body bg-body-tertiary">
                        <?php if ($builderSection): ?>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <form method="post" action="/admin/events/<?= (int)$event['id'] ?>/question-sections/<?= (int)$builderSection['id'] ?>/duplicate">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-outline-primary btn-sm">
                                        Duplicate Section
                                    </button>
                                </form>
                            </div>
                            <details class="card mb-3">
                                <summary class="card-header fw-semibold" style="cursor:pointer;">Section Settings</summary>
                                <div class="card-body">
                                    <form method="post" action="/admin/events/<?= (int) $event['id'] ?>/question-sections/<?= (int) $builderSection['id'] ?>/edit">
                                        <?= Csrf::field() ?>
                                        <div class="row g-3">
                                            <div class="col-12 col-lg-5">
                                                <label class="form-label">Title - English</label>
                                                <input type="text" name="title" class="form-control" maxlength="255" required value="<?= qh($builderSection['title']) ?>">
                                            </div>
                                            <div class="col-12 col-lg-5">
                                                <label class="form-label">Title - Spanish</label>
                                                <input type="text" name="title_es" class="form-control" maxlength="255" value="<?= qh($builderSection['title_es'] ?? '') ?>">
                                            </div>
                                            <div class="col-12 col-lg-2">
                                                <label class="form-label">Order</label>
                                                <input type="number" name="sort_order" class="form-control" min="0" value="<?= (int)$builderSection['sort_order'] ?>">
                                            </div>
                                            <div class="col-12 col-lg-6">
                                                <label class="form-label">Instructions - English</label>
                                                <textarea name="description" class="form-control" rows="3"><?= qh($builderSection['description'] ?? '') ?></textarea>
                                            </div>
                                            <div class="col-12 col-lg-6">
                                                <label class="form-label">Instructions - Spanish</label>
                                                <textarea name="description_es" class="form-control" rows="3"><?= qh($builderSection['description_es'] ?? '') ?></textarea>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input type="checkbox" name="enabled" value="1" class="form-check-input" id="section-enabled-<?= (int)$builderSection['id'] ?>" <?= (int)$builderSection['enabled'] === 1 ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="section-enabled-<?= (int)$builderSection['id'] ?>">Enabled</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2 mt-3">
                                            <button type="submit" class="btn btn-outline-primary">Save Section</button>
                                        </div>
                                    </form>
                                    <form method="post" action="/admin/events/<?= (int)$event['id'] ?>/question-sections/<?= (int)$builderSection['id'] ?>/delete" class="mt-2" onsubmit="return confirm('Delete this section? Its questions will not be deleted; they will become unsectioned.');">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete Section</button>
                                    </form>
                                </div>
                            </details>
                        <?php endif; ?>

                        <?php if (!$builderQuestions): ?>
                            <div class="text-muted mb-3">No questions in this section yet.</div>
                        <?php endif; ?>

                        <div class="accordion question-accordion mb-3" id="questions-for-<?= (int)$builderSectionId ?>">
                            <?php foreach ($builderQuestions as $question): ?>
                                <div class="accordion-item">
                                    <h3 class="accordion-header">
                                        <button
                                            class="accordion-button collapsed py-2"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#builder-question-<?= (int)$question['id'] ?>"
                                            aria-expanded="false"
                                        >
                                            <span class="me-auto"><?= qh($question['question_text']) ?></span>
                                            <?php if ((int)$question['required'] === 1): ?>
                                                <span class="badge text-bg-danger me-2">Required</span>
                                            <?php endif; ?>
                                            <?php if ((int)$question['enabled'] !== 1): ?>
                                                <span class="badge text-bg-secondary me-2">Disabled</span>
                                            <?php endif; ?>
                                        </button>
                                    </h3>
                                    <div
                                        id="builder-question-<?= (int)$question['id'] ?>"
                                        class="accordion-collapse collapse"
                                        data-bs-parent="#questions-for-<?= (int)$builderSectionId ?>"
                                    >
                                        <div class="accordion-body">
                                            <div class="d-flex flex-column flex-lg-row gap-2 justify-content-between mb-3 pb-3 border-bottom">
                                                <form method="post" action="/admin/events/<?= (int)$event['id'] ?>/questions/<?= (int)$question['id'] ?>/copy" class="d-flex flex-column flex-sm-row gap-2">
                                                    <?= Csrf::field() ?>
                                                    <select name="section_id" class="form-select form-select-sm" aria-label="Destination section">
                                                        <option value="">Additional Information / Unsectioned</option>
                                                        <?php foreach ($sections as $copySection): ?>
                                                            <option value="<?= (int)$copySection['id'] ?>" <?= (int)($question['section_id'] ?? 0) === (int)$copySection['id'] ? 'selected' : '' ?>>
                                                                <?= qh($copySection['title']) ?><?= (int)$copySection['enabled'] !== 1 ? ' (Disabled)' : '' ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm text-nowrap">
                                                        Copy Question
                                                    </button>
                                                </form>

                                                <form
                                                    method="post"
                                                    action="/admin/events/<?= (int)$event['id'] ?>/questions/<?= (int)$question['id'] ?>/delete"
                                                    onsubmit="return confirm('Delete this question? This cannot be undone. Questions with existing registration answers will be protected and cannot be deleted.');"
                                                >
                                                    <?= Csrf::field() ?>
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        Delete Question
                                                    </button>
                                                </form>
                                            </div>

                    <form method="post" action="/admin/events/<?= (int) $event['id'] ?>/questions/<?= (int) $question['id'] ?>/edit">
                        <?= Csrf::field() ?>

                        <div class="mb-3">
                            <label class="form-label">Question - English</label>
                            <textarea name="question_text" class="form-control question-text-field" maxlength="500" rows="2" required><?= qh($question['question_text']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Question - Spanish</label>
                            <textarea name="question_text_es" class="form-control question-text-field" maxlength="500" rows="2"><?= qh($question['question_text_es'] ?? '') ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label class="form-label">Section</label>
                                <select name="section_id" class="form-select">
                                    <option value="">No Section / Additional Information</option>
                                    <?php foreach ($sections as $section): ?>
                                        <option value="<?= (int) $section['id'] ?>" <?= (int) ($question['section_id'] ?? 0) === (int) $section['id'] ? 'selected' : '' ?>>
                                            <?= qh($section['title']) ?><?= (int) $section['enabled'] !== 1 ? ' (Disabled)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Type</label>
                                <select name="question_type" class="form-select question-type-select">
                                    <?php foreach (['text'=>'Text','textarea'=>'Long Text','select'=>'Dropdown','checkbox'=>'Checkbox'] as $value=>$label): ?>
                                        <option value="<?= $value ?>" <?= $question['question_type'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Data Type</label>
                                <select name="data_type" class="form-select data-type-select">
                                    <?php foreach (['text'=>'Standard Text','email'=>'Email Address','phone'=>'Phone Number','date'=>'Date','birthdate'=>'Birthdate','number'=>'Number'] as $value=>$label): ?>
                                        <option value="<?= $value ?>" <?= ($question['data_type'] ?? 'text') === $value ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label">Order</label>
                                <input type="number" name="sort_order" class="form-control" min="0" value="<?= (int) $question['sort_order'] ?>">
                            </div>
                            <div class="col-6 col-md-2 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="required" value="1" class="form-check-input" id="required-<?= (int) $question['id'] ?>" <?= (int) $question['required'] === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="required-<?= (int) $question['id'] ?>">Required</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-2 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="enabled" value="1" class="form-check-input" id="enabled-<?= (int) $question['id'] ?>" <?= (int) $question['enabled'] === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="enabled-<?= (int) $question['id'] ?>">Enabled</label>
                                </div>
                            </div>
                        </div>

                        <?php
                        $validation = json_decode((string)($question['validation_json'] ?? ''), true);
                        $validation = is_array($validation) ? $validation : [];
                        ?>
                        <div class="birthdate-validation-fields mt-3" <?= ($question['data_type'] ?? 'text') === 'birthdate' ? '' : 'hidden' ?>>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Minimum Age</label>
                                    <input type="number" name="min_age" class="form-control" min="0" max="150" value="<?= qh($validation['min_age'] ?? '') ?>">
                                    <div class="form-text">Optional. Whole years.</div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Maximum Age</label>
                                    <input type="number" name="max_age" class="form-control" min="0" max="150" value="<?= qh($validation['max_age'] ?? '') ?>">
                                    <div class="form-text">Optional. Whole years.</div>
                                </div>
                            </div>
                        </div>

                        <div class="dropdown-options-fields mt-3">
                            <div class="row g-3">
                                <div class="col-12 col-lg-6">
                                    <label class="form-label">Options - English</label>
                                    <textarea name="options" class="form-control" rows="4"><?= qh(qoptions($question['options_json'] ?? null)) ?></textarea>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <label class="form-label">Options - Spanish</label>
                                    <textarea name="options_es" class="form-control" rows="4"><?= qh(qoptions($question['options_json_es'] ?? null)) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <?php $editingQuestion = $question; require __DIR__ . '/question_rule_fields.php'; ?>

                        <button type="submit" class="btn btn-outline-primary">Save Question</button>
                    </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <details class="card border-primary-subtle">
                            <summary class="card-header fw-semibold" style="cursor:pointer;">
                                + Add Question<?= $builderSection ? ' to ' . qh((string)$builderSection['title']) : ' to Additional Information' ?>
                            </summary>
                            <div class="card-body">
            <form method="post" action="/admin/events/<?= (int) $event['id'] ?>/questions">
                <?= Csrf::field() ?>

                <div class="mb-3">
                    <label class="form-label">Question - English</label>
                    <textarea name="question_text" class="form-control question-text-field" maxlength="500" rows="2" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Question - Spanish</label>
                    <textarea name="question_text_es" class="form-control question-text-field" maxlength="500" rows="2"></textarea>
                    <div class="form-text">Optional. If blank, the public form will use the English question.</div>
                </div>

                <div class="row g-3">
                    <input type="hidden" name="section_id" value="<?= $builderSectionId ?: '' ?>">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Type</label>
                        <select name="question_type" class="form-select question-type-select">
                            <option value="text">Text</option>
                            <option value="textarea">Long Text</option>
                            <option value="select">Dropdown</option>
                            <option value="checkbox">Checkbox</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Data Type</label>
                        <select name="data_type" class="form-select data-type-select">
                            <option value="text">Standard Text</option>
                            <option value="email">Email Address</option>
                            <option value="phone">Phone Number</option>
                            <option value="date">Date</option>
                            <option value="birthdate">Birthdate</option>
                            <option value="number">Number</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input type="checkbox" name="required" value="1" class="form-check-input" id="new-required">
                            <label class="form-check-label" for="new-required">Required</label>
                        </div>
                    </div>
                </div>

                <div class="birthdate-validation-fields mt-3" hidden>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Minimum Age</label>
                            <input type="number" name="min_age" class="form-control" min="0" max="150">
                            <div class="form-text">Optional. Whole years.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Maximum Age</label>
                            <input type="number" name="max_age" class="form-control" min="0" max="150">
                            <div class="form-text">Optional. Whole years.</div>
                        </div>
                    </div>
                </div>

                <div class="dropdown-options-fields mt-3">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Options - English</label>
                            <textarea name="options" class="form-control" rows="4" placeholder="Enter one option per line"></textarea>
                            <div class="form-text">Dropdown: at least two choices. Checkbox: leave blank for one checkbox, or enter one choice per line for multiple checkboxes.</div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Options - Spanish</label>
                            <textarea name="options_es" class="form-control" rows="4" placeholder="Enter one translated option per line"></textarea>
                            <div class="form-text">Optional. Keep the same number and order as the English options.</div>
                        </div>
                    </div>
                </div>

                <?php require __DIR__ . '/question_rule_fields.php'; ?>

                <button type="submit" class="btn btn-primary">Add Question</button>
            </form>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach(function (form) {
        const typeSelect = form.querySelector('.question-type-select');
        const optionsFields = form.querySelector('.dropdown-options-fields');
        const questionTextFields = form.querySelectorAll('.question-text-field');
        const qualificationToggle = form.querySelector('.qualification-toggle');
        const qualificationFields = form.querySelector('.qualification-fields');

        if (typeSelect) {
            function updateQuestionFields() {
                const type = typeSelect.value;
                const usesOptions = type === 'select' || type === 'checkbox';

                if (optionsFields) {
                    optionsFields.classList.toggle('d-none', !usesOptions);
                    optionsFields.querySelectorAll('textarea').forEach(function (field) {
                        field.disabled = !usesOptions;
                    });
                }

                questionTextFields.forEach(function (field) {
                    field.rows = type === 'textarea' ? 5 : 2;
                });
            }
            typeSelect.addEventListener('change', updateQuestionFields);
            updateQuestionFields();
        }

        if (qualificationToggle && qualificationFields) {
            function updateQualificationFields() {
                const enabled = qualificationToggle.checked;
                qualificationFields.classList.toggle('d-none', !enabled);
                qualificationFields.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.disabled = !enabled;
                });
            }
            qualificationToggle.addEventListener('change', updateQualificationFields);
            updateQualificationFields();
        }
    });
});

    function updateDataTypeFields(form) {
        const questionType = form.querySelector('.question-type-select');
        const dataType = form.querySelector('.data-type-select');
        const birthFields = form.querySelector('.birthdate-validation-fields');
        if (!dataType) return;

        const supported = !questionType
            || questionType.value === 'text'
            || questionType.value === 'textarea';

        dataType.disabled = !supported;
        if (!supported) dataType.value = 'text';

        if (birthFields) {
            birthFields.hidden = !supported || dataType.value !== 'birthdate';
            birthFields.querySelectorAll('input').forEach(input => {
                input.disabled = birthFields.hidden;
            });
        }
    }

    document.querySelectorAll('form').forEach(form => {
        if (!form.querySelector('.data-type-select')) return;
        updateDataTypeFields(form);
        form.addEventListener('change', event => {
            if (
                event.target.classList.contains('question-type-select')
                || event.target.classList.contains('data-type-select')
            ) {
                updateDataTypeFields(form);
            }
        });
    });
</script>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
