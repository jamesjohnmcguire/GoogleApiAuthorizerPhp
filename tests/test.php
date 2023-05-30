<?php

include_once '../vendor/autoload.php';
require_once '../GoogleAuthorization.php';

function TestDiscover()
{
	$client = GoogleAuthorization::Authorize(
		Mode::Discover,
		'',
		'credentials.json',
		'tokens.json',
		'Google Drive API File Uploader',
		['https://www.googleapis.com/auth/drive'],
		'http://localhost:8000/test.php');

	if ($client != null)
	{
		echo 'Client seems valid' . PHP_EOL;
	}
}

function TestOauth()
{
	$client = null;

	echo 'Testing OAutho...' . PHP_EOL;

	if (PHP_SAPI === 'cli')
	{
		echo 'WARNING: OAuth redirecting only works on the web' . PHP_EOL;
	}
	else
	{
		$client = GoogleAuthorization::Authorize(
			Mode::OAuth,
			'',
			'credentials.json',
			'tokens.json',
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			'http://localhost:8000/test.php');
	}

	if ($client !== null)
	{
		echo 'Client seems valid' . PHP_EOL;
	}
}

function TestRawRequestUser()
{
	$client = new Google_Client();

	$client->setAccessType('offline');
	$client->setApplicationName('Google Drive API File Uploader');
	$client->setPrompt('select_account consent');

	$client->addScope("https://www.googleapis.com/auth/drive");

	$credentialFile = __DIR__ . '/credentials.json';

	$client->setAuthConfig($credentialFile);

	$authorizationUrl = $client->createAuthUrl();

	echo 'Open the following link in your browser:' . PHP_EOL;
	echo $authorizationUrl . PHP_EOL;
	echo 'Enter verification code: ';

	$authorizationCode = fgets(STDIN);
	$$authorizationCode = trim($authorizationCode);
	echo $authorizationCode . PHP_EOL;

	$accessToken = $client->fetchAccessTokenWithAuthCode($authorizationCode);
	echo "ACCESS TOKEN: " . PHP_EOL;
	print_r($accessToken);
	echo PHP_EOL;

	if (array_key_exists('error', $accessToken))
	{
		echo "ERROR:" . PHP_EOL;
	}
	else
	{
		$client->setAccessToken($accessToken);
		
		$json =  json_encode($accessToken);
		$credentialsFile = 'cretentials_new.json';
		echo "Saving to file: " . $credentialsFile . PHP_EOL;
		file_put_contents($credentialsFile, $json);
	}
}

function TestRequestUser()
{
	GoogleAuthorization::Authorize(
		Mode::Request,
		'',
		'credentials.json',
		'tokens.json',
		'Google Drive API File Uploader',
		['https://www.googleapis.com/auth/drive']);
}

function TestServiceAccount()
{
	$client = GoogleAuthorization::Authorize(
		Mode::Token,
		'',
		'',
		'',
		'Google Drive API File Uploader',
		['https://www.googleapis.com/auth/drive']);

	if ($client != null)
	{
		echo 'Client seems valid' . PHP_EOL;
	}
}

function TestTokens()
{
	echo 'Testing Tokens...' . PHP_EOL;

	$client = GoogleAuthorization::Authorize(
		Mode::Token,
		'',
		'credentials.json',
		'tokens.json',
		'Google Drive API File Uploader',
		['https://www.googleapis.com/auth/drive']);

	if ($client != null)
	{
		echo 'Client seems valid' . PHP_EOL;
	}
}

if (PHP_SAPI == 'cli')
{
	if (!empty($argv[1]))
	{
		$command = $argv[1];
	}

	if (!empty($argv[2]))
	{
		$server = $argv[2];
	}

	if (!empty($argv[3]))
	{
		$role = $argv[3];
	}
}
else
{
	if ((!empty($_GET)) && (!empty($_GET['command'])))
	{
		$command = $_GET['command'];
	}

	if ((!empty($_GET)) && (!empty($_GET['server'])))
	{
		$server = $_GET['server'];
	}

	if ((!empty($_GET)) && (!empty($_GET['role'])))
	{
		$role = $_GET['role'];
	}
}

TestDiscover();
TestRequestUser();
TestTokens();
TestOauth();
