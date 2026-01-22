<?php
header("Content-Type: application/json");

$progFile = __DIR__ . "/tmp/progress.json";

if (!file_exists($progFile)) {
    echo json_encode([
        "stage" => "idle",
        "current" => 0,
        "total" => 1,
        "percent" => 0,
        "message" => "Waiting",
        "eta" => ""
    ]);
    exit;
}

$data = json_decode(@file_get_contents($progFile), true);
if (!is_array($data)) {
    echo json_encode([
        "stage" => "error",
        "current" => 0,
        "total" => 1,
        "percent" => 0,
        "message" => "Progress file unreadable",
        "eta" => ""
    ]);
    exit;
}

$current = isset($data["current"]) ? (int)$data["current"] : 0;
$total   = isset($data["total"]) ? (int)$data["total"] : 1;
$percent = ($total > 0) ? (int)round(($current / $total) * 100) : 0;

$data["current"] = $current;
$data["total"]   = $total;
$data["percent"] = max(0, min(100, $percent));
$data["stage"]   = $data["stage"] ?? "mosaic";
$data["message"] = $data["message"] ?? "";
$data["eta"]     = $data["eta"] ?? "";

echo json_encode($data);
