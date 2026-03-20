<?php
namespace App\Helpers;

/**
 * Helpers om input te lezen in vanilla PHP.
 */
class Request {
    /** JSON body -> associative array */
    public static function jsonBody(): array {
        $raw = file_get_contents('php://input');
        if (!$raw) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /** Bearer token uit Authorization header */
    public static function bearerToken(): ?string {
        // Probeer verschillende manieren om Authorization header te krijgen
        $h = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? null;

        // Fallback: getallheaders() voor Apache
        if (!$h && function_exists('getallheaders')) {
            $headers = getallheaders();
            $h = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        }

        if (!$h) return null;
        if (stripos($h, 'Bearer ') === 0) return trim(substr($h, 7));
        return null;
    }

    /**
     * Get query parameter from GET request
     *
     * @param string $key Parameter name
     * @param mixed $default Default value if not found
     * @return mixed Parameter value or default
     */
    public static function get(string $key, $default = null) {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get all query parameters
     *
     * @return array All GET parameters
     */
    public static function allGet(): array {
        return $_GET;
    }
}
