<?php
// 1. Start the "net" to catch any raw PHP errors/warnings
ob_start();

require __DIR__ . '/src/Rcon.php';
use Thedudeguy\Rcon;

// --- SERVER SETTINGS ---
$host = 'bitemc.xyz'; 
$port = 50019; 
$password = 'bitebooneydev67'; 
$timeout = 3; 

$username = $_POST['username'] ?? '';
$final_response = []; // We will store our JSON response here

if (empty($username)) {
    $final_response = ["status" => "error", "message" => "No username provided."];
} else {
    try {
        $rcon = new Rcon($host, $port, $password, $timeout);
        
        if ($rcon->connect()) {
            $response = $rcon->sendCommand("api-socialclaim " . $username);
            
            if (strpos($response, '[API-FAIL]') !== false) {
                $final_response = ["status" => "error", "message" => $response];
            } else {
                $final_response = ["status" => "success", "message" => "Head to the server crates to claim your loot!"];
            }
        } else {
            // If it fails, we set the response instead of crashing!
            $final_response = ["status" => "error", "message" => "[API-FAIL] Server connection refused. (Port blocked or offline)"];
        }
    } catch (Exception $e) {
        $final_response = ["status" => "error", "message" => "[API-FAIL] RCON Error: " . $e->getMessage()];
    }
}

// 2. Empty the net (throw away any PHP warnings that happened above)
ob_end_clean();

// 3. Securely send ONLY our clean JSON back to the website
header('Content-Type: application/json');
echo json_encode($final_response);
?>
