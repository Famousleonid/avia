[CmdletBinding(SupportsShouldProcess = $true)]
param(
    [string] $EnvFile = 'C:\avia\RO\.env.sync_quantum'
)

$ErrorActionPreference = 'Stop'
# Intentionally fixed: this installer must never replace the RO bridge task.
$taskName = 'sync_quantum_unit_history'
$taskPath = '\'
$description = 'AVIA Quantum Unit History: daily complete snapshot at 06:30.'

try {
    $runner = Join-Path $PSScriptRoot 'run_unit_history.ps1'
    $exporter = Join-Path $PSScriptRoot 'unit_history_export.php'
    foreach ($path in @($runner, $exporter, $EnvFile)) {
        if (!(Test-Path -LiteralPath $path -PathType Leaf)) { throw "Required file not found: $path" }
    }
    $EnvFile = (Resolve-Path -LiteralPath $EnvFile).ProviderPath
    if ($EnvFile.Contains('"') -or $runner.Contains('"')) { throw 'Paths must not contain quote characters.' }
    $powershell = Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'

    if (!$PSCmdlet.ShouldProcess($taskName, 'Create/update separate daily 06:30 task and run first upload as SYSTEM')) {
        Write-Output 'No task or data changed. Existing sync_quantum is never modified.'
        return
    }
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    if (!$principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        throw 'Open PowerShell as Administrator and run this command again.'
    }
    $existing = Get-ScheduledTask -TaskPath $taskPath -ErrorAction Stop | Where-Object { $_.TaskName -eq $taskName }
    if ($existing) {
        if ($existing.Description -ne $description) { throw "Task $taskName already exists with another description; nothing changed." }
        if ($existing.State -eq 'Running') { throw 'Unit history task is already running. Wait for it to finish; nothing changed.' }
    }
    $action = New-ScheduledTaskAction -Execute $powershell `
        -Argument ('-NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File "{0}" -EnvFile "{1}"' -f $runner, $EnvFile) `
        -WorkingDirectory $PSScriptRoot
    $trigger = New-ScheduledTaskTrigger -Daily -At '06:30'
    $settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew `
        -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries `
        -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 15) `
        -ExecutionTimeLimit (New-TimeSpan -Minutes 10)
    # Oracle/API credentials come from the existing local environment file.
    # SYSTEM allows unattended runs after reboot without storing a Windows password.
    $taskPrincipal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Limited
    Register-ScheduledTask -TaskPath $taskPath -TaskName $taskName -Action $action -Trigger $trigger `
        -Settings $settings -Principal $taskPrincipal -Description $description -Force | Out-Null

    Write-Output "Task ready: $taskName, daily 06:30 (bridge computer time zone: $((Get-TimeZone).Id))."
    Write-Output 'Starting first upload under the scheduled task account...'
    $startedAfter = (Get-Date).AddSeconds(-2)
    Start-ScheduledTask -TaskPath $taskPath -TaskName $taskName
    $deadline = (Get-Date).AddMinutes(11)
    do {
        Start-Sleep -Seconds 2
        $task = Get-ScheduledTask -TaskPath $taskPath -TaskName $taskName
        $info = Get-ScheduledTaskInfo -TaskPath $taskPath -TaskName $taskName
        if ($info.LastRunTime -ge $startedAfter -and $task.State -ne 'Running' -and $task.State -ne 'Queued') {
            if ($info.LastTaskResult -ne 0) {
                throw "First upload failed (task result $($info.LastTaskResult)). Task remains installed; inspect $PSScriptRoot\unit_history_sync.log."
            }
            Write-Output 'SUCCESS: first upload completed. Daily updates are enabled.'
            Write-Output "Next run: $($info.NextRunTime). Log: $PSScriptRoot\unit_history_sync.log"
            Write-Output 'Existing sync_quantum was not changed.'
            exit 0
        }
    } while ((Get-Date) -lt $deadline)
    throw "Timed out waiting for the first upload. Task remains installed; inspect $PSScriptRoot\unit_history_sync.log and Task Scheduler."
} catch {
    Write-Output "ERROR: $($_.Exception.Message)"
    exit 1
}
