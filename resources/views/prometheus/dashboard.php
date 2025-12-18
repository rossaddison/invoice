<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Ul;
use Yiisoft\Html\Tag\Li;

/**
 * @var string $title
 * @var string $refresh_url
 * @var string $metrics_url
 * @var string $health_url
 * @var array $health
 * @var array $system_info
 * @var array $metrics
 */
?>
<?= Html::tag('h1', 'Prometheus Metrics Dashboard') ?>

<?= Html::cssFile('@assetUrl/css/bootstrap.min.css') ?>
<?= Html::cssFile('@assetUrl/css/style.css') ?>

<?= Html::openTag('div', ['class' => 'container-fluid']) ?>
    <?= Html::openTag('div', ['class' => 'row']) ?>
        <?= Html::openTag('div', ['class' => 'col-12']) ?>
            <?= Html::openTag('div', ['class' => 'card']) ?>
    <?= Html::openTag('div', ['class' =>
        'card-header d-flex justify-content-between align-items-center']) ?>
    <?= Html::openTag('h3', ['class' => 'card-title']); ?>
        <?= Html::encode($title) ?>
    <?= Html::closeTag('h3') ?>
        <?= Html::a('Refresh',
            $refresh_url,
            ['class' => 'btn btn-primary btn-sm']
        ) ?>
        <?= Html::a(
            'Raw Metrics',
            $metrics_url,
            ['class' => 'btn btn-secondary btn-sm', 'target' => '_blank']
        ) ?>
        <?= Html::a(
            ' Health Check',
            $health_url,
            ['class' => 'btn btn-info btn-sm', 'target' => '_blank']
        ) ?>
    <?= Html::closeTag('div') ?>
    <?= Html::closeTag('div') ?>
    <?= Html::openTag('div', ['class' => 'card-body']) ?>
    <!-- 'Health Status' -->
    <?= Html::openTag('div', ['class' => 'row mb-4']) ?>
        <?= Html::openTag('div', ['class' => 'col-12']) ?>
            <?= Html::openTag('div', ['class' => 'card']) ?>
                <?= Html::openTag('div', ['class' => 'card-header']) ?>
                    <?= Html::openTag('h5', ['class' => 'mb-0']) ?>
                        💓 Application Health
                        <?php
                        $healthStatus = (string)
                            ($health['status'] ?? 'unknown');
                        $badgeClass = 'badge badge-' .
                            ($healthStatus === 'healthy' ? 'success' :
                                ($healthStatus === 'warning' ? 'warning' :
                                'danger'));
                        ?>
                        <?= Html::tag('span', strtoupper($healthStatus),
                                ['class' => $badgeClass]) ?>
                    <?= Html::closeTag('h5') ?>
                <?= Html::closeTag('div') ?>
                <?= Html::openTag('div', ['class' => 'card-body']) ?>
                    <?php if (isset($health['checks'])): ?>
                        <?= Html::openTag('div', ['class' => 'row']) ?>
                            <?php
                                /**
                                 * @var string|int $checkName
                                 * @var array $checkData
                                 * @var string $checkData['status']
                                 */
                                foreach ($health['checks'] as $checkName => $checkData): ?>
                                <?= Html::openTag('div', ['class' => 'col-md-4 mb-2']) ?>
                                    <?= Html::openTag('div', ['class' => 'd-flex align-items-center']) ?>
                                        <?php
                                        $iconClass = 'fas fa-' . ($checkData['status'] === 'ok' ? 'check-circle text-success' : ($checkData['status'] === 'warning' ? 'exclamation-triangle text-warning' : 'times-circle text-danger')) . ' mr-2';
                                        ?>
                                        <?php $checkNameStr = is_string($checkName) ? $checkName : 'check_' . $checkName; ?>
                                        <?= Html::tag('strong', ucfirst(str_replace('_', ' ', $checkNameStr)) . ':') ?>
                                        <?= Html::tag('span', ($checkData['status'] ?? 'unknown'), ['class' => 'ml-2']) ?>
                                    <?= Html::closeTag('div') ?>
                                    <?php if (isset($checkData['usage_percent'])): ?>
                                        <?= Html::tag('small', 'Memory: ' . (string) ($checkData['usage_percent'] ?? '0') . '%', ['class' => 'text-muted']) ?>
                                    <?php endif; ?>
                                <?= Html::closeTag('div') ?>
                            <?php endforeach; ?>
                        <?= Html::closeTag('div') ?>
                    <?php endif; ?>
                <?= Html::closeTag('div') ?>
            <?= Html::closeTag('div') ?>
        <?= Html::closeTag('div') ?>
    <?= Html::closeTag('div') ?>
    <!-- 'System Information' -->
    <?= Html::openTag('div', ['class' => 'row mb-4']) ?>
        <?= Html::openTag('div', ['class' => 'col-12']) ?>
            <?= Html::openTag('div', ['class' => 'card']) ?>
                <?= Html::openTag('div', ['class' => 'card-header']) ?>
                    <?= Html::openTag('h5', ['class' => 'mb-0']) ?>
                        System Information
                    <?= Html::closeTag('h5') ?>
                <?= Html::closeTag('div') ?>
                <?= Html::openTag('div', ['class' => 'card-body']) ?>
                    <?= Html::openTag('div', ['class' => 'row']) ?>
                        <?= Html::openTag('div', ['class' => 'col-md-3']) ?>
                            <?= Html::tag('strong', 'PHP Version:') ?><?= Html::tag('br') ?>
                            <?= Html::tag('span', (string) $system_info['php_version'], ['class' => 'text-muted']) ?>
                        <?= Html::closeTag('div') ?>
                        <?= Html::openTag('div', ['class' => 'col-md-3']) ?>
                            <?= Html::tag('strong', 'Operating System:') ?><?= Html::tag('br') ?>
                            <?= Html::tag('span', (string) $system_info['os'], ['class' => 'text-muted']) ?>
                        <?= Html::closeTag('div') ?>
                        <?= Html::openTag('div', ['class' => 'col-md-3']) ?>
                            <?= Html::tag('strong', 'Memory Limit:') ?><?= Html::tag('br') ?>
                            <?= Html::tag('span', (string) $system_info['memory_limit'], ['class' => 'text-muted']) ?>
                        <?= Html::closeTag('div') ?>
                        <?= Html::openTag('div', ['class' => 'col-md-3']) ?>
                            <?= Html::tag('strong', 'Memory Usage:') ?><?= Html::tag('br') ?>
                            <?= Html::tag('span', number_format((float) ($system_info['memory_usage'] ?? 0) / 1024 / 1024, 2) . ' MB', ['class' => 'text-muted']) ?>
                        <?= Html::closeTag('div') ?>
                    <?= Html::closeTag('div') ?>
                    <?= Html::openTag('div', ['class' => 'row mt-2']) ?>
                        <?= Html::openTag('div', ['class' => 'col-md-6']) ?>
                            <?= Html::tag('strong', 'Server Time:') ?><?= Html::tag('br') ?>
                            <?= Html::tag('span', (string) ($system_info['server_time'] ?? 'Unknown'), ['class' => 'text-muted']) ?>
                        <?= Html::closeTag('div') ?>
                        <?= Html::openTag('div', ['class' => 'col-md-6']) ?>
                            <?= Html::tag('strong', 'Peak Memory:') ?><?= Html::tag('br') ?>
                            <?= Html::tag('span', number_format((float) ($system_info['peak_memory'] ?? 0) / 1024 / 1024, 2) . ' MB', ['class' => 'text-muted']) ?>
                        <?= Html::closeTag('div') ?>
                    <?= Html::closeTag('div') ?>
                <?= Html::closeTag('div') ?>
            <?= Html::closeTag('div') ?>
        <?= Html::closeTag('div') ?>
    <?= Html::closeTag('div') ?>

    <!-- 'Metrics Overview' -->
    <?= Html::openTag('div', ['class' => 'row']) ?>
        <?= Html::openTag('div', ['class' => 'col-12']) ?>
            <?= Html::openTag('div', ['class' => 'card']) ?>
                <?= Html::openTag('div', ['class' => 'card-header']) ?>
                    <?= Html::openTag('h5', ['class' => 'mb-0']) ?>
                        Metrics Overview
                    <?= Html::closeTag('h5') ?>
                <?= Html::closeTag('div') ?>
                <?= Html::openTag('div', ['class' => 'card-body']) ?>
                    <?php if (!empty($metrics)): ?>
                        <div class="row">
                            <?php
                            $metricType = '';
                            $metricTypes = ['counter' => 0, 'gauge' => 0, 'histogram' => 0, 'summary' => 0];
                            $metricTypesCounter = $metricTypes['counter'];
                            $metricTypesGauge = $metricTypes['gauge'];
                            $metricTypesHistogram = $metricTypes['histogram'];
                            $metricTypesSummary = $metricTypes['summary'];

                            /**
                             * @var array $metric
                             */
                            foreach ($metrics as $metric) {
                                $metricType = (string) $metric['type'];
                                $mT = $metricTypes[$metricType];
                                $mT++;
                            }
                            ?>
                            <?= Html::openTag('div', ['class' => 'col-md-3']) ?>
                                <?= Html::openTag('div', ['class' => 'info-box']) ?>
                                    <?= Html::openTag('span', ['class' => 'info-box-icon bg-info']) ?>
                                    <?= Html::closeTag('span') ?>
                                    <?= Html::openTag('div', ['class' => 'info-box-content']) ?>
                                        <?= Html::tag('span', 'Counters', ['class' => 'info-box-text']) ?>
                                        <?= Html::tag('span', (string) $metricTypesCounter, ['class' => 'info-box-number']) ?>
                                    <?= Html::closeTag('div') ?>
                                <?= Html::closeTag('div') ?>
                            <?= Html::closeTag('div') ?>
                            <?= Html::openTag('div', ['class' => 'col-md-3']) ?>
                                <?= Html::openTag('div', ['class' => 'info-box']) ?>
                                    <?= Html::openTag('span', ['class' => 'info-box-icon bg-success']) ?>
                                    <?= Html::closeTag('span') ?>
                                    <?= Html::openTag('div', ['class' => 'info-box-content']) ?>
                                        <?= Html::tag('span', 'Gauges', ['class' => 'info-box-text']) ?>
                                        <?= Html::tag('span', (string) $metricTypesGauge, ['class' => 'info-box-number']) ?>
                                    <?= Html::closeTag('div') ?>
                                <?= Html::closeTag('div') ?>
                            <?= Html::closeTag('div') ?>
                            <?= Html::openTag('div', ['class' => 'col-md-3']) ?>
                                <?= Html::openTag('div', ['class' => 'info-box']) ?>
                                    <?= Html::openTag('span', ['class' => 'info-box-icon bg-warning']) ?>
                                    <?= Html::closeTag('span') ?>
                                    <?= Html::openTag('div', ['class' => 'info-box-content']) ?>
                                        <?= Html::tag('span', 'Histograms', ['class' => 'info-box-text']) ?>
                                        <?= Html::tag('span', (string) $metricTypesHistogram, ['class' => 'info-box-number']) ?>
                                    <?= Html::closeTag('div') ?>
                                <?= Html::closeTag('div') ?>
                            <?= Html::closeTag('div') ?>
                            <?= Html::openTag('div', ['class' => 'col-md-3']) ?>
                                <?= Html::openTag('div', ['class' => 'info-box']) ?>
                                    <?= Html::openTag('span', ['class' => 'info-box-icon bg-primary']) ?>
                                    <?= Html::closeTag('span') ?>
                                    <?= Html::openTag('div', ['class' => 'info-box-content']) ?>
                                        <?= Html::tag('span', 'Total Metrics', ['class' => 'info-box-text']) ?>
                                        <?= Html::tag('span', (string) count($metrics), ['class' => 'info-box-number']) ?>
                                    <?= Html::closeTag('div') ?>
                                <?= Html::closeTag('div') ?>
                            <?= Html::closeTag('div') ?>
                        <?= Html::closeTag('div') ?>

                        <!-- 'Detailed Metrics' -->
                        <?= Html::openTag('div', ['class' => 'mt-4']) ?>
                            <?= Html::tag('h6', 'Detailed Metrics') ?>
                            <?= Html::openTag('div', ['class' => 'accordion', 'id' => 'metricsAccordion']) ?>
                                <?php
                                    /**
                                     * @var array $metric
                                     * @var string $metric['type']
                                     * @var string $metric['name']
                                     */
                                    foreach ($metrics as $index => $metric): ?>
                                    <?= Html::openTag('div', ['class' => 'card']) ?>
                                        <?= Html::openTag('div', ['class' => 'card-header', 'id' => 'heading' . (string) $index]) ?>
                                            <?= Html::openTag('h2', ['class' => 'mb-0']) ?>
                                                <?php
                                                $metricType = ($metric['type'] ?? 'unknown');
                                                $metricName = ($metric['name'] ?? 'unknown');
                                                $chartEmoji = $metricType === 'counter' ? '📊' :
                                                 ($metricType === 'gauge' ? '⚡' : '📈');
                                                $buttonContent = $chartEmoji .
                                                    ' ' . Html::encode($metricName) . ' (' . $metricType . ')';
                                                ?>
                                                <?= Html::button(
                                                    $buttonContent,
                                                    [
                                                        'class' => 'btn btn-link',
                                                        'type' => 'button',
                                                        'data-toggle' => 'collapse',
                                                        'data-target' => '#collapse' . (string) $index,
                                                        'aria-expanded' => 'false',
                                                        'aria-controls' => 'collapse' . (string) $index
                                                    ]
                                                ) ?>
                                            <?= Html::closeTag('h2') ?>
                                        <?= Html::closeTag('div') ?>
                                        <?= Html::openTag('div', [
                                            'id' => 'collapse' . (string) $index,
                                            'class' => 'collapse',
                                            'aria-labelledby' => 'heading' . (string) $index,
                                            'data-parent' => '#metricsAccordion'
                                        ]) ?>
                                            <?= Html::openTag('div', ['class' => 'card-body']) ?>
                                                <?= Html::tag('p', Html::encode((string) ($metric['help'] ?? 'No description')), ['class' => 'text-muted']) ?>
                                                <?php
                                                    /** @var mixed $metricValues */
                                                    $metricValues = $metric['values'];
                                                    if (!empty($metricValues) && is_array($metricValues)): ?>
                                                    <?= Html::tag('h6', 'Values:') ?>
                                                    <?php
                                                    $metricValuesArrayMap = array_map(fn($value) => (string) $value, $metricValues);
                                                    $preContent = Html::encode(implode("\n", array_slice($metricValuesArrayMap, 0, 10)));
                                                    if (count($metricValuesArrayMap) > 10) {
                                                        $preContent .= "\n... (" . (count($metricValues) - 10) . " more)";
                                                    }
                                                    ?>
                                                    <?= Html::tag('pre', $preContent, ['style' => 'max-height: 200px; overflow-y: auto;']) ?>
                                                <?php endif; ?>
                                            <?= Html::closeTag('div') ?>
                                        <?= Html::closeTag('div') ?>
                                    <?= Html::closeTag('div') ?>
                                <?php endforeach; ?>
                            <?= Html::closeTag('div') ?>
                        <?= Html::closeTag('div') ?>
                    <?php else: ?>
                        <?= Html::openTag('div', ['class' => 'alert alert-info']) ?>
                            ℹ️ No metrics available yet. The metrics will appear after some application activity.
                        <?= Html::closeTag('div') ?>
                    <?php endif; ?>
                <?= Html::closeTag('div') ?>
            <?= Html::closeTag('div') ?>
        <?= Html::closeTag('div') ?>
    <?= Html::closeTag('div') ?>

    <!-- 'Integration Information' -->
    <?= Html::openTag('div', ['class' => 'row mt-4']) ?>
        <?= Html::openTag('div', ['class' => 'col-12']) ?>
            <?= Html::openTag('div', ['class' => 'card']) ?>
                <?= Html::openTag('div', ['class' => 'card-header']) ?>
                    <?= Html::openTag('h5', ['class' => 'mb-0']) ?>
                        🔌 Integration Information
                    <?= Html::closeTag('h5') ?>
                <?= Html::closeTag('div') ?>
                <?= Html::openTag('div', ['class' => 'card-body']) ?>
                    <?= Html::openTag('div', ['class' => 'row']) ?>
                        <?= Html::openTag('div', ['class' => 'col-md-4']) ?>
                            <?= Html::tag('h6', '🖥️ Prometheus Configuration') ?>
                            <?= Html::tag('p', 'Add this job to your prometheus.yml:', ['class' => 'text-muted small']) ?>
                            <?= Html::tag('pre',
                                    'scrape_configs:
                                    - job_name: \'yii3-invoice-app\'
                                    static_configs:
                                    - targets: [\'localhost:8080\']
                                    metrics_path: \'/prometheus/metrics\'
                                    scrape_interval: 15s',
                                ['class' => 'small bg-light p-2']
                            ) ?>
                        <?= Html::closeTag('div') ?>
                        <?= Html::openTag('div', ['class' => 'col-md-4']) ?>
                            <?= Html::tag('h6', '📊 Grafana Integration') ?>
                            <?= Html::tag('p', 'Compatible with rfmoz/grafana-dashboards', ['class' => 'text-muted small']) ?>
                            <?php $items = [
                                    'HTTP request metrics',
                                    'Business KPIs',
                                    'System resource usage',
                                    'Application health status'
                                ];
                                Ul::tag()->class('small')->items(Li::tag()->content(...$items))->render() ?>
                        <?= Html::closeTag('div') ?>
                        <?= Html::openTag('div', ['class' => 'col-md-4']) ?>
                            <?= Html::tag('h6', '💻 Exporters') ?>
                            <?= Html::tag('p', 'Works alongside:', ['class' => 'text-muted small']) ?>
                            <?php
                                $items = [
                                    'node_exporter (port 9100)',
                                    'windows_exporter (port 9182)',
                                    'Custom application metrics'
                                ];
                                Ul::tag()->class('small')->items(Li::tag()->content(...$items))->render() ?>
                        <?= Html::closeTag('div') ?>
                    <?= Html::closeTag('div') ?>
                <?= Html::closeTag('div') ?>
            <?= Html::closeTag('div') ?>
        <?= Html::closeTag('div') ?>
    <?= Html::closeTag('div') ?>
<?= Html::closeTag('div') ?>
<?= Html::closeTag('div') ?>
<?= Html::closeTag('div') ?>
<?= Html::closeTag('div') ?>
<?= Html::closeTag('div') ?>

<?= Html::style('
.info-box {
    display: block;
    min-height: 90px;
    background: #fff;
    width: 100%;
    box-shadow: 0 1px 1px rgba(0,0,0,0.1);
    border-radius: 2px;
    margin-bottom: 15px;
}

.info-box-icon {
    border-top-left-radius: 2px;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-bottom-left-radius: 2px;
    display: block;
    float: left;
    height: 90px;
    width: 90px;
    text-align: center;
    font-size: 45px;
    line-height: 90px;
    background: rgba(0,0,0,0.2);
}

.info-box-content {
    padding: 5px 10px;
    margin-left: 90px;
}

.info-box-number {
    display: block;
    font-weight: bold;
    font-size: 18px;
}

.info-box-text {
    display: block;
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.bg-info { background-color: #17a2b8!important; color: white; }
.bg-success { background-color: #28a745!important; color: white; }
.bg-warning { background-color: #ffc107!important; color: white; }
.bg-primary { background-color: #007bff!important; color: white; }
'); ?>