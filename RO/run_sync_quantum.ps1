param(
    [string] $RoDir = $PSScriptRoot,
    [string] $EnvFile = ""
)

$ErrorActionPreference = "Stop"

function Write-RunnerLog {
    param([string] $Message)

    $logPath = Join-Path $RoDir "sync_quantum_runner.log"
    $line = "[{0}] {1}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), $Message
    Add-Content -LiteralPath $logPath -Value $line -Encoding UTF8
}

function Set-ProcessEnvFromFile {
    param([string] $Path)

    if (-not (Test-Path -LiteralPath $Path)) {
        throw "Environment file not found: $Path"
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

        [Environment]::SetEnvironmentVariable($key, $value, "Process")
    }
}

function Get-EnvValue {
    param([string] $Name, [string] $Default = "")

    $value = [Environment]::GetEnvironmentVariable($Name, "Process")
    if ([string]::IsNullOrWhiteSpace($value)) {
        return $Default
    }

    return $value
}

try {
    $RoDir = (Resolve-Path -LiteralPath $RoDir -ErrorAction Stop).ProviderPath

    if ([string]::IsNullOrWhiteSpace($EnvFile)) {
        $EnvFile = Join-Path $RoDir ".env.sync_quantum"
    }

    Set-ProcessEnvFromFile -Path $EnvFile

    if ([string]::IsNullOrWhiteSpace((Get-EnvValue "AVIA_SYNC_TIMEZONE"))) {
        [Environment]::SetEnvironmentVariable("AVIA_SYNC_TIMEZONE", "America/Toronto", "Process")
    }

    $oracleClientDir = Get-EnvValue "ORACLE_CLIENT_DIR" (Get-EnvValue "ORACLE_HOME" "C:\oracle")
    if (-not [string]::IsNullOrWhiteSpace($oracleClientDir)) {
        $oracleClientDir = (Resolve-Path -LiteralPath $oracleClientDir -ErrorAction Stop).ProviderPath
        [Environment]::SetEnvironmentVariable("ORACLE_CLIENT_DIR", $oracleClientDir, "Process")

        $currentPath = [Environment]::GetEnvironmentVariable("PATH", "Process")
        [Environment]::SetEnvironmentVariable("PATH", $oracleClientDir + ";" + $currentPath, "Process")

        if ([string]::IsNullOrWhiteSpace((Get-EnvValue "TNS_ADMIN"))) {
            $tnsPath = Join-Path $oracleClientDir "tnsnames.ora"
            if (Test-Path -LiteralPath $tnsPath) {
                [Environment]::SetEnvironmentVariable("TNS_ADMIN", $oracleClientDir, "Process")
            }
        }
    }

    $phpPath = Get-EnvValue "PHP_PATH" (Join-Path $RoDir "PHP_8.1\php.exe")
    if (Test-Path -LiteralPath $phpPath) {
        $phpExe = (Resolve-Path -LiteralPath $phpPath -ErrorAction Stop).ProviderPath
    } else {
        $phpExe = (Get-Command $phpPath -ErrorAction Stop).Source
    }

    $phpDir = Split-Path -Parent $phpExe
    $pathWithPhp = $phpDir + ";" + [Environment]::GetEnvironmentVariable("PATH", "Process")
    [Environment]::SetEnvironmentVariable("PATH", $pathWithPhp, "Process")

    $syncScript = Join-Path $RoDir "sync.php"
    if (-not (Test-Path -LiteralPath $syncScript)) {
        throw "sync.php not found: $syncScript"
    }

    $required = @("ORACLE_USER", "ORACLE_PASS", "ORACLE_DSN", "AVIA_SYNC_API_URL", "AVIA_SYNC_API_TOKEN")
    foreach ($name in $required) {
        $value = Get-EnvValue $name
        if ([string]::IsNullOrWhiteSpace($value) -or $value -like "CHANGE_ME*" -or $value -like "https://your-host.example*") {
            throw "Required value is missing in ${EnvFile}: $name"
        }
    }

    Write-RunnerLog ("start php={0} oracle={1} dsn={2} api={3}" -f $phpExe, $oracleClientDir, (Get-EnvValue "ORACLE_DSN"), (Get-EnvValue "AVIA_SYNC_API_URL"))

    & $phpExe $syncScript
    $exitCode = $LASTEXITCODE

    Write-RunnerLog ("finish exit_code={0}" -f $exitCode)
    exit $exitCode
} catch {
    Write-RunnerLog ("error " + $_.Exception.Message)
    exit 1
}
