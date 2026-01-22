<?php
header("Content-Type: application/json");

$progFile = __DIR__ . "/tmp/progress.json";

if (!file_exists($progFile)) {
    echo json_encode(["ok" => false, "message" => "No job in progress"]);
    exit;
}

$data = json_decode(@file_get_contents($progFile), true);
if (!is_array($data)) $data = [];

$data["cancel"] = true;
$data["stage"] = $data["stage"] ?? "mosaic";
$data["message"] = "Cancelling…";

file_put_contents($progFile, json_encode($data), LOCK_EX);

echo json_encode(["ok" => true, "message" => "Cancel requested"]);
