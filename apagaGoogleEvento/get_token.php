<?php
require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Google\Auth\JWT as GoogleJWT;

function getJWT($serviceAccountPath) {
    $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
    $now = time();
    
    $payload = [
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/calendar',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now,
    ];

    return JWT::encode($payload, $serviceAccount['private_key'], 'RS256');
}

// Caminho para o arquivo JSON da conta de serviço
$serviceAccountPath = 'nomadic-tine-316917-bd61656c0914.json';
$jwt = getJWT($serviceAccountPath);

// Trocar o JWT por um token de acesso
$response = json_decode(file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
    'http' => [
        'header' => [
            'Content-Type: application/x-www-form-urlencoded',
        ],
        'method' => 'POST',
        'content' => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]),
    ],
])), true);

echo 'Access Token: ' . $response['access_token'];
