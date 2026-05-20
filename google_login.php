<?php
session_start();
require 'vendor/autoload.php';
require_once __DIR__ . '/env.php';

$client = new Google_Client();

$client->setClientId($_ENV['GOOGLE_CLIENT_ID'] ?? '');
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI'] ?? '');

$client->addScope("email");
$client->addScope("profile");

$client->setPrompt("select_account consent");
$client->setAccessType("online");

// CSRF protection
$state = bin2hex(random_bytes(32));
$_SESSION['oauth_state'] = $state;

$client->setState($state);

header('Location: ' . $client->createAuthUrl());
exit();
