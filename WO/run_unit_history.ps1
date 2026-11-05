param(
    [string] $EnvFile = "C:\avia\RO\.env.sync_quantum",
    [switch] $CheckOnly
)

$ErrorActionPreference = 'Stop'
$logPath = Join-Path $PSScriptRoot 'unit_history_sync.log'
try {
    $settings = @{}
    foreach ($rawLine in Get-Content -LiteralPath $EnvFile -ErrorAction Stop) {
        $line = $rawLine.Trim()
        if (!$line -or $line.StartsWith('#')) { continue }
        $separator = $line.IndexOf('=')
        if ($separator -le 0) { continue }
        $key = $line.Substring(0, $separator).Trim()
        $value = $line.Substring($separator + 1).Trim()
        if ($value.Length -ge 2 -and (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'")))) {
            $value = $value.Substring(1, $value.Length - 2)
        }
        $settings[$key] = $value
    }
    foreach ($key in @('ORACLE_USER','ORACLE_PASS','ORACLE_DSN','TNS_ADMIN','ORACLE_CLIENT_DIR','ORACLE_HOME','AVIA_SYNC_API_TOKEN','AVIA_SYNC_API_URL','AVIA_UNIT_HISTORY_API_URL','AVIA_SYNC_TIMEZONE')) {
        if ($settings.ContainsKey($key)) { [Environment]::SetEnvironmentVariable($key, $settings[$key], 'Process') }
    }
    foreach ($key in @('ORACLE_USER','ORACLE_PASS','ORACLE_DSN','AVIA_SYNC_API_TOKEN')) {
        if ([string]::IsNullOrWhiteSpace([Environment]::GetEnvironmentVariable($key, 'Process'))) { throw "Missing setting: $key" }
    }
    if (!$env:AVIA_UNIT_HISTORY_API_URL) {
        if ($env:AVIA_SYNC_API_URL -notmatch '/ro-sync/?$') { throw 'AVIA_SYNC_API_URL must end with /ro-sync, or set AVIA_UNIT_HISTORY_API_URL.' }
        $env:AVIA_UNIT_HISTORY_API_URL = $env:AVIA_SYNC_API_URL -replace '/ro-sync/?$', '/unit-history'
    }
    if ($env:AVIA_UNIT_HISTORY_API_URL -notmatch '^https://') { throw 'History API requires HTTPS.' }
    $phpExe = $settings['PHP_PATH']
    if (!$phpExe) { $phpExe = Join-Path (Split-Path -Parent $EnvFile) 'PHP_8.1\php.exe' }
    if (!(Test-Path -LiteralPath $phpExe)) { throw 'PHP_PATH does not point to php.exe.' }
    $clientDir = $env:ORACLE_CLIENT_DIR
    if (!$clientDir) { $clientDir = $env:ORACLE_HOME }
    if (!$clientDir) { $clientDir = 'C:\oracle' }
    $env:PATH = "$clientDir;$(Split-Path -Parent $phpExe);$env:PATH"
    if (!$env:TNS_ADMIN -and (Test-Path -LiteralPath (Join-Path $clientDir 'tnsnames.ora'))) { $env:TNS_ADMIN = $clientDir }
    $modules = & $phpExe -m
    if ($LASTEXITCODE -ne 0) { throw 'PHP module check failed.' }
    foreach ($module in @('oci8','curl')) {
        if ($modules -notcontains $module) { throw "Enable $module in this PHP installation's php.ini." }
    }
    if ($CheckOnly) { Write-Output 'Settings and PHP modules OK. No export or upload performed.'; exit 0 }
    Add-Content -LiteralPath $logPath -Value "[$(Get-Date -Format s)] Starting unit history sync"
    $output = & $phpExe (Join-Path $PSScriptRoot 'unit_history_export.php') --send 2>&1
    $code = $LASTEXITCODE
    $output | Add-Content -LiteralPath $logPath
    $output | Write-Output
    if ($code -ne 0) { throw "History export failed (exit $code)." }
    Add-Content -LiteralPath $logPath -Value "[$(Get-Date -Format s)] SUCCESS"
    exit 0
} catch {
    $message = "[$(Get-Date -Format s)] ERROR: $($_.Exception.Message)"
    Add-Content -LiteralPath $logPath -Value $message
    Write-Output $message
    exit 1
}
