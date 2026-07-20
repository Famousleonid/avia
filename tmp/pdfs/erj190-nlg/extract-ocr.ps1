param(
    [string] $Path,
    [string] $Directory,
    [string] $OutputPath
)

$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.Runtime.WindowsRuntime

$typeNames = @(
    'Windows.Storage.StorageFile, Windows, ContentType=WindowsRuntime',
    'Windows.Storage.Streams.IRandomAccessStream, Windows, ContentType=WindowsRuntime',
    'Windows.Storage.FileAccessMode, Windows, ContentType=WindowsRuntime',
    'Windows.Graphics.Imaging.BitmapDecoder, Windows, ContentType=WindowsRuntime',
    'Windows.Graphics.Imaging.SoftwareBitmap, Windows, ContentType=WindowsRuntime',
    'Windows.Media.Ocr.OcrEngine, Windows, ContentType=WindowsRuntime',
    'Windows.Media.Ocr.OcrResult, Windows, ContentType=WindowsRuntime'
)

foreach ($typeName in $typeNames) {
    $null = [type] $typeName
}

$asTask = [System.WindowsRuntimeSystemExtensions].GetMethods() |
    Where-Object {
        $_.Name -eq 'AsTask' -and $_.IsGenericMethodDefinition -and
        $_.GetGenericArguments().Count -eq 1 -and $_.GetParameters().Count -eq 1
    } |
    Select-Object -First 1

function Await-WinRt($operation, [type] $resultType) {
    $task = $asTask.MakeGenericMethod($resultType).Invoke($null, @($operation))
    return $task.GetAwaiter().GetResult()
}

function Get-OcrResult([string] $imagePath) {
    $file = Await-WinRt ([Windows.Storage.StorageFile]::GetFileFromPathAsync($imagePath)) ([Windows.Storage.StorageFile])
    $stream = Await-WinRt ($file.OpenAsync([Windows.Storage.FileAccessMode]::Read)) ([Windows.Storage.Streams.IRandomAccessStream])
    $decoder = Await-WinRt ([Windows.Graphics.Imaging.BitmapDecoder]::CreateAsync($stream)) ([Windows.Graphics.Imaging.BitmapDecoder])
    $bitmap = Await-WinRt ($decoder.GetSoftwareBitmapAsync()) ([Windows.Graphics.Imaging.SoftwareBitmap])
    $engine = [Windows.Media.Ocr.OcrEngine]::TryCreateFromUserProfileLanguages()

    return Await-WinRt ($engine.RecognizeAsync($bitmap)) ([Windows.Media.Ocr.OcrResult])
}

