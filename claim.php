<?php
// Force strict JSON output and hide raw HTML errors
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// ==========================================
// 🛠️ BULLETPROOF RCON LIBRARY 
// ==========================================
class Rcon {
    private $host;
    private $port;
    private $password;
    private $timeout;
    private $socket;
    private $authorized = false;
    private $lastResponse = '';

    const PACKET_AUTHORIZE = 5;
    const PACKET_COMMAND = 6;
    const SERVERDATA_AUTH = 3;
    const SERVERDATA_AUTH_RESPONSE = 2;
    const SERVERDATA_EXECCOMMAND = 2;
    const SERVERDATA_RESPONSE_VALUE = 0;

    public function __construct($host, $port, $password, $timeout) {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->timeout = $timeout;
    }

    public function connect() {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        if (!$this->socket) return false;
        stream_set_timeout($this->socket, 3, 0);
        return $this->authorize();
    }

    public function disconnect() {
        if ($this->socket) { fclose($this->socket); }
    }

    public function isConnected() { return $this->authorized; }

    public function sendCommand($command) {
        if (!$this->isConnected()) return false;
        $this->writePacket(self::PACKET_COMMAND, self::SERVERDATA_EXECCOMMAND, $command);
        $response_packet = $this->readPacket();
        if (isset($response_packet['id']) && $response_packet['id'] == self::PACKET_COMMAND) {
            if ($response_packet['type'] == self::SERVERDATA_RESPONSE_VALUE) {
                $this->lastResponse = $response_packet['body'];
                return $response_packet['body'];
            }
        }
        return false;
    }

    private function authorize() {
        $this->writePacket(self::PACKET_AUTHORIZE, self::SERVERDATA_AUTH, $this->password);
        $response_packet = $this->readPacket();
        if (isset($response_packet['type']) && $response_packet['type'] == self::SERVERDATA_AUTH_RESPONSE) {
            if ($response_packet['id'] == self::PACKET_AUTHORIZE) {
                $this->authorized = true;
                return true;
            }
        }
        $this->disconnect();
        return false;
    }

    private function writePacket($packetId, $packetType, $packetBody) {
        $packet = pack('VV', $packetId, $packetType);
        $packet = $packet . $packetBody . "\x00\x00";
        $packet_size = strlen($packet);
        $packet = pack('V', $packet_size) . $packet;
        @fwrite($this->socket, $packet, strlen($packet));
    }

    // THE PATCH: Safe reading that prevents Fatal Errors
    private function readPacket() {
        $size_data = @fread($this->socket, 4);
        if (!$size_data || strlen($size_data) < 4) {
            return ['id' => -1, 'type' => -1, 'body' => ''];
        }
        $size_pack = unpack('V1size', $size_data);
        $size = $size_pack['size'] ?? 0;
        if ($size <= 0) return ['id' => -1, 'type' => -1, 'body' => ''];
        
        $packet_data = @fread($this->socket, $size);
        if (!$packet_data) return ['id' => -1, 'type' => -1, 'body' => ''];
        
        $packet_pack = unpack('V1id/V1type/a*body', $packet_data);
        return $packet_pack ?: ['id' => -1, 'type' => -1, 'body' => ''];
    }
}

// ==========================================
// 🚀 MAIN API LOGIC
// ==========================================
$host = '65.109.63.52'; 
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
    $connected = $rcon->connect();
    
    if ($connected) {
        $response = $rcon->sendCommand("api-socialclaim " . $username);
        
        if (strpos((string)$response, '[API-FAIL]') !== false) {
            echo json_encode(["status" => "error", "message" => $response]);
        } else {
            echo json_encode(["status" => "success", "message" => "Head to the server crates to claim your loot!"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "[API-FAIL] Minecraft server reached, but connection was dropped. Check RCON password and IP!"]);
    }
} catch (Throwable $e) { // Throwable catches the deepest PHP 8 Fatal Errors!
    echo json_encode(["status" => "error", "message" => "[API-FAIL] Script Error: " . $e->getMessage()]);
}
?>
