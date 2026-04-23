# Sync WebP: proof, services, hero, work grid (wg-01..25). Tenant 4.
$ErrorActionPreference = "Stop"
$srcProof = "C:\OSPanel\home\rentbase-media\tenants\4\public\site\brand\proof"
$mediaBrand = "C:\OSPanel\home\rentbase-media\tenants\4\public\site\brand"
$localBrand = "C:\OSPanel\home\rentbase.local\storage\app\public\tenants\4\public\site\brand"

function Ensure-Dir($p) { New-Item -ItemType Directory -Force -Path $p | Out-Null }

Ensure-Dir "$localBrand\proof"
Ensure-Dir "$localBrand\services"
Ensure-Dir "$mediaBrand\services"

Copy-Item -Path "$srcProof\*.webp" -Destination "$localBrand\proof\" -Force

Copy-Item -Path "$srcProof\XXXL (8).webp" -Destination "$localBrand\hero-1916.webp" -Force
Copy-Item -Path "$srcProof\XXXL (8).webp" -Destination "$mediaBrand\hero-1916.webp" -Force

$serviceMap = [ordered]@{
    "polirovka-kuzova" = "XXXL (21).webp"
    "keramika"         = "XXXL (25).webp"
    "ppf"              = "XXXL (11).webp"
    "tonirovka"        = "XXXL (32).webp"
    "himchistka-salona"= "XXXL (24).webp"
    "shumka"           = "XXXL (19).webp"
    "detejling-mojka"  = "XXXL (12).webp"
    "podkapotnaya-himchistka" = "XXXL (2).webp"
    "kozha-keramika"   = "XXXL (45).webp"
    "pdr"              = "XXXL (22).webp"
    "himchistka-kuzova"= "XXXL (7).webp"
    "himchistka-diskov"= "XXXL (15).webp"
    "antidozhd"        = "XXXL (16).webp"
    "bronirovanie-salona" = "XXXL (38).webp"
    "remont-skolov"    = "XXXL (14).webp"
    "restavratsiya-kozhi" = "XXXL (10).webp"
    "setki-radiatora"  = "XXXL (1).webp"
    "predprodazhnaya"  = "XXXL (43).webp"
    "vinil"            = "XXXL (26).webp"
}

foreach ($e in $serviceMap.GetEnumerator()) {
    $slug = $e.Key
    $fn = $e.Value
    $src = Join-Path $srcProof $fn
    if (-not (Test-Path $src)) { Write-Warning "Missing: $src"; continue }
    Copy-Item -Path $src -Destination "$localBrand\services\$slug.webp" -Force
    Copy-Item -Path $src -Destination "$mediaBrand\services\$slug.webp" -Force
}

$workGrid = @(
    @{n=1;  src="XXXL (1).webp";  },
    @{n=2;  src="XXXL (4).webp";  },
    @{n=3;  src="XXXL (8).webp";  },
    @{n=4;  src="XXXL (12).webp"; },
    @{n=5;  src="XXXL (42).webp"; },
    @{n=6;  src="XXXL (43).webp"; },
    @{n=7;  src="XXXL.webp";      },
    @{n=8;  src="XXXL (11).webp"; },
    @{n=9;  src="XXXL (21).webp"; },
    @{n=10; src="XXXL (22).webp"; },
    @{n=11; src="XXXL (24).webp"; },
    @{n=12; src="XXXL (26).webp"; },
    @{n=13; src="XXXL (32).webp"; },
    @{n=14; src="XXXL (37).webp"; },
    @{n=15; src="XXXL (38).webp"; },
    @{n=16; src="XXXL (44).webp"; },
    @{n=17; src="XXXL (45).webp"; },
    @{n=18; src="XXXL (13).webp"; },
    @{n=19; src="XXXL (14).webp"; },
    @{n=20; src="XXXL (25).webp"; },
    @{n=21; src="XXXL (28).webp"; },
    @{n=22; src="XXXL (31).webp"; },
    @{n=23; src="XXXL (23).webp"; },
    @{n=24; src="XXXL (35).webp"; },
    @{n=25; src="XXXL (46).webp"; }
)

foreach ($row in $workGrid) {
    $src = Join-Path $srcProof $row.src
    if (-not (Test-Path $src)) { Write-Warning "Skip wg $($row.n): $($row.src)"; continue }
    $base = "wg-{0:D2}.webp" -f $row.n
    Copy-Item -Path $src -Destination "$localBrand\proof\$base" -Force
    Copy-Item -Path $src -Destination "$mediaBrand\proof\$base" -Force
}

Write-Host "OK"
