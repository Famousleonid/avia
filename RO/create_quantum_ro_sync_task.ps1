param(
    [string]$TaskName = "Avia Quantum RO Sync",
    [string]$PhpPath = "C:\OSPanel\modules\php\PHP_8.1\php.exe",
    [string]$RoDir = "C:\OSPanel\domains\avia.loc\RO",
    [int]$IntervalMinutes = 5
)

$ErrorActionPreference = "Stop"

$syncScript = Join-Path $RoDir "sync.php"
$hiddenRunner = Join-Path $RoDir "run_quantum_ro_sync_hidden.vbs"

if (-not (Test-Path -LiteralPath $PhpPath)) {
    throw "PHP executable not found: $PhpPath"
}

if (-not (Test-Path -LiteralPath $syncScript)) {
    throw "sync.php not found: $syncScript"
}

if (-not (Test-Path -LiteralPath $hiddenRunner)) {
    throw "hidden runner not found: $hiddenRunner"
}

$action = New-ScheduledTaskAction `
    -Execute "wscript.exe" `
    -Argument "`"$hiddenRunner`""

$trigger = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date).AddMinutes(1) `
    -RepetitionInterval (New-TimeSpan -Minutes $IntervalMinutes) `
    -RepetitionDuration (New-TimeSpan -Days 3650)

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -MultipleInstances IgnoreNew `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 4)

$principal = New-ScheduledTaskPrincipal `
    -UserId "$env:USERDOMAIN\$env:USERNAME" `
    -LogonType Interactive `
    -RunLevel Limited

Register-ScheduledTask `
    -TaskName $TaskName `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Principal $principal `
    -Description "Runs Quantum RO staging sync and Laravel buffer parser every $IntervalMinutes minutes using hidden wscript runner." `
    -Force | Out-Null

Write-Host "Scheduled task created: $TaskName"
Write-Host "Runs every $IntervalMinutes minutes."
Write-Host "PHP: $PhpPath"
Write-Host "Script: $syncScript"
Write-Host "Hidden runner: $hiddenRunner"
Write-Host "Parser: C:\OSPanel\domains\avia.loc\artisan quantum-ro:apply"
Write-Host ""
Write-Host "Useful commands:"
Write-Host "  Get-ScheduledTask -TaskName '$TaskName'"
Write-Host "  Start-ScheduledTask -TaskName '$TaskName'"
Write-Host "  Unregister-ScheduledTask -TaskName '$TaskName' -Confirm:`$false"
