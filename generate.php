<?php
ini_set('memory_limit', '4096M');
set_time_limit(0);

/* =====================================================
   PATHS
===================================================== */
$TMP_DIR     = __DIR__ . "/tmp";
$OUT_DIR     = __DIR__ . "/output";
$STATE_FILE  = "$TMP_DIR/state.json";
$PROG_FILE   = "$TMP_DIR/progress.json";
$MASK_FILE   = "$TMP_DIR/mask.png";
$CANVAS_FILE = "$TMP_DIR/canvas.png";
$FONT        = __DIR__ . "/fonts/Montserrat-Bold.ttf";

/* =====================================================
   HELPERS
===================================================== */
function writeProgress($data) {
    file_put_contents(__DIR__ . "/tmp/progress.json", json_encode($data));
}
function readProgress() {
    return json_decode(@file_get_contents(__DIR__ . "/tmp/progress.json"), true);
}
function eta($current, $total, $start) {
    if ($current <= 0) return "";
    $elapsed = time() - $start;
    $rate = $elapsed / $current;
    return gmdate("H:i:s", (int)(($total - $current) * $rate));
}
function safeMkdir($dir) {
    if (!is_dir($dir)) mkdir($dir, 0777, true);
}
function avgBrightness($path) {
    $img = @imagecreatefromstring(@file_get_contents($path));
    if (!$img) return 0;
    $w = imagesx($img); $h = imagesy($img);
    $r=$g=$b=0; $n=40;
    for ($i=0;$i<$n;$i++) {
        $rgb = imagecolorat($img, rand(0,$w-1), rand(0,$h-1));
        $r += ($rgb>>16)&255;
        $g += ($rgb>>8)&255;
        $b += $rgb&255;
    }
    imagedestroy($img);
    return ($r+$g+$b)/($n*3);
}
function clampInt($v,$min,$max){ return max($min, min($max, (int)$v)); }

/* =====================================================
   ENSURE DIRS
===================================================== */
safeMkdir($TMP_DIR);
safeMkdir($OUT_DIR);

/* =====================================================
   CANCEL CHECK (any time)
===================================================== */
$progress = readProgress();
if (is_array($progress) && !empty($progress["cancel"])) {
    writeProgress(["stage"=>"cancelled","message"=>"Job cancelled by user"]);
    if (file_exists($STATE_FILE)) unlink($STATE_FILE);
    exit;
}

