<?php

declare(strict_types=1);

use Yiisoft\ErrorHandler\Exception\UserException;
use Yiisoft\ErrorHandler\Renderer\HtmlRenderer;
use Yiisoft\ErrorHandler\ThrowableRendererInterface;

/**
 * Standalone, zero-dependency production error page.
 *
 * This replaces the stock yiisoft/error-handler `production.php` template.
 * It intentionally does NOT go through the app's own layout/view system
 * (resources/views/layout/*): that layout resolves fonts, colors and menu
 * content from SettingRepository, i.e. it needs a working database — which
 * is exactly the condition that cannot be relied on when this template is
 * the one rendering (e.g. "SQLSTATE[HY000] [2002] Connection refused").
 * Every style below is inlined and no request goes out to a CDN, so the
 * page always renders even when the DB and every other service are down.
 *
 * Security: mirrors the stock template's disclosure rule exactly. Only a
 * throwable that explicitly opts in via UserException is safe to surface
 * to the visitor; everything else renders as a generic message. Never add
 * exception class names, messages, file paths or stack traces here for the
 * non-UserException branch — that belongs solely to the verbose/dev
 * template, gated by YII_DEBUG.
 *
 * @var Throwable $throwable
 * @var \Psr\Http\Message\ServerRequestInterface|null $request
 * @var HtmlRenderer $this
 */

if (UserException::isUserException($throwable)) {
    $name = $this->getThrowableName($throwable);
    $message = $throwable->getMessage();
} else {
    $name = 'Something went wrong';
    $message = ThrowableRendererInterface::DEFAULT_ERROR_MESSAGE;
}

// No JavaScript: "Try again" is a plain link back to the very URL that
// failed, so retrying is just a normal navigation, not a script-driven
// reload (keeps this page CSP-safe with no unsafe-inline/unsafe-eval).
$retryUrl = $request !== null ? (string) $request->getUri() : '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->htmlEncode($name) ?> &mdash; Yii3-i</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #e9ecef;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        }

        .card {
            width: 100%;
            max-width: 32rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            color: #74c0fc;
            margin-bottom: 1.75rem;
        }

        .brand-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 6px;
            background: #0f3460;
            border: 1px solid rgba(116, 192, 252, 0.4);
            font-size: 0.85rem;
        }

        .icon {
            font-size: 2.5rem;
            line-height: 1;
            margin-bottom: 1rem;
        }

        h1 {
            margin: 0 0 0.75rem;
            font-size: 1.4rem;
            font-weight: 600;
            color: #ff6b6b;
        }

        p {
            margin: 0 0 0.5rem;
            font-size: 0.95rem;
            line-height: 1.6;
            color: #ced4da;
        }

        .support {
            font-size: 0.85rem;
            color: #adb5bd;
            margin-top: 1.25rem;
        }

        .actions {
            margin-top: 1.75rem;
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 0.55rem 1.25rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: #0f3460;
            color: #e9ecef;
            border-color: rgba(116, 192, 252, 0.4);
        }

        .btn-primary:hover {
            background: #133a70;
        }

        .btn-secondary {
            background: transparent;
            color: #ced4da;
            border-color: rgba(255, 255, 255, 0.15);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.06);
        }

        .timestamp {
            margin-top: 1.75rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.75rem;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="brand">
            <span class="brand-mark">i</span>
            <span>Yii3-i</span>
        </div>

        <div class="icon" aria-hidden="true">&#9888;&#65039;</div>

        <h1><?= $this->htmlEncode($name) ?></h1>
        <p><?= nl2br($this->htmlEncode($message)) ?></p>

        <p class="support">
            This has been logged and our team will look into it.
            If the problem continues, please contact support.
        </p>

        <div class="actions">
            <a class="btn btn-primary" href="<?= $this->htmlEncode($retryUrl) ?>">Try again</a>
            <a class="btn btn-secondary" href="/">Go to homepage</a>
        </div>

        <div class="timestamp"><?= date('Y-m-d H:i:s') ?></div>
    </div>
</body>
</html>
