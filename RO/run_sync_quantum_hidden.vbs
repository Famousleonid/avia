Option Explicit

Dim shell
Dim fso
Dim folder
Dim runner
Dim command

Set shell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

folder = fso.GetParentFolderName(WScript.ScriptFullName)
runner = fso.BuildPath(folder, "run_sync_quantum.ps1")

shell.CurrentDirectory = folder
command = "powershell.exe -NoProfile -ExecutionPolicy Bypass -File " & Chr(34) & runner & Chr(34)

' Window style 0 keeps the PowerShell/PHP console hidden.
shell.Run command, 0, False
