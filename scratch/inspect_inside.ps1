Add-Type -AssemblyName System.Drawing
$bmp = [System.Drawing.Bitmap]::FromFile('C:\Users\JABIGUERO\.gemini\antigravity-ide\brain\0e30bb16-ea56-4570-8175-aba3f4b570c5\.user_uploaded\media_1791213613626.png')

for ($y = 5; $y -lt 30; $y += 2) {
    $line = ""
    for ($x = 10; $x -lt 96; $x += 2) {
        $c = $bmp.GetPixel($x, $y)
        if ($c.R -gt 250 -and $c.G -gt 250 -and $c.B -gt 250) {
            $line += " "
        } elseif ($c.R -gt 200 -and $c.G -gt 200 -and $c.B -gt 200) {
            $line += "."
        } else {
            $line += "#"
        }
    }
    Write-Output ("y=" + $y + ": " + $line)
}
$bmp.Dispose()
