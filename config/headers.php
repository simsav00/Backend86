<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if(isset($_SERVER["HTTP_ORIGIN"]) && in_array($_SERVER["HTTP_ORIGIN"], ALLOWED_HTTP_ORIGINS)){
  header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
}
else{
  header("Access-Control-Allow-Origin: " . HTTP_ORIGIN_FALLBACK_ON_FAILURE);
}
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
  header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type");
  exit(0);
}