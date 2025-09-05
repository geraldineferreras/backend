param(
    [Parameter(Mandatory=$true)]
    [string]$InputPath,
    [Parameter(Mandatory=$true)]
    [string]$OutputPath
)

$ErrorActionPreference = 'Stop'

if (-not (Test-Path -LiteralPath $InputPath)) {
    throw "Input file not found: $InputPath"
}

$inInsertBlock = $false

# Stream process to avoid loading entire file in memory
Get-Content -LiteralPath $InputPath | ForEach-Object {
    $line = $_

    if (-not $inInsertBlock) {
        # Skip DROP TABLE IF EXISTS ... ; lines
        if ($line -match '^\s*DROP\s+TABLE\s+IF\s+EXISTS\b') {
            return
        }

        # Detect start of INSERT INTO ... ; block
        if ($line -match '^\s*INSERT\s+INTO\b') {
            $inInsertBlock = $true
            # If the INSERT ends on the same line, exit block immediately
            if ($line -match ';\s*$') { $inInsertBlock = $false }
            return
        }

        # Pass-through any other lines
        $line
    }
    else {
        # Inside INSERT block, wait until statement terminator
        if ($line -match ';\s*$') { $inInsertBlock = $false }
        return
    }
} | Set-Content -LiteralPath $OutputPath -Encoding UTF8

Write-Output "Wrote cleaned SQL to: $OutputPath"


