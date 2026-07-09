<?php
// 1. Connect to Minecraft RCON
require __DIR__ . '/src/Rcon.php';
$rcon = new \xPaw\MinecraftRcon();
$rcon->Connect( 'bitemc.xyz', 25575, 'bitebooneydev67' );

// 2. Get the username from the HTML website
$username = $_POST['username'];

// 3. Run our Skript command!
$response = $rcon->Command( "api-socialclaim " . $username );

// 4. Read what Skript said and send it back to the HTML website
if (strpos($response, '[API-FAIL]') !== false) {
    echo json_encode(["status" => "error", "message" => $response]);
} else {
    echo json_encode(["status" => "success", "message" => "Head to the server crates to claim your loot!"]);
}
?>
