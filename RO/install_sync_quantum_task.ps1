param(
    [string] $TaskName = "sync_quantum",
    [string] $RoDir = "C:\avia\RO",
    [int] $IntervalMinutes = 5,
    [string] $PhpPath = "",
    [string] $OracleClientDir = "C:\oracle",
    [string] $TnsAdmin = "",
    [string] $OracleUser = "",
    [string] $OraclePass = "",
    [string] $OracleDsn = "MAXQPROD",
    [string] $ApiUrl = "",
    [string] $ApiToken = "",
    [string] $Timezone = "America/Toronto",
    [switch] $RunNow,
    [switch] $SkipPhpModuleCheck
)

$ErrorActionPreference = "Stop"

function Read-EnvFile {
    param([string] $Path)

    $map = @{}
    if (-not (Test-Path -LiteralPath $Path)) {
        return $map
    }

    foreach ($rawLine in Get-Content -LiteralPath $Path) {
        $line = $rawLine.Trim()
        if ($line.Length -eq 0) { continue }
        if ($line.StartsWith("#")) { continue }

        $idx = $line.IndexOf("=")
        if ($idx -le 0) { continue }

        $key = $line.Substring(0, $idx).Trim()
        $value = $line.Substring($idx + 1).Trim()

        if ($value.Length -ge 2) {
            $first = $value.Substring(0, 1)
            $last = $value.Substring($value.Length - 1, 1)
            if (($first -eq '"' -and $last -eq '"') -or ($first -eq "'" -and $last -eq "'")) {
                $value = $value.Substring(1, $value.Length - 2)
            }
        }

        $map[$key] = $value
    }

    return $map
}

function Write-EnvFile {
    param([string] $Path, [hashtable] $Map)

    $lines = @(
        "# sync_quantum local bridge environment",
        "# Keep this file on the bridge PC only.",
        "ORACLE_CLIENT_DIR=$($Map["ORACLE_CLIENT_DIR"])",
        "TNS_ADMIN=$($Map["TNS_ADMIN"])",
        "ORACLE_USER=$($Map["ORACLE_USER"])",
        "ORACLE_PASS=$($Map["ORACLE_PASS"])",
        "ORACLE_DSN=$($Map["ORACLE_DSN"])",
        "PHP_PATH=$($Map["PHP_PATH"])",
        "AVIA_SYNC_API_URL=$($Map["AVIA_SYNC_API_URL"])",
        "AVIA_SYNC_API_TOKEN=$($Map["AVIA_SYNC_API_TOKEN"])",
        "AVIA_SYNC_TIMEZONE=$($Map["AVIA_SYNC_TIMEZONE"])"
    )

    Set-Content -LiteralPath $Path -Value $lines -Encoding UTF8
}

function Missing-OrPlaceholder {
    param([string] $Value)

    return [string]::IsNullOrWhiteSpace($Value) `
        -or $Value -like "CHANGE_ME*" `
        -or $Value -like "https://your-host.example*"
}

function Resolve-OracleClientDir {
    param([string] $BaseDir)

    if (-not (Test-Path -LiteralPath $BaseDir)) {
        throw "Oracle directory not found: $BaseDir"
    }

    $directOci = Join-Path $BaseDir "oci.dll"
    if (Test-Path -LiteralPath $directOci) {
        return (Resolve-Path -LiteralPath $BaseDir).ProviderPath
    }

    $oci = Get-ChildItem -LiteralPath $BaseDir -Filter oci.dll -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($oci) {
        return $oci.DirectoryName
    }

    throw "oci.dll not found under Oracle directory: $BaseDir"
}

$RoDir = (Resolve-Path -LiteralPath $RoDir -ErrorAction Stop).ProviderPath

$syncScript = Join-Path $RoDir "sync.php"
$queryScript = Join-Path $RoDir "quantum_ro_query.php"
$runnerScript = Join-Path $RoDir "run_sync_quantum.ps1"
$hiddenRunner = Join-Path $RoDir "run_sync_quantum_hidden.vbs"
$envPath = Join-Path $RoDir ".env.sync_quantum"
$envExample = Join-Path $RoDir ".env.sync_quantum.example"

foreach ($path in @($syncScript, $queryScript, $runnerScript, $hiddenRunner)) {
    if (-not (Test-Path -LiteralPath $path)) {
        throw "Required file not found: $path"
    }
}

if (-not (Test-Path -LiteralPath $envPath)) {
    if (Test-Path -LiteralPath $envExample) {
        Copy-Item -LiteralPath $envExample -Destination $envPath
    } else {
        New-Item -ItemType File -Path $envPath -Force | Out-Null
    }
}

$envMap = Read-EnvFile -Path $envPath

if (-not $envMap.ContainsKey("ORACLE_CLIENT_DIR") -or [string]::IsNullOrWhiteSpace($envMap["ORACLE_CLIENT_DIR"])) {
    $envMap["ORACLE_CLIENT_DIR"] = $OracleClientDir
}
if (-not $envMap.ContainsKey("TNS_ADMIN")) {
    $envMap["TNS_ADMIN"] = $TnsAdmin
}
if (-not $envMap.ContainsKey("ORACLE_DSN") -or [string]::IsNullOrWhiteSpace($envMap["ORACLE_DSN"])) {
    $envMap["ORACLE_DSN"] = $OracleDsn
}
if (-not $envMap.ContainsKey("PHP_PATH") -or [string]::IsNullOrWhiteSpace($envMap["PHP_PATH"])) {
    $envMap["PHP_PATH"] = if ([string]::IsNullOrWhiteSpace($PhpPath)) { Join-Path $RoDir "PHP_8.1\php.exe" } else { $PhpPath }
}
if (-not $envMap.ContainsKey("AVIA_SYNC_TIMEZONE") -or [string]::IsNullOrWhiteSpace($envMap["AVIA_SYNC_TIMEZONE"])) {
    $envMap["AVIA_SYNC_TIMEZONE"] = $Timezone
}

