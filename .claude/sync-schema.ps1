# Triggered by PostToolUse hook on Edit|Write.
# If the edited file is under src/Infrastructure/Persistence/, cycles BUILD_DATABASE in .env
# to force Cycle ORM to sync the schema, then reverts.
$raw = [Console]::In.ReadToEnd()
if ([string]::IsNullOrWhiteSpace($raw)) { exit 0 }

try {
    $data = ConvertFrom-Json $raw
    $filePath = $data.tool_input.file_path
    if ($null -eq $filePath -or $filePath -notmatch 'Infrastructure[/\\]Persistence[/\\]') { exit 0 }

    $envFile = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\.env'))
    $enc = New-Object System.Text.UTF8Encoding $false

    $content = [System.IO.File]::ReadAllText($envFile, $enc)
    $content = $content -replace '(?m)^BUILD_DATABASE=.*', 'BUILD_DATABASE=true'
    [System.IO.File]::WriteAllText($envFile, $content, $enc)

    try {
        Invoke-WebRequest -Uri 'http://localhost/invoice/' -UseBasicParsing -TimeoutSec 30 | Out-Null
    } catch {}

    $content = [System.IO.File]::ReadAllText($envFile, $enc)
    $content = $content -replace '(?m)^BUILD_DATABASE=.*', 'BUILD_DATABASE='
    [System.IO.File]::WriteAllText($envFile, $content, $enc)
} catch { exit 0 }
