<?php
$isEdit = isset($editingQuestion);
$current = $isEdit ? $editingQuestion : [];
$currentId = (int) ($current['id'] ?? 0);
?>
<details class="border rounded mb-4 bg-light mt-4">
    <summary class="p-3 fw-semibold" style="cursor:pointer;">Conditional Display</summary>
    <div class="p-3 pt-0">
        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <label class="form-label">Show this question only when</label>
                <select name="conditional_question_id" class="form-select">
                    <option value="">Always show</option>
                    <?php foreach ($conditionQuestions as $conditionQuestion): ?>
                        <?php if ($currentId && (int) $conditionQuestion['id'] === $currentId) continue; ?>
                        <option value="<?= (int) $conditionQuestion['id'] ?>"
                            <?= (int) ($current['conditional_question_id'] ?? 0) === (int) $conditionQuestion['id'] ? 'selected' : '' ?>>
                            <?= qh($conditionQuestion['question_text']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <label class="form-label">Condition</label>
                <select name="conditional_operator" class="form-select">
                    <option value="equals" <?= ($current['conditional_operator'] ?? 'equals') === 'equals' ? 'selected' : '' ?>>Equals</option>
                    <option value="not_equals" <?= ($current['conditional_operator'] ?? '') === 'not_equals' ? 'selected' : '' ?>>Does Not Equal</option>
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <label class="form-label">Value</label>
                <input type="text" name="conditional_value" class="form-control"
                    value="<?= qh($current['conditional_value'] ?? '') ?>" placeholder="Example: Yes">
            </div>
        </div>
        <div class="form-text mt-2">
            Conditions use the stored English answer value. Only Dropdown and Checkbox questions can control another question.
            For a single checkbox use 1 for checked and 0 for unchecked.
        </div>
    </div>
</details>

<details class="border rounded mb-4 bg-light">
    <summary class="p-3 fw-semibold" style="cursor:pointer;">Registration Qualification</summary>
    <div class="p-3 pt-0">
        <div class="form-check mb-3">
            <input type="checkbox" name="blocks_registration" value="1"
                class="form-check-input qualification-toggle"
                id="blocks-registration-<?= $currentId ?: 'new' ?>"
                <?= (int) ($current['blocks_registration'] ?? 0) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="blocks-registration-<?= $currentId ?: 'new' ?>">
                Block registration based on this answer
            </label>
        </div>
        <div class="qualification-fields">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label">Condition</label>
                    <select name="blocking_operator" class="form-select">
                        <option value="equals" <?= ($current['blocking_operator'] ?? 'equals') === 'equals' ? 'selected' : '' ?>>Equals</option>
                        <option value="not_equals" <?= ($current['blocking_operator'] ?? '') === 'not_equals' ? 'selected' : '' ?>>Does Not Equal</option>
                    </select>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label">Value</label>
                    <input type="text" name="blocking_value" class="form-control"
                        value="<?= qh($current['blocking_value'] ?? '') ?>" placeholder="Example: No">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Blocking Message - English</label>
                    <textarea name="blocking_message" class="form-control" rows="3" maxlength="2000"><?= qh($current['blocking_message'] ?? '') ?></textarea>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Blocking Message - Spanish</label>
                    <textarea name="blocking_message_es" class="form-control" rows="3" maxlength="2000"><?= qh($current['blocking_message_es'] ?? '') ?></textarea>
                    <div class="form-text">Optional. English is used if blank.</div>
                </div>
            </div>
            <div class="form-text mt-2">
                For Dropdown and multi-option Checkbox questions, use an English option exactly as configured.
                For a single Checkbox, use 1 for checked and 0 for unchecked.
            </div>
        </div>
    </div>
</details>
<?php unset($editingQuestion); ?>
