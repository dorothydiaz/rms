Add-Type -AssemblyName System.Drawing
$bmp = [System.Drawing.Bitmap]::FromFile('C:\Users\JABIGUERO\.gemini\antigravity-ide\brain\0e30bb16-ea56-4570-8175-aba3f4b570c5\.user_uploaded\media_1791213613626.png')
for ($y = 0; $y -lt $bmp.Height; $y += 3) {
    $line = ""
    for ($x = 0; $x -lt 200; $x += 2) {
        $c = $bmp.GetPixel($x, $y)
        if ($c.R -gt 245 -and $c.G -gt 245 -and $c.B -gt 245) {
            $line += " "
        } elseif ($c.R -gt 200 -and $c.G -gt 200 -and $c.B -gt 200) {
            $line += "."
        } else {
            $line += "#"
        }
    }
    Write-Output $line
}
$bmp.Dispose()
