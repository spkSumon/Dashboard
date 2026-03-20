<?php
namespace App\Middleware;

use App\Helpers\Request;
use App\Helpers\Response;

/**
 * POC-auth: simpele token check.
 * Token format in deze POC:
 *   base64(json).sha256(secret)
 *
 * Dit is GEEN echte JWT, maar voldoende als POC.
 * Later kan je dit vervangen door een echte JWT lib of sessions.
 */
class Auth {
    public static function requireAuth(string $secret): array {
        $token = Request::bearerToken();
        if (!$token || strpos($token, '.') === false) {
            Response::error('Geen token', 401);
        }

        [$payloadB64, $sig] = explode('.', $token, 2);
        $expected = hash('sha256', $secret);

        if (!hash_equals($expected, $sig)) {
            Response::error('Ongeldig token', 401);
        }

        $json = base64_decode($payloadB64, true);
        $data = json_decode($json ?: '', true);
        if (!is_array($data)) {
            Response::error('Ongeldig token payload', 401);
        }
        return $data;
    }
}
