# Translation Value Wrapper Script

## Purpose

This script processes PHP translation array files to ensure translation values comply with line-length rules (such as SonarQube's "line too long" rule) by intelligently splitting long values into concatenated fragments.

## What It Does

The script **only modifies values** in simple single-line PHP array entries of the form:

```php
'key' => 'very long value text that exceeds maximum line length.. .',