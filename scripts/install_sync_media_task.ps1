param(
    [string] $TaskName = "Sync Media",
    [string] $PhpPath = "",
    [string] $ScriptPath = ""
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($ScriptPath)) {
    $rootScript = Join-Path $PSScriptRoot "sync_media.php"
    $parentScript = Join-Path (Split-Path -Parent $PSScriptRoot) "sync_media.php"

    if (Test-Path -LiteralPath $rootScript) {
        $ScriptPath = $rootScript
    } else {
        $ScriptPath = $parentScript
    }
}

$resolvedScript = Resolve-Path -LiteralPath $ScriptPath -ErrorAction Stop
$scriptFile = $resolvedScript.ProviderPath

if ([string]::IsNullOrWhiteSpace($PhpPath)) {
    $scriptDir = Split-Path -Parent $scriptFile
    $localPhp = Join-Path $scriptDir "PHP_8.1\php.exe"
    $scriptFolderPhp = Join-Path $PSScriptRoot "PHP_8.1\php.exe"

    if (Test-Path -LiteralPath $localPhp) {
        $PhpPath = $localPhp
    } elseif (Test-Path -LiteralPath $scriptFolderPhp) {
        $PhpPath = $scriptFolderPhp
    } else {
        $PhpPath = "php.exe"
    }
}

$phpCommand = Get-Command $PhpPath -ErrorAction Stop
$phpExe = $phpCommand.Source

if (-not (Test-Path -LiteralPath $phpExe)) {
    throw "PHP executable not found: $phpExe"
}

$curlCheck = & $phpExe -m
if ($LASTEXITCODE -ne 0) {
    throw "PHP module check failed: $phpExe -m"
}

if ($curlCheck -notcontains "curl") {
    throw "PHP cURL extension is not enabled for: $phpExe"
}

$action = New-ScheduledTaskAction `
    -Execute $phpExe `
    -Argument ('-f "' + $scriptFile + '"') `
    -WorkingDirectory (Split-Path -Parent $scriptFile)

$trigger = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date).AddMinutes(1) `
    -RepetitionInterval (New-TimeSpan -Minutes 5) `
    -RepetitionDuration (New-TimeSpan -Days 3650)

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -MultipleInstances IgnoreNew

Register-ScheduledTask `
    -TaskName $TaskName `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Description "Runs sync_media.php every 5 minutes." `
    -Force | Out-Null

Write-Host "Scheduled task '$TaskName' created."
Write-Host "PHP:    $phpExe"
Write-Host "Script: $scriptFile"