/* =====================================================
   INITIAL REQUEST (first call)
===================================================== */
if (!file_exists($STATE_FILE)) {

    // reset progress
    writeProgress([
        "stage" => "init",
        "current" => 0,
        "total" => 1,
        "start_time" => time(),
        "message" => "Initializing",
        "cancel" => false
    ]);

    // basic input
    $text      = trim((string)($_POST['text'] ?? ""));
    $width_ft  = (float)($_POST['width_ft'] ?? 20);
    $height_ft = (float)($_POST['height_ft'] ?? 10);
    $mode      = (string)($_POST['mode'] ?? "preview");
    $format    = (string)($_POST['format'] ?? "tiff");
    $curve     = (string)($_POST['curve'] ?? "none");
    $outline   = (int)($_POST['outline'] ?? 12);
    $glow      = (int)($_POST['glow'] ?? 20);

    if ($text === "") {
        print_r($_POST);die;
        writeProgress(["stage"=>"error","message"=>"Text is required"]);
        exit;
    }

    // dpi/tile by mode
    if ($mode === "preview") {
        $dpi = 30;
        $tile = 40;
        $chunkTiles = 180; // faster chunks for preview
    } else {
        $dpi = (int)($_POST['dpi'] ?? 150);
        $dpi = clampInt($dpi, 30, 600);
        $tile = 120;
        $chunkTiles = 120;
    }

    $outline = clampInt($outline, 0, 200);
    $glow    = clampInt($glow, 0, 300);

    $width_px  = (int)round($width_ft * 12 * $dpi);
    $height_px = (int)round($height_ft * 12 * $dpi);

    if ($width_px <= 0 || $height_px <= 0) {
        writeProgress(["stage"=>"error","message"=>"Invalid poster size"]);
        exit;
    }

    // server-side upload validation
    if (empty($_FILES['images']) || empty($_FILES['images']['tmp_name'])) {
        writeProgress(["stage"=>"error","message"=>"No images uploaded"]);
        exit;
    }

    $maxFiles = 1500;                 // adjust
    $maxEachBytes = 25 * 1024 * 1024; // 25MB per file (adjust)
    $tmpNames = $_FILES['images']['tmp_name'];
    if (count($tmpNames) > $maxFiles) {
        writeProgress(["stage"=>"error","message"=>"Too many images (max $maxFiles)"]);
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $allowed = ["image/jpeg","image/png","image/webp","image/gif"];
    $extMap = [
        "image/jpeg"=>"jpg",
        "image/png"=>"png",
        "image/webp"=>"webp",
        "image/gif"=>"gif"
    ];

    // move uploads + compute brightness buckets
    writeProgress([
        "stage"=>"init",
        "current"=>0,
        "total"=>1,
        "start_time"=>time(),
        "message"=>"Validating and saving uploads",
        "cancel"=>false
    ]);

    $images = [];
    $brightness = [];
    $usage = [];

    foreach ($tmpNames as $i => $tmp) {
        $err = $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK) {
            writeProgress(["stage"=>"error","message"=>"Upload error on file #".($i+1)]);
            exit;
        }

        $size = (int)($_FILES['images']['size'][$i] ?? 0);
        if ($size <= 0 || $size > $maxEachBytes) {
            writeProgress(["stage"=>"error","message"=>"File too large/invalid on file #".($i+1)]);
            exit;
        }

        $mime = $finfo->file($tmp);
        if (!in_array($mime, $allowed, true)) {
            writeProgress(["stage"=>"error","message"=>"Only images allowed. Bad type on file #".($i+1)]);
            exit;
        }

        if (@getimagesize($tmp) === false) {
            writeProgress(["stage"=>"error","message"=>"Corrupt/invalid image on file #".($i+1)]);
            exit;
        }

        $ext = $extMap[$mime] ?? "img";
        $dest = "$TMP_DIR/upload_$i.$ext";
        if (!move_uploaded_file($tmp, $dest)) {
            writeProgress(["stage"=>"error","message"=>"Failed to save upload #".($i+1)]);
            exit;
        }

        $images[] = $dest;
        $b = avgBrightness($dest);
        $brightness[$dest] = $b;
        $usage[$dest] = 0;
    }

    // split into light/dark sets
    $light = [];
    $dark = [];
    foreach ($images as $p) {
        if (($brightness[$p] ?? 0) > 130) $light[] = $p;
        else $dark[] = $p;
    }

    // compute tiles grid
    $cols = (int)ceil($width_px / $tile);
    $rows = (int)ceil($height_px / $tile);
    $totalTiles = $cols * $rows;

    // write state
    $state = [
        "stage" => "text",
        "start" => time(),
        "width_px" => $width_px,
        "height_px" => $height_px,
        "dpi" => $dpi,
        "tile" => $tile,
        "cols" => $cols,
        "rows" => $rows,
        "totalTiles" => $totalTiles,
        "chunkTiles" => $chunkTiles,

        "format" => ($format === "pdf") ? "pdf" : "tiff",
        "curve" => ($curve === "arc") ? "arc" : "none",
        "outline" => $outline,
        "glow" => $glow,
        "text" => $text,

        "images" => $images,
        "light" => $light,
        "dark" => $dark,
        "brightness" => $brightness,
        "usage" => $usage,

        "tile_index" => 0,
        "done" => 0
    ];

    file_put_contents($STATE_FILE, json_encode($state));

    writeProgress([
        "stage"=>"text",
        "current"=>0,
        "total"=>1,
        "start_time"=>$state["start"],
        "message"=>"Creating text mask",
        "cancel"=>false
    ]);

    exit;
}

