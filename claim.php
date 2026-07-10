<?php
// 1. Absolute Silence: Force PHP to never output HTML warnings
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require __DIR__ . '/src/Rcon.php';
use Thedudeguy\Rcon;

// --- SERVER SETTINGS ---
$host = 'bitemc.xyz'; 
$port = 50019; 
$password = 'bitebooneydev67'; 
$timeout = 3; 

$username = $_POST['username'] ?? '';
$final_response = []; 

if (empty($username)) {
    echo json_encode(["status" => "error", "message" => "No username provided."]);
    exit;
}

try {
    $rcon = new Rcon($host, $port, $password, $timeout);
    
    // 2. Temporarily trap any connection warnings in a black hole
    set_error_handler(function() { return true; });
    $connected = $rcon->connect();
    restore_error_handler(); // Release the trap
    
    if ($connected) {
        $response = $rcon->sendCommand("api-socialclaim " . $username);
        
        if (strpos($response, '[API-FAIL]') !== false) {
            $final_response = ["status" => "error", "message" => $response];
        } else {
            $final_response = ["status" => "success", "message" => "Head to the server crates to claim your loot!"];
        }
    } else {
        $final_response = ["status" => "error", "message" => "[API-FAIL] Connection refused by Minecraft Server."];
    }
} catch (Exception $e) {
    $final_response = ["status" => "error", "message" => "[API-FAIL] RCON Connection Timed Out."];
}

// 3. Send ONLY the pure JSON back
echo json_encode($final_response);
?>
