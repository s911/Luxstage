# Prepare Luxstage offline deployment bundle on Windows (internet required).
# Downloads plugin/core zips into deploy/offline/packages/.
# Docker images are NOT downloaded here; prepare them separately on the target server.
param(
    [string]$WpCoreVersion = "6.6.2",
    [switch]$IncludeDockerImages
)

$ErrorActionPreference = "Stop"

$ProjectRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$OfflineDir = Join-Path $ProjectRoot "deploy\offline"
$PackagesDir = Join-Path $OfflineDir "packages"
$ImagesDir = Join-Path $OfflineDir "images"
$ToolsDir = Join-Path $OfflineDir "tools"

New-Item -ItemType Directory -Force -Path $PackagesDir, $ImagesDir, $ToolsDir | Out-Null

function Write-Step([string]$Message) {
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Download-File([string]$Url, [string]$Destination) {
    if (Test-Path $Destination) {
        Write-Host "SKIP: $(Split-Path -Leaf $Destination) already exists"
        return
    }
    Write-Host "Downloading $Url"
    curl.exe -fL $Url -o $Destination
    if ($LASTEXITCODE -ne 0) {
        throw "Download failed: $Url"
    }
}

function Ensure-Crane {
    $CraneExe = Join-Path $ToolsDir "crane.exe"
    if (Test-Path $CraneExe) {
        return $CraneExe
    }

    Write-Step "Downloading crane (image pull tool for Windows)"
    $Archive = Join-Path $env:TEMP "go-containerregistry-windows.tar.gz"
    Download-File "https://github.com/google/go-containerregistry/releases/latest/download/go-containerregistry_Windows_x86_64.tar.gz" $Archive

    tar -xzf $Archive -C $ToolsDir crane.exe
    if (-not (Test-Path $CraneExe)) {
        throw "crane.exe not found after extraction"
    }
    return $CraneExe
}

function Save-DockerImage([string]$CraneExe, [string]$ImageRef, [string]$OutputTar) {
    if (Test-Path $OutputTar) {
        Write-Host "SKIP: $(Split-Path -Leaf $OutputTar) already exists"
        return
    }
    Write-Host "Pulling image $ImageRef -> $(Split-Path -Leaf $OutputTar)"
    & $CraneExe pull $ImageRef $OutputTar
    if ($LASTEXITCODE -ne 0) {
        throw "crane pull failed for $ImageRef"
    }
}

Write-Step "Downloading WordPress core"
Download-File "https://wordpress.org/wordpress-$WpCoreVersion.zip" (Join-Path $PackagesDir "wordpress-$WpCoreVersion.zip")

Write-Step "Downloading required plugins"
$Plugins = @(
    @{ Name = "advanced-custom-fields"; Url = "https://downloads.wordpress.org/plugin/advanced-custom-fields.latest-stable.zip" },
    @{ Name = "contact-form-7"; Url = "https://downloads.wordpress.org/plugin/contact-form-7.6.1.6.zip" },
    @{ Name = "polylang"; Url = "https://downloads.wordpress.org/plugin/polylang.latest-stable.zip" },
    @{ Name = "seo-by-rank-math"; Url = "https://downloads.wordpress.org/plugin/seo-by-rank-math.latest-stable.zip" }
)

foreach ($Plugin in $Plugins) {
    Download-File $Plugin.Url (Join-Path $PackagesDir "$($Plugin.Name).zip")
}

Write-Step "Downloading optional plugins"
$OptionalPlugins = @(
    @{ Name = "fluentform"; Url = "https://downloads.wordpress.org/plugin/fluentform.latest-stable.zip" },
    @{ Name = "elementor"; Url = "https://downloads.wordpress.org/plugin/elementor.latest-stable.zip" },
    @{ Name = "webp-converter-for-media"; Url = "https://downloads.wordpress.org/plugin/webp-converter-for-media.latest-stable.zip" }
)

foreach ($Plugin in $OptionalPlugins) {
    try {
        Download-File $Plugin.Url (Join-Path $PackagesDir "$($Plugin.Name).zip")
    } catch {
        Write-Warning "Optional download failed: $($Plugin.Name) - $($_.Exception.Message)"
    }
}

$RootCf7 = Join-Path $ProjectRoot "contact-form-7.6.1.6.zip"
$TargetCf7 = Join-Path $PackagesDir "contact-form-7.zip"
if ((Test-Path $RootCf7) -and -not (Test-Path $TargetCf7)) {
    Copy-Item $RootCf7 $TargetCf7
    Write-Host "Copied contact-form-7.zip from project root"
}

if ($IncludeDockerImages) {
    Write-Step "Downloading Docker images as offline tar archives (optional)"
    $WordPressImage = "wordpress:6.6.2-php8.3-apache"
    $CraneExe = Ensure-Crane
    Save-DockerImage $CraneExe $WordPressImage (Join-Path $ImagesDir "wordpress.tar")
    Save-DockerImage $CraneExe "mysql:8.0" (Join-Path $ImagesDir "mysql-8.0.tar")
    Save-DockerImage $CraneExe "wordpress:cli" (Join-Path $ImagesDir "wordpress-cli.tar")
} else {
    Write-Step "Skipping Docker image download (default)"
    Write-Host "Prepare Docker images on the deployment server instead. See deploy/offline/ARTIFACTS.md"
}

$ImageList = @(Get-ChildItem $ImagesDir -File -ErrorAction SilentlyContinue | ForEach-Object { $_.Name })
if ($ImageList.Count -eq 0) {
    $ImageList = @("(skipped - prepare on server)")
}

$Manifest = @"
Generated: $(Get-Date -Format o)
Host: $env:COMPUTERNAME
WordPress core: wordpress-$WpCoreVersion.zip
Docker images included: $($IncludeDockerImages.IsPresent)

Images:
$($ImageList -join "`n")

Packages:
$(Get-ChildItem $PackagesDir -File | ForEach-Object { $_.Name })
"@

$Manifest | Out-File -FilePath (Join-Path $OfflineDir "bundle-manifest.txt") -Encoding utf8

Write-Step "Offline bundle ready"
Write-Host $Manifest
Write-Host ""
Write-Host "Copy the whole Luxstage folder to the offline server, then run:" -ForegroundColor Green
Write-Host "  sudo bash deploy/one-click-deploy-offline.sh --domain YOUR_IP --email admin@example.com --seed-demo-data"
