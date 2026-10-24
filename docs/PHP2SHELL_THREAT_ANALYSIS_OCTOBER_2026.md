# "PHP2Shell" / PHP-CGI Argument Injection — Why This Deployment Isn't Exposed (October 2026)

Prompted by the PHP 8.5 upgrade (`docs/PHP85_ALPINE_PRODUCTION_UPGRADE_OCTOBER_2026.md`)
and recent news coverage of mass-scanned PHP-CGI argument-injection attacks
("PHP2Shell", most notably [CVE-2024-4577](https://nvd.nist.gov/vuln/detail/CVE-2024-4577)
and its older ancestor, [CVE-2012-1823](https://nvd.nist.gov/vuln/detail/CVE-2012-1823)).
This is a deliberately cautious, careful upgrade (rebuilding the PHP 8.5
extension set piece by piece rather than a quick revert-and-retry) specifically
because this exact threat class was on our minds mid-upgrade — worth writing
up precisely what it is and why it doesn't apply here, rather than leaving
that as an assumption.

## What the attack actually is

PHP-CGI argument injection targets the **CGI SAPI** specifically: a
standalone `php-cgi` binary invoked per-request by a web server (via
`mod_cgi`, `mod_fcgid`, or a misconfigured FastCGI proxy). The `php-cgi`
binary accepts command-line flags, and under specific misconfigurations
(the original CVE-2012-1823 on any OS when PHP is run as a CGI script
without `cgi.force_redirect`; CVE-2024-4577's 2024 rediscovery, specific to
Windows locale-aware `MultiByteToWideChar`/Best-Fit encoding stripping a
leading `-` from the query string before PHP sees it) a crafted query
string gets reinterpreted as `php-cgi` command-line arguments instead of
plain request data. The best-known payload uses `-d allow_url_include=1
-d auto_prepend_file=php://input` to execute arbitrary PHP from the
request body — full remote code execution, no authentication required.

## Why it doesn't apply to this server

This app is served by **Apache with `mod_php`** — PHP runs embedded
directly inside the Apache worker process
(`LoadModule php_module modules/mod_php85.so`,
`/etc/apache2/conf.d/php85-module.conf`). There is no separate `php-cgi`
process, no query string ever gets parsed as command-line arguments, and
`mod_cgi`/`mod_fcgid` aren't in play for this vhost at all. The entire
attack surface this vulnerability class depends on — a CGI binary
interpreting its own invocation arguments from attacker-controlled input —
doesn't exist in this configuration, independent of which PHP version is
installed. Confirmed directly: `httpd -V` shows `mod_php` compiled in as
an Apache module, not a CGI/FastCGI handler, and there is no `cgi-bin`
directory mapped to anything executable under the document root.

## Evidence from the server's own logs

`/var/log/apache2/error.log` shows exactly the kind of automated scanning
this vulnerability class attracts — dozens of requests over the past week
hitting paths like:

```
/cgi-bin/.%2e/.%2e/.%2e/.%2e/.%2e/.%2e/.%2e/.%2e/.%2e/.%2e/bin/sh
/cgi-bin/%%32%65%%32%65/%%32%65%%32%65/.../bin/sh
```

— the classic path-traversal-to-shell probe pattern (encoded `../` segments
walking out of an assumed `cgi-bin` directory to directly invoke
`/bin/sh`). Every single one was rejected by Apache's own URL parser with
`AH10244: invalid URI path`, before ever reaching PHP in any form. This
confirms the relevant surface (a `cgi-bin` alias mapped to something
executable) simply isn't present — these bots are scanning for a
vulnerability class this server was never configured to have, regardless
of the PHP 8.4 → 8.5 transition.

## What the upgrade *was* a real risk for

Not CGI argument injection — but a careless, rushed version swap mid-flight
could have left Apache in a state where `.php` files were served as raw
text (a `LoadModule` pointing at a `.so` that failed to load, with no
fallback handler registered for the `.php` extension) rather than executed.
That's a **source-disclosure** risk, not RCE: `.env`, database credentials,
and API keys embedded in config would have been downloadable as plain text
to anyone who requested them while in that state. This is the concrete
reason the upgrade was done the deliberate way documented in
`docs/PHP85_ALPINE_PRODUCTION_UPGRADE_OCTOBER_2026.md` — verifying via a
direct CLI invocation of the entry script at every step, rather than
toggling Apache config and hoping, and confirming a real `200` from the
live site only once every extension gap was closed.

## Takeaways

- `mod_php` deployments are structurally immune to PHP-CGI argument
  injection; this is one of the few cases where the older,
  "less modern" Apache integration mode has a genuine security edge over
  a FastCGI/CGI-based one for this specific attack class.
- Keeping PHP on a currently-supported branch (8.5, as of this upgrade)
  still matters for *other* reasons — PHP 8.3/8.4 will eventually stop
  receiving security patches for whatever the *next* PHP-core or
  extension CVE turns out to be. That's general hygiene, not a fix for
  this specific threat.
- The `/cgi-bin/.../bin/sh` scanning traffic is worth an occasional glance
  at going forward, purely as a signal of what's being mass-probed against
  this IP — not as an active concern given the current configuration.
