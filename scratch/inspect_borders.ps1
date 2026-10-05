Add-Type -AssemblyName System.Drawing
$bmp = [System.Drawing.Bitmap]::FromFile('C:\Users\JABIGUERO\.gemini\antigravity-ide\brain\0e30bb16-ea56-4570-8175-aba3f4b570c5\.user_uploaded\media_1791213613626.png')
Write-Output "Image width: $($bmp.Width), height: $($bmp.Height)"

for ($y = 0; $y -lt 10; $y++) {
    $c = $bmp.GetPixel(50, $y)
    $hex = "{0:X2}{1:X2}{2:X2}" -f $c.R, $c.G, $c.B
    Write-Output "y=$y at x=50: #$hex"
}

for ($x = 0; $x -lt 150; $x++) {
    $c = $bmp.GetPixel($x, 18)
    if ($c.R -lt 240) {
        $hex = "{0:X2}{1:X2}{2:X2}" -f $c.R, $c.G, $c.B
        Write-Output "Darker at x=$x : #$hex"
    }
}
$bmp.Dispose()
