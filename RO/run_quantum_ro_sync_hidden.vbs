Option Explicit

Dim shell
Dim command

Set shell = CreateObject("WScript.Shell")
shell.CurrentDirectory = "C:\OSPanel\domains\avia.loc\RO"

command = """C:\OSPanel\modules\php\PHP_8.1\php.exe"" ""C:\OSPanel\domains\avia.loc\RO\sync.php"""

' Window style 0 keeps the PHP console hidden.
shell.Run command, 0, False
