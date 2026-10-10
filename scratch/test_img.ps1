Add-Type -AssemblyName System.Drawing
$imgPath = 'C:\Users\JABIGUERO\.gemini\antigravity-ide\brain\0e30bb16-ea56-4570-8175-aba3f4b570c5\.user_uploaded\media_1791213613626.png'
$bmp = [System.Drawing.Bitmap]::FromFile($imgPath)
Write-Output "Size: $($bmp.Width) x $($bmp.Height)"

# Sample horizontal line across the middle
$changes = @()
$prevColor = ""
for ($x = 0; $x -lt $bmp.Width; $x++) {
    $c = $bmp.GetPixel($x, 18)
    $hex = "{0:X2}{1:X2}{2:X2}" -f $c.R, $c.G, $c.B
    if ($hex -ne $prevColor) {
        $changes += "x=$x : #$hex"
        $prevColor = $hex
    }
}
$changes | Select-Object -First 30 | Write-Output
Write-Output "--- LAST 20 ---"
$changes | Select-Object -Last 20 | Write-Output
$bmp.Dispose()
