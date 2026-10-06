Add-Type -AssemblyName System.IO.Compression.FileSystem
$src = "C:\Users\Bryan\OneDrive\Documentos\Projetos Bryan - 01\FinMestre\financeiro"
$zip1 = "C:\Users\Bryan\OneDrive\Documentos\Projetos Bryan - 01\FinMestre\update_finmestre.zip"
$zip2 = "C:\Users\Bryan\OneDrive\Documentos\Projetos Bryan - 01\FinMestre\financeiro\update_finmestre.zip"

if (Test-Path $zip1) { Remove-Item $zip1 -Force }
if (Test-Path $zip2) { Remove-Item $zip2 -Force }

$zip = [System.IO.Compression.ZipFile]::Open($zip1, 'Create')
Get-ChildItem -Path $src -File -Recurse | Where-Object { 
    $_.Extension -ne '.zip' -and $_.FullName -notlike "*config\database.php"
} | ForEach-Object {
    $rel = $_.FullName.Substring($src.Length + 1).Replace('\', '/')
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $rel)
}
$zip.Dispose()

Copy-Item $zip1 $zip2 -Force
Write-Host "ZIP atualizado criado com sucesso SEM a senha do banco local!"