if (-not [string]::IsNullOrWhiteSpace($PhpPath)) { $envMap["PHP_PATH"] = $PhpPath }
if (-not [string]::IsNullOrWhiteSpace($OracleClientDir)) { $envMap["ORACLE_CLIENT_DIR"] = $OracleClientDir }
if (-not [string]::IsNullOrWhiteSpace($TnsAdmin)) { $envMap["TNS_ADMIN"] = $TnsAdmin }
if (-not [string]::IsNullOrWhiteSpace($OracleUser)) { $envMap["ORACLE_USER"] = $OracleUser }
if (-not [string]::IsNullOrWhiteSpace($OraclePass)) { $envMap["ORACLE_PASS"] = $OraclePass }
if (-not [string]::IsNullOrWhiteSpace($OracleDsn)) { $envMap["ORACLE_DSN"] = $OracleDsn }
if (-not [string]::IsNullOrWhiteSpace($ApiUrl)) { $envMap["AVIA_SYNC_API_URL"] = $ApiUrl }
if (-not [string]::IsNullOrWhiteSpace($ApiToken)) { $envMap["AVIA_SYNC_API_TOKEN"] = $ApiToken }
if (-not [string]::IsNullOrWhiteSpace($Timezone)) { $envMap["AVIA_SYNC_TIMEZONE"] = $Timezone }

$resolvedOracleDir = Resolve-OracleClientDir -BaseDir $envMap["ORACLE_CLIENT_DIR"]
$envMap["ORACLE_CLIENT_DIR"] = $resolvedOracleDir
if (Missing-OrPlaceholder $envMap["TNS_ADMIN"]) {
    $tnsPath = Join-Path $resolvedOracleDir "tnsnames.ora"
    if (Test-Path -LiteralPath $tnsPath) {
        $envMap["TNS_ADMIN"] = $resolvedOracleDir
    }
}

Write-EnvFile -Path $envPath -Map $envMap

$requiredKeys = @("ORACLE_USER", "ORACLE_PASS", "ORACLE_DSN", "AVIA_SYNC_API_URL", "AVIA_SYNC_API_TOKEN", "PHP_PATH")
$missing = @()
foreach ($key in $requiredKeys) {
    if (Missing-OrPlaceholder $envMap[$key]) {
        $missing += $key
    }
}

if ($missing.Count -gt 0) {
    throw "Fill these values in $envPath and rerun installer: $($missing -join ', ')"
}

$phpPathFromEnv = $envMap["PHP_PATH"]
if (Test-Path -LiteralPath $phpPathFromEnv) {
    $phpExe = (Resolve-Path -LiteralPath $phpPathFromEnv -ErrorAction Stop).ProviderPath
} else {
    try {
        $phpExe = (Get-Command $phpPathFromEnv -ErrorAction Stop).Source
    } catch {
        throw "PHP not found. Current PHP_PATH is '$phpPathFromEnv'. Install/copy PHP CLI there or edit PHP_PATH in $envPath, then rerun installer."
    }
}

$env:PATH = (Split-Path -Parent $phpExe) + ";" + $resolvedOracleDir + ";" + $env:PATH
if (-not [string]::IsNullOrWhiteSpace($envMap["TNS_ADMIN"])) {
    $env:TNS_ADMIN = $envMap["TNS_ADMIN"]
}

if (-not $SkipPhpModuleCheck) {
    $modules = & $phpExe -m
    if ($LASTEXITCODE -ne 0) {
        throw "PHP module check failed: $phpExe -m"
    }

    $moduleSet = @{}
    foreach ($module in $modules) {
        $moduleSet[$module.ToLowerInvariant()] = $true
    }

    foreach ($requiredModule in @("oci8", "openssl")) {
        if (-not $moduleSet.ContainsKey($requiredModule)) {
            throw "PHP extension is not enabled for ${phpExe}: $requiredModule"
        }
    }

    $allowUrlFopen = & $phpExe -r "echo ini_get('allow_url_fopen');"
    if ($allowUrlFopen -ne "1") {
        throw "PHP allow_url_fopen must be enabled for API HTTP requests."
    }
}

$action = New-ScheduledTaskAction `
    -Execute "wscript.exe" `
    -Argument ('"' + $hiddenRunner + '"') `
    -WorkingDirectory $RoDir

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
    -Description "Runs Quantum RO sync bridge every $IntervalMinutes minutes." `
    -Force | Out-Null

if ($RunNow) {
    Start-ScheduledTask -TaskName $TaskName
}

Write-Host "Scheduled task created: $TaskName"
Write-Host "Runs every $IntervalMinutes minutes."
Write-Host "RO dir: $RoDir"
Write-Host "PHP: $phpExe"
Write-Host "Oracle client: $resolvedOracleDir"
Write-Host "Env file: $envPath"
Write-Host "Runner: $hiddenRunner"
Write-Host ""
Write-Host "Useful commands:"
Write-Host "  Get-ScheduledTask -TaskName '$TaskName'"
Write-Host "  Start-ScheduledTask -TaskName '$TaskName'"
Write-Host "  Get-Content '$RoDir\quantum_ro_sync.log' -Tail 20"
Write-Host "  Get-Content '$RoDir\sync_quantum_runner.log' -Tail 20"
Write-Host "  Unregister-ScheduledTask -TaskName '$TaskName' -Confirm:`$false"