/* =====================================================
   RESUME JOB
===================================================== */
$state = json_decode(@file_get_contents($STATE_FILE), true);
if (!is_array($state)) {
    writeProgress(["stage"=>"error","message"=>"State file unreadable"]);
    exit;
}

// refresh cancel each chunk
$progress = readProgress();
if (is_array($progress) && !empty($progress["cancel"])) {
    writeProgress(["stage"=>"cancelled","message"=>"Job cancelled by user"]);
    unlink($STATE_FILE);
    exit;
}

/* =====================================================
   STAGE 1: TEXT MASK
===================================================== */
if ($state["stage"] === "text") {

    $w = $state["width_px"];
    $h = $state["height_px"];

    if (!file_exists($FONT)) {
        writeProgress(["stage"=>"error","message"=>"Font missing: fonts/Montserrat-Bold.ttf"]);
        unlink($STATE_FILE);
        exit;
    }

    $mask = imagecreatetruecolor($w, $h);
    imagefill($mask, 0, 0, imagecolorallocate($mask, 0, 0, 0));
    $white = imagecolorallocate($mask, 255, 255, 255);

    $fontSize = (int)max(20, $h / 4);

    // Helper: draw multiline centered
    $lines = preg_split("/\R/", trim($state["text"]));
    if (!$lines) $lines = [$state["text"]];
    $lineHeight = (int)round($fontSize * 1.15);
    $blockHeight = count($lines) * $lineHeight;
    $startY = (int)round(($h / 2) - ($blockHeight / 2) + $fontSize);

    // Glow + outline use same drawing positions
    $drawMultiline = function($img, $size, $color) use ($lines, $lineHeight, $startY, $FONT, $w) {
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if ($line === "") continue;
            $bbox = imagettfbbox($size, 0, $FONT, $line);
            $tw = abs($bbox[2] - $bbox[0]);
            $x = (int)round(($w - $tw) / 2);
            $y = $startY + ($i * $lineHeight);
            imagettftext($img, $size, 0, $x, $y, $color, $FONT, $line);
        }
    };

    if ($state["curve"] === "arc") {
        // Arc text (single-line recommended)
        $txt = str_replace(["\r","\n"], " ", $state["text"]);
        $txt = trim(preg_replace("/\s+/", " ", $txt));
        if ($txt === "") $txt = " ";

        $chars = preg_split('//u', $txt, -1, PREG_SPLIT_NO_EMPTY);
        $count = max(1, count($chars));
        $radius = (int)round($h / 3);
        $cx = (int)round($w / 2);
        $cy = (int)round($h / 2);

        $angleStep = 360 / ($count + 2);
        $angle = -90 - ($count * $angleStep / 2);

        // Glow
        for ($g = (int)$state["glow"]; $g > 0; $g--) {
            $alpha = 127 - ($g * 4);
            $col = imagecolorallocatealpha($mask, 255, 255, 255, max(0, $alpha));
            $a = $angle;
            foreach ($chars as $ch) {
                $rad = deg2rad($a);
                $x = $cx + cos($rad) * $radius;
                $y = $cy + sin($rad) * $radius;
                imagettftext($mask, $fontSize + $g, $a + 90, (int)$x, (int)$y, $col, $FONT, $ch);
                $a += $angleStep;
            }
        }

        // Outline
        $op = (int)$state["outline"];
        for ($ox=-$op; $ox<=$op; $ox++) {
            for ($oy=-$op; $oy<=$op; $oy++) {
                $a = $angle;
                foreach ($chars as $ch) {
                    $rad = deg2rad($a);
                    $x = $cx + cos($rad) * $radius + $ox;
                    $y = $cy + sin($rad) * $radius + $oy;
                    imagettftext($mask, $fontSize, $a + 90, (int)$x, (int)$y, $white, $FONT, $ch);
                    $a += $angleStep;
                }
            }
        }

        // Main
        $a = $angle;
        foreach ($chars as $ch) {
            $rad = deg2rad($a);
            $x = $cx + cos($rad) * $radius;
            $y = $cy + sin($rad) * $radius;
            imagettftext($mask, $fontSize, $a + 90, (int)$x, (int)$y, $white, $FONT, $ch);
            $a += $angleStep;
        }

    } else {
        // Glow (multiline)
        for ($g = (int)$state["glow"]; $g > 0; $g--) {
            $alpha = 127 - ($g * 4);
            $col = imagecolorallocatealpha($mask, 255, 255, 255, max(0, $alpha));
            $drawMultiline($mask, $fontSize + $g, $col);
        }

        // Outline (multiline)
        $op = (int)$state["outline"];
        for ($ox=-$op; $ox<=$op; $ox++) {
            for ($oy=-$op; $oy<=$op; $oy++) {
                foreach ($lines as $i => $line) {
                    $line = trim($line);
                    if ($line === "") continue;
                    $bbox = imagettfbbox($fontSize, 0, $FONT, $line);
                    $tw = abs($bbox[2] - $bbox[0]);
                    $x = (int)round(($w - $tw) / 2) + $ox;
                    $y = $startY + ($i * $lineHeight) + $oy;
                    imagettftext($mask, $fontSize, 0, $x, $y, $white, $FONT, $line);
                }
            }
        }

        // Main (multiline)
        $drawMultiline($mask, $fontSize, $white);
    }

    imagepng($mask, $MASK_FILE);
    imagedestroy($mask);

    // init canvas
    $canvas = imagecreatetruecolor($w, $h);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 0, 0, 0));
    imagepng($canvas, $CANVAS_FILE);
    imagedestroy($canvas);

    // move to mosaic
    $state["stage"] = "mosaic";
    file_put_contents($STATE_FILE, json_encode($state));

    writeProgress([
        "stage"=>"mosaic",
        "current"=>0,
        "total"=>$state["totalTiles"],
        "start_time"=>$state["start"],
        "message"=>"Generating mosaic",
        "eta"=>""
    ]);

    exit;
}

