<?php
// 1. Force JSON output for everything, even crashes
header('Content-Type: application/json');

// 2. The Safety Net: Catch absolute fatal errors and turn them into readable text!
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo json_encode([
            "status" => "error", 
            "message" => "PHP CRASH: " . $error['message'] . " in " . basename($error['file']) . " on line " . $error['line']
        ]);
    }
});

// Hide default HTML errors so they don't break our JSON
error_reporting(0);
ini_set('display_errors', 0);

// 3. Explicitly check if the file exists before crashing!
$rconFile = __DIR__ . '/src/Rcon.php';
if (!file_exists($rconFile)) {
    echo json_encode(["status" => "error", "message" => "Missing File: Cannot find src/Rcon.php in GitHub!"]);
    exit;
}

require $rconFile;
use Thedudeguy\Rcon;

// --- SERVER SETTINGS ---
$host = 'bitemc.xyz'; 
$port = 50019; 
$password = 'bitebooneydev67'; 
$timeout = 3; 

$username = $_POST['username'] ?? '';

if (empty($username)) {
    echo json_encode(["status" => "error", "message" => "No username provided."]);
    exit;
}

try {
    $rcon = new Rcon($host, $port, $password, $timeout);
    $connected = @$rcon->connect();
    
    if ($connected) {
        $response = @$rcon->sendCommand("api-socialclaim " . $username);
        
        if (strpos($response, '[API-FAIL]') !== false) {
            echo json_encode(["status" => "error", "message" => $response]);
        } else {
            echo json_encode(["status" => "success", "message" => "Head to the server crates to claim your loot!"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "[API-FAIL] Server connection refused. (Check rcon.ip=0.0.0.0)"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "[API-FAIL] RCON Error: " . $e->getMessage()]);
}
?>
