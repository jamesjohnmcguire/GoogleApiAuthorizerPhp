<?php
declare(strict_types=1);

$root = dirname(__DIR__, 1);

include_once $root . '/SourceCode/vendor/autoload.php';
require_once $root . '/SourceCode/GoogleAuthorization.php';

use GoogleApiAuthorization\GoogleAuthorization;
use GoogleApiAuthorization\Mode;

function TestDiscover()
{
	$client = GoogleAuthorization::authorize(
		Mode::Discover,
		'',
		'credentials.json',
		'tokens.json',
		'Google Drive API File Uploader',
		['https://www.googleapis.com/auth/drive'],
		'http://localhost:8000/test.php');

	if ($client != null)
	{
		$service = new \Google_Service_Drive($client);
		$about = $service->about;

		$options =
		[
			'fields' => 'storageQuota',
			'prettyPrint' => true
		];

		$response = $about->get($options);
		if ($response !== null)
		{
			echo 'Client seems valid' . PHP_EOL;
		}
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
		$client = GoogleAuthorization::authorize(
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
		$service = new \Google_Service_Drive($client);
		$about = $service->about;

		$options =
		[
			'fields' => 'storageQuota',
			'prettyPrint' => true
		];

		$response = $about->get($options);
		if ($response !== null)
		{
			echo 'Client seems valid' . PHP_EOL;
		}
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

		$service = new \Google_Service_Drive($client);
		$about = $service->about;

		$options =
		[
			'fields' => 'storageQuota',
			'prettyPrint' => true
		];

		$response = $about->get($options);
		if ($response !== null)
		{
			echo 'Client seems valid' . PHP_EOL;
		}
	}
}

function TestRequestUser()
{
	$client = GoogleAuthorization::authorize(
		Mode::Request,
		'',
		'credentials.json',
		'tokens.json',
		'Google Drive API File Uploader',
		['https://www.googleapis.com/auth/drive']);

	if ($client != null)
	{
		$service = new \Google_Service_Drive($client);
		$about = $service->about;

		$options =
		[
			'fields' => 'storageQuota',
			'prettyPrint' => true
		];

		$response = $about->get($options);
		if ($response !== null)
		{
			echo 'Client seems valid' . PHP_EOL;
		}
	}
}

function TestServiceAccount($serviceAccountFilePath)
{
	$client = GoogleAuthorization::authorize(
		Mode::ServiceAccount,
		'',
		null,
		'',
		'Google Drive API File Uploader',
		['https://www.googleapis.com/auth/drive']);

	if ($client != null)
	{
		$service = new \Google_Service_Drive($client);
		$about = $service->about;

		$options =
		[
			'fields' => 'storageQuota',
			'prettyPrint' => true
		];

		$response = $about->get($options);
		if ($response !== null)
		{
			echo 'Client seems valid' . PHP_EOL;
		}
	}
}

function TestTokens($credentialsFilePath, $tokensFilePath)
{
	echo 'Testing Tokens...' . PHP_EOL;

	$client = GoogleAuthorization::authorize(
		Mode::Token,
		$credentialsFilePath,
		null,
		null,
		'Google Drive API File Uploader',
		['https://www.googleapis.com/auth/drive']);

	if ($client != null)
	{
		$service = new \Google_Service_Drive($client);
		$about = $service->about;

		$options =
		[
			'fields' => 'storageQuota',
			'prettyPrint' => true
		];

		$response = $about->get($options);
		if ($response !== null)
		{
			echo 'Client seems valid' . PHP_EOL;
		}
	}
}

$command = null;
$credentialsFilePath = null;
$serviceAccountFilePath = null;
$tokensFilePath = null;

if (PHP_SAPI == 'cli')
{
	if (!empty($argv[1]))
	{
		$command = $argv[1];
	}

	if (!empty($argv[2]))
	{
		$credentialsFilePath = $argv[2];
	}

	if (!empty($argv[3]))
	{
		$serviceAccountFilePath = $argv[3];
	}

	if (!empty($argv[4]))
	{
		$tokensFilePath = $argv[4];
	}
}
else
{
	if ((!empty($_GET)) && (!empty($_GET['command'])))
	{
		$command = $_GET['command'];
	}

	if ((!empty($_GET)) && (!empty($_GET['credentials'])))
	{
		$credentialsFilePath = $_GET['credentials'];
	}

	if ((!empty($_GET)) && (!empty($_GET['service'])))
	{
		$serviceAccountFilePath = $_GET['service'];
	}

	if ((!empty($_GET)) && (!empty($_GET['tokens'])))
	{
		$tokensFilePath = $_GET['tokens'];
	}
}

switch($command)
{
	case 'discover':
		TestDiscover();
		echo PHP_EOL . 'TestDiscover finished' . PHP_EOL;
		break;
	case 'oauth':
		TestOauth();
		break;
	case 'request':
		TestRequestUser();
		break;
	case 'service':
		TestServiceAccount($serviceAccountFilePath);
		break;
	case 'tokens':
		TestTokens($credentialsFilePath, $tokensFilePath);
		break;
	default:
		break;
}
