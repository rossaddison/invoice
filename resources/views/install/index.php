<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @var Yiisoft\View\WebView $this
 * @var string $csrf
 * @var string $currentStep 'requirements'|'database'|'build'|'handoff'
 * @var list<array{name: string, mandatory: bool, condition: bool, by: string, memo: string, error: bool, warning: bool}> $requirements
 * @var array{total: int, errors: int, warnings: int} $summary
 * @var string $dbHost
 * @var string $dbName
 * @var string $dbUser
 *
 * English-only for now — this wizard is new and hasn't yet gone through the
 * app's translation-key rollout (see resources/messages/en/app.php).
 */
$this->setTitle('Yii3-i Setup');

$steps = ['requirements' => 'Requirements', 'database' => 'Database', 'build' => 'Build Tables', 'handoff' => 'Create Admin'];
$stepKeys = array_keys($steps);
$currentIndex = array_search($currentStep, $stepKeys, true);
if ($currentIndex === false) {
    $currentIndex = 0;
}
?>
<div class="container py-5" data-install-wizard data-current-step="<?= H::encode((string) $currentIndex) ?>">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="d-flex justify-content-between mb-4">
                <?php foreach (array_values($steps) as $index => $label): ?>
                    <div class="text-center flex-fill">
                        <div class="install-step-circle <?php
                            if ($index < $currentIndex) {
                                echo 'is-complete';
                            } elseif ($index === $currentIndex) {
                                echo 'is-active';
                            } else {
                                echo 'is-pending';
                            }
                        ?>"><?= $index + 1 ?></div>
                        <small class="d-block mt-1"><?= H::encode($label) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card border shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Requirements</h5>
                    <?php if ($summary['errors'] > 0): ?>
                        <div class="alert alert-danger">
                            <?= $summary['errors'] ?> mandatory requirement<?= $summary['errors'] === 1 ? '' : 's' ?> not met — resolve these before continuing.
                        </div>
                    <?php endif; ?>
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Requirement</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requirements as $requirement): ?>
                                <tr class="<?= $requirement['error'] ? 'table-danger' : ($requirement['warning'] ? 'table-warning' : '') ?>">
                                    <td>
                                        <?= H::encode($requirement['name']) ?>
                                        <?php if ($requirement['mandatory']): ?><span class="badge bg-secondary">required</span><?php endif; ?>
                                    </td>
                                    <td><?= $requirement['condition'] ? '✓ OK' : '✗ FAIL' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if ($currentStep === 'database'): ?>
                        <h5 class="card-title mb-3">Database Configuration</h5>
                        <div id="install-db-result"></div>
                        <div class="mb-2">
                            <label class="form-label" for="install-db-host">Host</label>
                            <?= H::input('text', 'Install[dbHost]', $dbHost, ['class' => 'form-control', 'id' => 'install-db-host']) ?>
                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="install-db-name">Database name</label>
                            <?= H::input('text', 'Install[dbName]', $dbName, ['class' => 'form-control', 'id' => 'install-db-name']) ?>
                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="install-db-user">Username</label>
                            <?= H::input('text', 'Install[dbUser]', $dbUser, ['class' => 'form-control', 'id' => 'install-db-user']) ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="install-db-password">Password</label>
                            <?= H::input('password', 'Install[dbPassword]', '', ['class' => 'form-control', 'id' => 'install-db-password', 'autocomplete' => 'new-password']) ?>
                        </div>
                        <input type="hidden" id="install-csrf" name="_csrf" value="<?= H::encode($csrf) ?>">
                        <button type="button" class="btn btn-outline-secondary" data-install-action="test-connection">Test Connection</button>
                        <button type="button" class="btn btn-primary" data-install-action="save-database" disabled>Save &amp; Continue &rarr;</button>
                    <?php elseif ($currentStep === 'build'): ?>
                        <h5 class="card-title mb-3">Build Database Tables</h5>
                        <p>Database connection confirmed. Click below to create all required tables.</p>
                        <div id="install-build-result"></div>
                        <input type="hidden" id="install-csrf" name="_csrf" value="<?= H::encode($csrf) ?>">
                        <button type="button" class="btn btn-primary" data-install-action="build-tables">Build Tables</button>
                    <?php elseif ($currentStep === 'handoff'): ?>
                        <h5 class="card-title mb-3">Create Admin Account</h5>
                        <p>Tables are ready. Create your first user below — it will automatically become the admin account, and two-factor authentication (via any TOTP app, e.g. <a href="https://getaegis.app">Aegis</a>) will be required on first login.</p>
                        <a class="btn btn-primary" href="/signup">Continue to Signup &rarr;</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