if ($Directory) {
    $records = foreach ($image in Get-ChildItem -LiteralPath $Directory -Filter '*.png' | Sort-Object Name) {
        if ($image.Name -notmatch '^f(?<figure>\d+)-p\d+\.png$') {
            continue
        }

        $figure = $Matches.figure
        $ocr = Get-OcrResult $image.FullName
        $lines = $ocr.Lines | ForEach-Object {
            $firstWord = $_.Words | Select-Object -First 1
            [pscustomobject]@{
                x = [math]::Round($firstWord.BoundingRect.X)
                y = [math]::Round($firstWord.BoundingRect.Y)
                text = $_.Text.Trim()
            }
        }

        $pageRecords = @()
        foreach ($line in $lines | Where-Object { $_.x -lt 650 -and $_.y -gt 330 }) {
            $matched = $line.text -match '(?i)-\s*(?<item>\d+[A-Z]*)\s+(?<part>[A-Z0-9][A-Z0-9\-\.]+)'
            if (-not $matched) {
                $matched = $line.text -match '(?i)^\s*(?<item>\d+[A-Z]*)\s+(?<part>(?=[A-Z0-9\-\.]*[A-Z-])[A-Z0-9][A-Z0-9\-\.]+)\s*$'
            }
            if (-not $matched) {
                continue
            }

            $item = $Matches.item.ToUpperInvariant()
            $partNumber = $Matches.part.ToUpperInvariant()
            if ($partNumber -notmatch '\d') {
                continue
            }

            $nameLine = $lines |
                Where-Object {
                    $_.x -ge 640 -and $_.x -lt 1900 -and
                    [math]::Abs($_.y - $line.y) -le 42 -and
                    $_.text -match '[A-Za-z]' -and
                    $_.text -notmatch '^\(' -and
                    $_.text -notmatch '^(SEE|SUPSD|ATTACHING|USE ONLY|ITEM NOT|PRE SB|POST SB)'
                } |
                Sort-Object @{ Expression = { [math]::Abs($_.y - $line.y) } }, y |
                Select-Object -First 1

            $pageRecords += [pscustomobject]@{
                figure = [int] $figure
                item = $item
                ipl_num = "$figure-$item"
                part_number = $partNumber
                name_ocr = if ($nameLine) { ($nameLine.text -replace '^\.+\s*', '' -replace '\s+NP$', '').Trim() } else { '' }
                source_page = $image.Name
                source_y = $line.y
            }
        }

        $itemLines = $lines | Where-Object {
            $_.x -ge 130 -and $_.x -lt 265 -and $_.y -gt 330 -and
            $_.text -match '^\s*-?\s*\d{1,3}[A-Z]{0,2}\s*$'
        }
        $partLines = $lines | Where-Object {
            $_.x -ge 255 -and $_.x -lt 650 -and $_.y -gt 330 -and
            $_.text -match '(?=.*\d)^[A-Z0-9\-\.\s,]+$' -and
            $_.text -notmatch '^(ITEM|PAGE|DEC|32-)'
        }

        foreach ($partLine in $partLines) {
            $itemLine = $itemLines |
                Where-Object { [math]::Abs($_.y - $partLine.y) -le 18 } |
                Sort-Object @{ Expression = { [math]::Abs($_.y - $partLine.y) } }, y |
                Select-Object -First 1
            if (-not $itemLine) {
                continue
            }

            $item = ($itemLine.text -replace '[^0-9A-Z]', '').ToUpperInvariant()
            $partNumber = ($partLine.text -replace '[^A-Z0-9\-]', '').ToUpperInvariant()
            if ($item -notmatch '^\d+[A-Z]{0,2}$' -or $partNumber.Length -lt 4 -or $partNumber -notmatch '\d' -or $partNumber.StartsWith('-')) {
                continue
            }

            $nameLine = $lines |
                Where-Object {
                    $_.x -ge 640 -and $_.x -lt 1900 -and
                    [math]::Abs($_.y - $partLine.y) -le 42 -and
                    $_.text -match '[A-Za-z]' -and
                    $_.text -notmatch '^\(' -and
                    $_.text -notmatch '^(SEE|SUPSD|ATTACHING|USE ONLY|ITEM NOT|PRE SB|POST SB)'
                } |
                Sort-Object @{ Expression = { [math]::Abs($_.y - $partLine.y) } }, y |
                Select-Object -First 1

            $pageRecords += [pscustomobject]@{
                figure = [int] $figure
                item = $item
                ipl_num = "$figure-$item"
                part_number = $partNumber
                name_ocr = if ($nameLine) { ($nameLine.text -replace '^\.+\s*', '' -replace '\s+NP$', '').Trim() } else { '' }
                source_page = $image.Name
                source_y = $partLine.y
            }
        }

        $pageRecords | Sort-Object ipl_num, source_y -Unique
    }

    if (-not $OutputPath) {
        $records | Sort-Object figure, ipl_num | ConvertTo-Json -Depth 3
    } else {
        $records | Export-Csv -LiteralPath $OutputPath -NoTypeInformation -Encoding utf8
        $records | Group-Object figure | ForEach-Object { '{0}: {1}' -f $_.Name, $_.Count }
        'TOTAL: {0}' -f @($records).Count
    }
    exit
}

if (-not $Path) {
    throw 'Specify either -Path or -Directory.'
}

$ocr = Get-OcrResult $Path
$ocr.Lines | ForEach-Object {
    $firstWord = $_.Words | Select-Object -First 1
    $x = [math]::Round($firstWord.BoundingRect.X)
    $y = [math]::Round($firstWord.BoundingRect.Y)
    '{0,5} {1,5} {2}' -f $x, $y, $_.Text
}
