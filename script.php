<?php
/**
 * Project bootstrap script
 * Run once, then DELETE this file.
 */

$base = __DIR__;

/* =========================
   FOLDERS TO CREATE
========================= */
$folders = [
    "uploads",
    "output",
    "tmp",
    "fonts"
];

/* =========================
   FILES TO CREATE
========================= */
$files = [
    "index.php",
    "generate.php",
    "progress.php",
    "cancel.php"
];

/* =========================
   CREATE FOLDERS
========================= */
foreach ($folders as $folder) {
    $path = $base . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
        echo "Created folder: $folder<br>";
    } else {
        echo "Folder exists: $folder<br>";
    }

    // Keep empty folders in git
    $gitkeep = $path . DIRECTORY_SEPARATOR . ".gitkeep";
    if (!file_exists($gitkeep)) {
        file_put_contents($gitkeep, "");
    }
}

/* =========================
   CREATE EMPTY PHP FILES
========================= */
foreach ($files as $file) {
    $path = $base . DIRECTORY_SEPARATOR . $file;
    if (!file_exists($path)) {
        file_put_contents($path, "<?php\n\n");
        echo "Created file: $file<br>";
    } else {
        echo "File exists: $file<br>";
    }
}

/* =========================
   FONTS README
========================= */
$fontReadme = $base . "/fonts/README.txt";
if (!file_exists($fontReadme)) {
    file_put_contents(
        $fontReadme,
        "Place your .ttf fonts here.\nExample:\nMontserrat-Bold.ttf\n"
    );
}

/* =========================
   DONE
========================= */
echo "<hr>";
echo "<strong>Setup complete.</strong><br>";
echo "You can now copy-paste code into the created files.<br>";
echo "Delete <code>script.php</code> after setup.";
