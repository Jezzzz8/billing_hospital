<?php

function patient_portal_check_rate_limit(string $ipAddress): array
{
    $socket = @stream_socket_client('tcp://127.0.0.1:8081', $errno, $error, 1);
    if ($socket === false) {
        error_log(sprintf('[patient_portal_rate_limit] Rate limiter unavailable (%d): %s', $errno, $error));
        return ['allowed' => false, 'retry_after' => 60, 'unavailable' => true];
    }

    stream_set_timeout($socket, 1);
    $request = json_encode(['ip' => $ipAddress], JSON_THROW_ON_ERROR) . "\n";
    $written = fwrite($socket, $request);
    if ($written !== strlen($request)) {
        fclose($socket);
        error_log('[patient_portal_rate_limit] Could not send the rate-limit request.');
        return ['allowed' => false, 'retry_after' => 60, 'unavailable' => true];
    }

    $response = fgets($socket, 1024);
    fclose($socket);
    if ($response === false) {
        error_log('[patient_portal_rate_limit] Rate limiter returned no response.');
        return ['allowed' => false, 'retry_after' => 60, 'unavailable' => true];
    }

    $result = json_decode($response, true);
    if (!is_array($result) || !array_key_exists('allowed', $result)) {
        error_log('[patient_portal_rate_limit] Rate limiter returned an invalid response.');
        return ['allowed' => false, 'retry_after' => 60, 'unavailable' => true];
    }

    return $result;
}
