<?php

require "vendor/autoload.php";

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

// 1. Create a Client Secret
$config = Configuration::forSymmetricSigner(
    Sha256::create(),
    InMemory::file(__DIR__ . '/private-key.pem')
);

$client_id = 'SEARCHADS.c99b2f21-5964-4869-847c-ljh799004501';
$team_id = 'SEARCHADS.c99b2f21-5964-4869-847c-ljh799004501';
$key_id = '285e95c1-d514-4522-b6e3-ljh799004501';

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

echo "clientSecret 建议保存，有效期可设置最长 180 天\n";
echo $client_secret;
echo "\n";

// 2. Request an Access Token
$postdata = http_build_query(
    array(
        'grant_type' => 'client_credentials',
        'scope' => 'searchadsorg',
        'agency' => 'ljh',
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

echo "access_token 有效期1个小时\n";
echo $results->access_token;
echo "\n";

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
echo "\n";
