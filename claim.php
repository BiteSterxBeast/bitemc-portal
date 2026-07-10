<?php
// 1. Force PHP to hide raw text warnings so it doesn't break our JSON!
error_reporting(0);
ini_set('display_errors', 0);

require __DIR__ . '/src/Rcon.php';
use Thedudeguy\Rcon;

header('Content-Type: application/json');

// --- SERVER SETTINGS ---
$host = 'bitemc.xyz'; 
$port = 50019; // 2. Updated to your new port!
$password = 'bitebooneydev67'; 
$timeout = 3; 

$username = $_POST['username'] ?? '';

if (empty($username)) {
    echo json_encode(["status" => "error", "message" => "No username provided."]);
    exit;
}

try {
    $rcon = new Rcon($host, $port, $password, $timeout);
    
    if ($rcon->connect()) {
        $response = $rcon->sendCommand("api-socialclaim " . $username);
        
        if (strpos($response, '[API-FAIL]') !== false) {
            echo json_encode(["status" => "error", "message" => $response]);
        } else {
            echo json_encode(["status" => "success", "message" => "Head to the server crates to claim your loot!"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "[API-FAIL] Server connection refused. (Port blocked or offline)"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "[API-FAIL] RCON Error: Connection Timed Out."]);
}
?>