/* =====================================================
   STAGE 2: MOSAIC (CHUNKED)
===================================================== */
if ($state["stage"] === "mosaic") {

    if (!file_exists($MASK_FILE) || !file_exists($CANVAS_FILE)) {
        writeProgress(["stage"=>"error","message"=>"Missing mask/canvas temp files"]);
        unlink($STATE_FILE);
        exit;
    }

    $w = $state["width_px"];
    $h = $state["height_px"];
    $tile = $state["tile"];
    $cols = $state["cols"];
    $totalTiles = $state["totalTiles"];

    $mask = imagecreatefrompng($MASK_FILE);
    $canvas = imagecreatefrompng($CANVAS_FILE);

    // brightness targets: inside text should be lighter, background darker
    $TARGET_TEXT = 215;
    $TARGET_BG   = 45;

    $chunkTiles = (int)$state["chunkTiles"];
    $startIndex = (int)$state["tile_index"];
    $endIndex = min($startIndex + $chunkTiles, $totalTiles);

    for ($idx = $startIndex; $idx < $endIndex; $idx++) {

        // cancel check occasionally
        if (($idx % 25) === 0) {
            $p = readProgress();
            if (is_array($p) && !empty($p["cancel"])) {
                imagedestroy($mask);
                imagedestroy($canvas);
                writeProgress(["stage"=>"cancelled","message"=>"Job cancelled by user"]);
                unlink($STATE_FILE);
                exit;
            }
        }

        $tx = ($idx % $cols) * $tile;
        $ty = (int)(floor($idx / $cols)) * $tile;

        // sample mask
        $mx = min($w-1, $tx + 5);
        $my = min($h-1, $ty + 5);
        $m  = (imagecolorat($mask, $mx, $my) & 255);

        $insideText = ($m > 200);

        $set = $insideText ? ($state["light"] ?? []) : ($state["dark"] ?? []);
        // fallback if one side empty
        if (!$set) $set = $state["images"];

        $target = $insideText ? $TARGET_TEXT : $TARGET_BG;

        // pick best match: brightness distance + reuse penalty
        $best = null;
        $bestScore = 1e18;

        foreach ($set as $imgPath) {
            $b = $state["brightness"][$imgPath] ?? 0;
            $u = $state["usage"][$imgPath] ?? 0;
            $score = abs($b - $target) + ($u * 8);
            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $imgPath;
            }
        }

        if ($best) {
            $state["usage"][$best] = ($state["usage"][$best] ?? 0) + 1;

            $src = @imagecreatefromstring(@file_get_contents($best));
            if ($src) {
                imagecopyresampled(
                    $canvas, $src,
                    $tx, $ty, 0, 0,
                    $tile, $tile,
                    imagesx($src), imagesy($src)
                );
                imagedestroy($src);
            }
        }

        $state["done"] = $idx + 1;
        $state["tile_index"] = $idx + 1;
    }

    imagepng($canvas, $CANVAS_FILE);
    imagedestroy($mask);
    imagedestroy($canvas);

    file_put_contents($STATE_FILE, json_encode($state));

    // update progress
    writeProgress([
        "stage"=>"mosaic",
        "current"=>$state["done"],
        "total"=>$totalTiles,
        "start_time"=>$state["start"],
        "message"=>"Rendering tiles ({$state["done"]} / {$totalTiles})",
        "eta"=>eta($state["done"], $totalTiles, $state["start"]),
        "cancel"=>false
    ]);

    // done with mosaic?
    if ($state["tile_index"] >= $totalTiles) {
        $state["stage"] = "export";
        file_put_contents($STATE_FILE, json_encode($state));

        writeProgress([
            "stage"=>"export",
            "current"=>0,
            "total"=>1,
            "start_time"=>$state["start"],
            "message"=>"Exporting final file",
            "eta"=>""
        ]);
    }

    exit;
}

