[CmdletBinding(SupportsShouldProcess=$true)]
param()
$ErrorActionPreference='Stop'
$root=[IO.Path]::GetFullPath($PSScriptRoot).TrimEnd('\')
if ($root -ne 'C:\avia\Stock') { throw 'Expected C:\avia\Stock.' }
$principal=New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (!$principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) { throw 'Run PowerShell as Administrator.' }
foreach($name in @('run_stock_daily.ps1','stock_export.php','stock_query.sql')) {
    if (!(Test-Path -LiteralPath (Join-Path $root $name))) { throw "Missing $name" }
}
$taskName='sync_quantum_stock_daily'
$description='AVIA Stock: daily warehouse and reservation snapshot at 05:00.'
$existing=Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existing -and ($existing.Description -ne $description -or $existing.State -eq 'Running')) { throw 'Stock task conflict; inspect before replacing.' }
$service=Get-CimInstance Win32_Service -Filter "Name='AVIAStock'"
if ($service -and $service.PathName.Trim('"') -ne (Join-Path $root 'AVIAStockService.exe')) { throw 'Unexpected AVIAStock service path.' }
if (!$PSCmdlet.ShouldProcess('AVIAStock and sync_quantum_stock_daily','Remove Stock tunnel service, install 05:00 Stock task, and run first export')) { exit }
if ($service) {
    Stop-Service AVIAStock -ErrorAction Stop
    (Get-Service AVIAStock).WaitForStatus('Stopped',[TimeSpan]::FromSeconds(30))
    & sc.exe delete AVIAStock
    if ($LASTEXITCODE -ne 0) { throw 'Cannot remove AVIAStock service.' }
}
# Exact Stock files only; never recurse and never touch RO, WO, or other tasks.
foreach($name in @('AVIAStockService.exe','stock_tunnel.key','stock_tunnel.key.pub','production_known_hosts','tunnel-diagnostic.log','stock_router.php','check_stock_tunnel.ps1','check_stock_service.php','start_stock_service.ps1','install_stock_service.ps1','run_stock.ps1')) {
    $target=[IO.Path]::GetFullPath((Join-Path $root $name))
    if ([IO.Path]::GetDirectoryName($target) -ne $root) { throw 'Cleanup target outside Stock.' }
    if (Test-Path -LiteralPath $target) { Remove-Item -LiteralPath $target -Force }
}
$exe=Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'
$action=New-ScheduledTaskAction -Execute $exe -Argument '-NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File C:\avia\Stock\run_stock_daily.ps1' -WorkingDirectory $root
$trigger=New-ScheduledTaskTrigger -Daily -At '05:00'
$settings=New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -ExecutionTimeLimit (New-TimeSpan -Minutes 10)
$account=New-ScheduledTaskPrincipal -UserId SYSTEM -LogonType ServiceAccount -RunLevel Limited
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Principal $account -Description $description -Force | Out-Null
Start-ScheduledTask -TaskName $taskName
Write-Output 'Stock task installed at 05:00 bridge time; first export requested. Check its result and stock_sync.log. sync_quantum and Unit History unchanged.'
Get-ScheduledTaskInfo -TaskName $taskName
