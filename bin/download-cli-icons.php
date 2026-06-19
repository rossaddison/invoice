<?php
declare(strict_types=1);

// Downloads SVG icons for the m.php main-menu cards into public/img/cli/.
// Run via:  php bin/download-cli-icons.php
// Or via the System submenu → "Download Menu Icons" in m.php.

$dest = dirname(__DIR__) . '/public/img/cli/';
if (!is_dir($dest)) {
    mkdir($dest, 0755, true);
    echo "Created: $dest\n";
}

// jsDelivr CDN — reliable, no rate limiting for open-source packages
$SI = 'https://cdn.jsdelivr.net/npm/simple-icons@latest/icons/%s.svg';   // brand SVGs
$BI = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@latest/icons/%s.svg'; // generic SVGs

$icons = [
    // Brand icons (Simple Icons npm package)
    'composer'    => sprintf($SI, 'composer'),
    'nodejs'      => sprintf($SI, 'nodedotjs'),
    'typescript'  => sprintf($SI, 'typescript'),
    'angular'     => sprintf($SI, 'angular'),
    'snyk'        => sprintf($SI, 'snyk'),
    'sonarcloud'  => sprintf($SI, 'sonarcloud'),
    'github'      => sprintf($SI, 'github'),
    // Generic icons (Bootstrap Icons npm package)
    'psalm'       => sprintf($BI, 'search'),
    'testing'     => sprintf($BI, 'check2-circle'),
    'phpcs-fixer' => sprintf($BI, 'brush'),
    'phpcs'       => sprintf($BI, 'code-slash'),
    'rector'      => sprintf($BI, 'stars'),
    'yii'         => 'https://www.yiiframework.com/image/design/logo/yii3_full_for_light.svg',
    'peppol'      => sprintf($BI, 'globe'),
    'benchmarks'  => sprintf($BI, 'speedometer2'),
    'system'      => sprintf($BI, 'gear'),
];

function downloadSvg(string $url, string $path): bool
{
    // cURL preferred; falls back to file_get_contents
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_USERAGENT      => 'Yii3-i-DevTools/1.0',
        ]);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (is_string($data) && $data !== '' && $code === 200) {
            file_put_contents($path, $data);
            return true;
        }
    }

    $ctx  = stream_context_create(['http' => ['timeout' => 20, 'user_agent' => 'Yii3-i-DevTools/1.0']]);
    $data = @file_get_contents($url, false, $ctx);
    if (is_string($data) && $data !== '') {
        file_put_contents($path, $data);
        return true;
    }
    return false;
}

$ok  = 0;
$err = 0;
foreach ($icons as $name => $url) {
    $file = $dest . $name . '.svg';
    echo "Downloading $name … ";
    if (downloadSvg($url, $file)) {
        echo "✓\n";
        $ok++;
    } else {
        echo "✗ FAILED ($url)\n";
        $err++;
    }
}

echo "\nDone: $ok downloaded, $err failed.\n";
echo "Icons saved to: $dest\n";
