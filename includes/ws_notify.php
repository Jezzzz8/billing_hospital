<?php

function ws_broadcast(
    string $event,
    array $payload = [],
    array $roles = [],
    ?int $departmentId = null,
    array $userIds = []
): void
{
    $senderUserId = (int)($_SESSION['user']['user_id'] ?? 0);
    if ($senderUserId > 0) {
        $payload['sender_user_id'] = $senderUserId;
    }

    $socket = @stream_socket_client('tcp://127.0.0.1:8080', $errno, $error, 1);
    if ($socket === false) {
        error_log(sprintf('[ws_broadcast] Could not connect to the WebSocket server (%d): %s', $errno, $error));
        return;
    }

    stream_set_timeout($socket, 2);

    try {
        $key = base64_encode(random_bytes(16));
        $handshake = "GET / HTTP/1.1\r\n"
            . "Host: 127.0.0.1:8080\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Key: {$key}\r\n"
            . "Sec-WebSocket-Version: 13\r\n"
            . "Origin: http://localhost\r\n\r\n";
        $write = static function (string $data) use ($socket): void {
            $length = strlen($data);
            $offset = 0;
            while ($offset < $length) {
                $written = fwrite($socket, substr($data, $offset));
                if ($written === false || $written === 0) {
                    throw new RuntimeException('Could not write to the WebSocket connection.');
                }
                $offset += $written;
            }
        };

        $write($handshake);

        $response = '';
        while (!str_contains($response, "\r\n\r\n") && strlen($response) < 16384) {
            $line = fgets($socket, 2048);
            if ($line === false) {
                throw new RuntimeException('The WebSocket server did not complete its handshake.');
            }
            $response .= $line;
        }

        $expectedAccept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        if (
            !preg_match('/^HTTP\/1\.[01] 101\b/m', $response)
            || !preg_match('/^Sec-WebSocket-Accept:\s*(.+)\r?$/mi', $response, $matches)
            || !hash_equals($expectedAccept, trim($matches[1]))
        ) {
            throw new RuntimeException('The WebSocket server returned an invalid handshake.');
        }

        $message = json_encode([
            'action'        => 'broadcast',
            'event'         => $event,
            'payload'       => $payload,
            'roles'         => $roles,
            'department_id' => $departmentId,
            'user_ids'      => array_values(array_unique(array_filter(array_map('intval', $userIds), static fn(int $id): bool => $id > 0))),
        ], JSON_THROW_ON_ERROR);
        $messageLength = strlen($message);
        if ($messageLength > 65535) {
            throw new RuntimeException('The WebSocket broadcast exceeds the supported message size.');
        }

        $mask = random_bytes(4);
        $maskedMessage = '';
        for ($index = 0; $index < $messageLength; $index++) {
            $maskedMessage .= $message[$index] ^ $mask[$index % 4];
        }
        $lengthHeader = $messageLength < 126
            ? chr(0x80 | $messageLength)
            : chr(0x80 | 126) . pack('n', $messageLength);
        $write("\x81" . $lengthHeader . $mask . $maskedMessage);
    } catch (Throwable $e) {
        error_log('[ws_broadcast] ' . $e->getMessage());
    } finally {
        fclose($socket);
    }
}
