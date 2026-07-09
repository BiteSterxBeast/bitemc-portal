<?php
require __DIR__ . '/src/Rcon.php';
use Thedudeguy\Rcon;

header('Content-Type: application/json');

$host = 'bitemc.xyz'; 
$port = 25575; 
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
        echo json_encode(["status" => "error", "message" => "[API-FAIL] Server connection refused."]);
    }
} catch (Exception $e) {
    // This stops PHP from crashing and sends the EXACT error back to your website!
    echo json_encode(["status" => "error", "message" => "[API-FAIL] RCON Error: " . $e->getMessage()]);
}
?>
