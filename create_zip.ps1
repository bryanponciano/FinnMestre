Add-Type -AssemblyName System.IO.Compression.FileSystem
$sourceDir = "C:\Users\Bryan\Documents\Projetos Bryan - 01\FinMestre\financeiro"
$tempDir = "C:\Users\Bryan\Documents\Projetos Bryan - 01\FinMestre\temp_deploy_linux"
$zipPath = "C:\Users\Bryan\Documents\Projetos Bryan - 01\FinMestre\update_finmestre.zip"
if (Test-Path $tempDir) { Remove-Item $tempDir -Recurse -Force }
Copy-Item -Path $sourceDir -Destination $tempDir -Recurse
Remove-Item -Path "$tempDir\config\database.php" -Force -ErrorAction SilentlyContinue
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
$zip = [System.IO.Compression.ZipFile]::Open($zipPath, 'Create')
Get-ChildItem -Path $tempDir -File -Recurse | ForEach-Object {
    $relativePath = $_.FullName.Substring($tempDir.Length + 1)
    $linuxPath = $relativePath.Replace('\', '/')
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $linuxPath)
}
$zip.Dispose()
Remove-Item $tempDir -Recurse -Force
