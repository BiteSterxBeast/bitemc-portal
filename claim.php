<?php
// Include the RCON file we just added to the src folder
require __DIR__ . '/src/Rcon.php';
use Thedudeguy\Rcon;

// Tell the website we are sending JSON data back
header('Content-Type: application/json');

// --- SERVER SETTINGS ---
$host = 'YOUR_SERVER_IP'; // Your SMP IP
$port = 25575; // Default RCON port
$password = 'YOUR_RCON_PASSWORD'; // Set this in server.properties
$timeout = 3; // Give up after 3 seconds if the server is offline

$rcon = new Rcon($host, $port, $password, $timeout);

// Safely grab the username sent by the HTML file
$username = $_POST['username'] ?? '';

// Attempt to connect and fire the command
if ($rcon->connect()) {
    $response = $rcon->sendCommand("api-socialclaim " . $username);
    
    // Check Skript's response
    if (strpos($response, '[API-FAIL]') !== false) {
        echo json_encode(["status" => "error", "message" => $response]);
    } else {
        echo json_encode(["status" => "success", "message" => "Head to the server crates to claim your loot!"]);
    }
} else {
    // If the Minecraft server is restarting or offline
    echo json_encode(["status" => "error", "message" => "[API-FAIL] Server is currently unreachable. Try again in a few minutes!"]);
}
?>
