<?php
// src/Helper/JwtHelper.php

declare(strict_types=1);

namespace Educarama\Helper;

final class JwtHelper
{
    /** La firma del token. En producción debería estar en config.php. */
    private const SECRET = 'L@_Cl@v3_S3cr3t@_D3_3duc@r@m@_M0b1l3';

    public static function generar(int $idUsuario): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'id_usuario' => $idUsuario,
            'iat' => time(),
            'exp' => time() + (86400 * 30) // Expira en 30 días
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::SECRET, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function extraerId(?string $tokenEnviado): ?int
    {
        if (empty($tokenEnviado)) {
            return null;
        }

        // El formato suele ser "Bearer <token>"
        $partes = explode(' ', $tokenEnviado);
        $token = count($partes) === 2 ? $partes[1] : $partes[0];

        $tks = explode('.', $token);
        if (count($tks) !== 3) {
            return null;
        }

        [$headb64, $bodyb64, $cryptob64] = $tks;

        $sig = base64_decode(str_replace(['-', '_'], ['+', '/'], $cryptob64));
        $hash = hash_hmac('sha256', $headb64 . "." . $bodyb64, self::SECRET, true);

        if (!hash_equals($sig, $hash)) {
            return null; // Firma inválida
        }

        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $bodyb64)), true);

        if (!isset($payload['id_usuario']) || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null; // Token expirado o malformado
        }

        return (int) $payload['id_usuario'];
    }

    /** 
     * Busca la cabecera 'Authorization' en la request actual (útil para la API).
     */
    public static function obtenerCabecera(): ?string
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
}