/* =====================================================
   STAGE 3: EXPORT (CMYK PDF/TIFF)
===================================================== */
if ($state["stage"] === "export") {

    if (!file_exists($CANVAS_FILE)) {
        writeProgress(["stage"=>"error","message"=>"Missing canvas file for export"]);
        unlink($STATE_FILE);
        exit;
    }

    $format = ($state["format"] === "pdf") ? "pdf" : "tiff";
    $outPath = $OUT_DIR . "/mosaic_cmyk." . $format;

    // export using ImageMagick
    $dpi = (int)$state["dpi"];
    $cmd = "convert " .
        escapeshellarg($CANVAS_FILE) . " " .
        "-colorspace CMYK -density " . escapeshellarg((string)$dpi) . " -units PixelsPerInch " .
        "-compress lzw " .
        escapeshellarg($outPath);

    @exec($cmd, $o, $code);

    if ($code !== 0 || !file_exists($outPath)) {
        writeProgress(["stage"=>"error","message"=>"Export failed. Is ImageMagick 'convert' installed?"]);
        unlink($STATE_FILE);
        exit;
    }

    writeProgress([
        "stage"=>"done",
        "current"=>1,
        "total"=>1,
        "message"=>"Completed. Output: " . basename($outPath),
        "eta"=>""
    ]);

    // cleanup only state (keep canvas/mask if you want debugging)
    unlink($STATE_FILE);
    exit;
}

/* =====================================================
   FALLBACK
===================================================== */
writeProgress(["stage"=>"error","message"=>"Unknown stage"]);
