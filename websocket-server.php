<?php
require __DIR__ . '/vendor/autoload.php';

use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\ConnectionInterface as SocketConnectionInterface;
use React\Socket\SocketServer;

class BillingNotifier implements MessageComponentInterface
{
    protected SplObjectStorage $clients;

    public function __construct()
    {
        $this->clients = new SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        echo "[+] Connection {$conn->resourceId} opened (total: {$this->clients->count()})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $data = json_decode($msg, true);
        if (!is_array($data)) {
            return;
        }

        if (($data['action'] ?? '') === 'broadcast') {
            $roles        = $data['roles'] ?? [];
            $departmentId = $data['department_id'] ?? null;
            $userIds      = $data['user_ids'] ?? [];

            $frame = json_encode([
                'type'        => 'event',
                'event'       => $data['event'] ?? 'update',
                'payload'     => $data['payload'] ?? [],
                'roles'       => $roles,
                'user_ids'    => $userIds,
                'department'  => $departmentId,
                'timestamp'   => time(),
            ]);

            foreach ($this->clients as $client) {
                $client->send($frame);
            }
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
        echo "[-] Connection {$conn->resourceId} closed (total: {$this->clients->count()})\n";
    }

    public function onError(ConnectionInterface $conn, Exception $e): void
    {
        echo "[!] Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(new WsServer(new BillingNotifier())),
    8080
);

$loginAttempts = [];
$rateLimiter = new SocketServer('127.0.0.1:8081');
$rateLimiter->on('connection', static function (SocketConnectionInterface $connection) use (&$loginAttempts): void {
    $buffer = '';
    $connection->on('data', static function (string $chunk) use ($connection, &$buffer, &$loginAttempts): void {
        $buffer .= $chunk;
        if (strlen($buffer) > 2048) {
            $connection->end("{\"allowed\":false,\"retry_after\":60}\n");
            return;
        }

        $lineEnd = strpos($buffer, "\n");
        if ($lineEnd === false) {
            return;
        }

        $request = json_decode(substr($buffer, 0, $lineEnd), true);
        $ipAddress = is_array($request) ? (string)($request['ip'] ?? '') : '';
        if (filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            $connection->end("{\"allowed\":false,\"retry_after\":60}\n");
            return;
        }

        $now = time();
        foreach ($loginAttempts as $ip => $attempts) {
            if ($now - $attempts['started_at'] >= 900) {
                unset($loginAttempts[$ip]);
            }
        }

        if (!isset($loginAttempts[$ipAddress])) {
            if (count($loginAttempts) >= 10000) {
                $connection->end("{\"allowed\":false,\"retry_after\":900}\n");
                return;
            }
            $loginAttempts[$ipAddress] = ['started_at' => $now, 'count' => 0];
        }

        $attempts = &$loginAttempts[$ipAddress];
        if ($attempts['count'] >= 10) {
            $retryAfter = max(1, 900 - ($now - $attempts['started_at']));
            $connection->end(json_encode(['allowed' => false, 'retry_after' => $retryAfter]) . "\n");
            unset($attempts);
            return;
        }

        $attempts['count']++;
        $connection->end("{\"allowed\":true,\"retry_after\":0}\n");
        unset($attempts);
    });
});
$rateLimiter->on('error', static function (Throwable $error): void {
    error_log('[patient_portal_rate_limit] ' . $error->getMessage());
});

echo "WebSocket server listening on ws://0.0.0.0:8080\n";
echo "Patient portal in-memory rate limiter listening on 127.0.0.1:8081\n";
$server->run();