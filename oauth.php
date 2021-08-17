<?php
// composer require lcobucci/jwt

require "vendor/autoload.php";

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

// 1. Create a Client Secret
$config = Configuration::forSymmetricSigner(
    Sha256::create(),
    InMemory::file(__DIR__ . '/private-key.pem')
);

$client_id = 'SEARCHADS.27478e71-3bb0-4588-998c-182e2b405577';
$team_id = 'SEARCHADS.27478e71-3bb0-4588-998c-182e2b405577';
$key_id = 'bacaebda-e219-41ee-a907-e2c25b24d1b2';

$audience = "https://appleid.apple.com";
$alg = "ES256";

$now   = new DateTimeImmutable();
$token = $config->builder()
                ->issuedBy($team_id)
                ->permittedFor($audience)
                ->issuedAt($now)
                ->relatedTo($client_id)
                ->expiresAt($now->modify('+180 days'))
                ->withHeader('alg', $alg)
                ->withHeader('kid', $key_id)
                ->getToken($config->signer(), $config->signingKey());

$client_secret = $token->toString();

echo $client_secret;

// 2. Request an Access Token
$postdata = http_build_query(
    array(
        'grant_type' => 'client_credentials',
        'scope' => 'searchadsorg',
        'client_id' => $client_id,
        'client_secret' => $client_secret
    )
);
$opts = array('http' =>
    array(
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                    "Host: appleid.apple.com\r\n",
        'content' => $postdata
    )
);
$context = stream_context_create($opts);
$contents = file_get_contents('https://appleid.apple.com/auth/oauth2/token', false, $context);
$results = json_decode($contents); 

echo $results->access_token;

// 3. Get User ACL

$opts = array('http' =>
    array(
        'method' => 'GET',
        'header' => "Authorization: Bearer ".$results->access_token
    )
);
$context = stream_context_create($opts);
$contents = file_get_contents('https://api.searchads.apple.com/api/v4/acls', false, $context);

echo $contents;
