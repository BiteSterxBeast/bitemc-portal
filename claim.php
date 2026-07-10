<?php
// Force strict JSON output and hide raw HTML errors
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// ==========================================
// 🛠️ HYPER-DIAGNOSTIC RCON LIBRARY 
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
        
        // 1. NETWORK/SOCKET ERRORS (IP or Port issues)
        if (!$this->socket) {
            if ($errno === 111 || strpos(strtolower($errstr), 'refused') !== false) {
                throw new Exception("CONNECTION REFUSED: Port {$this->port} is closed. The server is offline, the port is not port-forwarded, or rcon.ip is not 0.0.0.0.");
            } elseif ($errno === 110 || strpos(strtolower($errstr), 'timed out') !== false) {
                throw new Exception("CONNECTION TIMED OUT: The IP Address ({$this->host}) is unreachable. Check if the IP is correct and not blocked by a proxy/DDoS protection.");
            } else {
                throw new Exception("NETWORK ERROR [{$errno}]: {$errstr}");
            }
        }
        
        stream_set_timeout($this->socket, 3, 0);
        return $this->authorize();
    }

    public function disconnect() {
        if ($this->socket) { @fclose($this->socket); }
    }

    public function isConnected() { return $this->authorized; }

    public function sendCommand($command) {
        if (!$this->isConnected()) throw new Exception("EXECUTION FAILED: Cannot send command, RCON is not authorized.");
        
        $this->writePacket(self::PACKET_COMMAND, self::SERVERDATA_EXECCOMMAND, $command);
        $response_packet = $this->readPacket();
        
        if (isset($response_packet['id']) && $response_packet['id'] == self::PACKET_COMMAND) {
            if ($response_packet['type'] == self::SERVERDATA_RESPONSE_VALUE) {
                return $response_packet['body'];
            }
        }
        
        throw new Exception("COMMAND TIMEOUT: The command was sent, but the server took too long to reply or returned invalid data.");
    }

    private function authorize() {
        $this->writePacket(self::PACKET_AUTHORIZE, self::SERVERDATA_AUTH, $this->password);
        $response_packet = $this->readPacket();

        // Some servers send an empty packet before the actual auth packet, skip it.
        if (isset($response_packet['type']) && $response_packet['type'] == self::SERVERDATA_RESPONSE_VALUE) {
            $response_packet = $this->readPacket();
        }

        // 2. DROPPED CONNECTION ERRORS
        if (empty($response_packet) || $response_packet['id'] === -999) {
            throw new Exception("CONNECTION DROPPED: Reached the server perfectly, but it instantly hung up. A plugin is stealing Port {$this->port}, or a Proxy is blocking the data.");
        }

        // 3. PASSWORD ERRORS
        if ($response_packet['id'] === -1) {
            throw new Exception("INVALID PASSWORD: Reached the server, but the rcon.password provided is incorrect!");
        }

        // 4. SUCCESS
        if ($response_packet['type'] == self::SERVERDATA_AUTH_RESPONSE && $response_packet['id'] == self::PACKET_AUTHORIZE) {
            $this->authorized = true;
            return true;
        }

        throw new Exception("UNKNOWN AUTH ERROR: Server replied with corrupted or unrecognized RCON data.");
    }

    private function writePacket($packetId, $packetType, $packetBody) {
        $packet = pack('VV', $packetId, $packetType);
        $packet = $packet . $packetBody . "\x00\x00";
        $packet_size = strlen($packet);
        $packet = pack('V', $packet_size) . $packet;
        $write_status = @fwrite($this->socket, $packet, strlen($packet));
        
        if ($write_status === false) {
            throw new Exception("WRITE FAILED: Render lost connection to the Minecraft server while trying to send data.");
        }
    }

    private function readPacket() {
        $size_data = @fread($this->socket, 4);
        if (!$size_data || strlen($size_data) < 4) {
            return ['id' => -999, 'type' => -999, 'body' => ''];
        }
        $size_pack = unpack('V1size', $size_data);
        $size = $size_pack['size'] ?? 0;
        if ($size <= 0) return ['id' => -999, 'type' => -999, 'body' => ''];
        
        $packet_data = @fread($this->socket, $size);
        if (!$packet_data) return ['id' => -999, 'type' => -999, 'body' => ''];
        
        $packet_pack = unpack('V1id/V1type/a*body', $packet_data);
        return $packet_pack ?: ['id' => -999, 'type' => -999, 'body' => ''];
    }
}

// ==========================================
// 🚀 MAIN API LOGIC
// ==========================================

// -> EDIT THESE SETTINGS AS NEEDED <-
$host = 'bitemc.xyz'; // Switch to raw IP if using a domain proxy
$port = 50004; 
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
    }
} catch (Throwable $e) { 
    // This catches every single hyper-specific error we programmed above
    echo json_encode(["status" => "error", "message" => "[API-FAIL] " . $e->getMessage()]);
}
?>
