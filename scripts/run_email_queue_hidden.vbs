Option Explicit

Dim shell
Set shell = CreateObject("WScript.Shell")
shell.Run """C:\xampp\php\php.exe"" ""C:\xampp\htdocs\Flex\scripts\process_email_queue.php""", 0, True
