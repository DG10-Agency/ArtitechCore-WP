# ArtitechCore Local-WP Sync Script
# This script synchronizes the plugin files to the LocalWP installation.

$source = Get-Location
$destination = "C:\Users\krris\Local Sites\uniqueholisticsolutions\app\public\wp-content\plugins\artitechcore-for-wordpress"

Write-Host "Syncing ArtitechCore to: $destination" -ForegroundColor Cyan

# Use robocopy for fast, mirrored synchronization
# /MIR  : Mirrors a directory tree (behaves like rsync)
# /XD   : Excludes directories (.git, .claude, docs, etc.)
# /XF   : Excludes specific files (.gitignore, ZIPs, scratch scripts)
robocopy $source $destination /MIR /XD .git .claude docs assets/src /XF .gitignore artitechcore-for-wordpress.zip *.log scratch_*.php

if ($LASTEXITCODE -lt 8) {
    Write-Host "Success: Plugin updated in LocalWP." -ForegroundColor Green
} else {
    Write-Host "Warning: Robocopy finished with exit code $LASTEXITCODE. Check for errors." -ForegroundColor Yellow
}
