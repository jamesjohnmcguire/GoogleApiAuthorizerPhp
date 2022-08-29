<?php

declare(strict_types=1);

namespace DigitalZenWorks\GoogleApiAuthorization\Tests;

$root = dirname(__DIR__, 1);

require_once $root . '/SourceCode/vendor/autoload.php';
require_once $root . '/SourceCode/GoogleApiAuthorization.php';

use DigitalZenWorks\GoogleApiAuthorization\Authorizer;
use DigitalZenWorks\GoogleApiAuthorization\Mode;
use PHPUnit\Framework\TestCase;

final class UnitTests extends TestCase
{
	protected ?string $credentialsFilePath = 'credentials.json';
	protected ?string $serviceAccountFilePath = 'ServiceAccount.json';
	protected ?string $tokensFilePath = 'tokens.json';

	public function setUp() : void
	{
	}

	public static function setUpBeforeClass() : void
	{
		global $argv;

		if (!empty($argv[5]))
		{
			self::$credentialsFilePath = $argv[5];
		}
	}

	public function tearDown() : void
	{
	}

	public function testSantityCheck()
	{
		$result = true;

		$this->assertTrue($result);
	}

	public function testDiscoverSuccess()
	{
		$client = Authorizer::authorize(
			Mode::Discover,
			$this->credentialsFilePath,
			$this->serviceAccountFilePath,
			$this->tokensFilePath,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			'http://localhost:8000/test.php');

		$this->assertNotNull($client);

		$service = new \Google_Service_Drive($client);
		$about = $service->about;

		$options =
		[
			'fields' => 'storageQuota',
			'prettyPrint' => true
		];

		$response = $about->get($options);
		$this->assertNotNull($response);

		$this->assertInstanceOf('Google\Service\Drive\About', $response);
	}

	public function testObjectDiscoveryTest()
	{
		$authorizer = new Authorizer('Google Drive API File Uploader',
		['https://www.googleapis.com/auth/drive'],
		['promptUser' => false, 'showWarnings' => false]);

		$client = $authorizer->apiAuthorize(
			Mode::Discover,
			$this->credentialsFilePath,
			$this->serviceAccountFilePath,
			$this->tokensFilePath,
			'http://localhost:8000/test.php');
	
		$this->assertNotNull($client);

		$service = new \Google_Service_Drive($client);
		$about = $service->about;

		$options =
		[
			'fields' => 'storageQuota',
			'prettyPrint' => true
		];

		$response = $about->get($options);
		$this->assertNotNull($response);

		$this->assertInstanceOf('Google\Service\Drive\About', $response);
	}

	public function testServiceAccountDirectSuccess()
	{
		$client = Authorizer::authorizeServiceAccount(
			$this->serviceAccountFilePath,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			false);
	
		$this->assertNotNull($client);

		$service = new \Google_Service_Drive($client);
		$about = $service->about;

		$options =
		[
			'fields' => 'storageQuota',
			'prettyPrint' => true
		];

		$response = $about->get($options);
		$this->assertNotNull($response);

		$this->assertInstanceOf('Google\Service\Drive\About', $response);
	}

	public function testServiceAccountFailNoFileOrEnvironementVariable()
	{
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS';
		putenv($environmentVariable);

		$client = Authorizer::authorize(
			Mode::ServiceAccount,
			null,
			null,
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			null,
			['promptUser' => false, 'showWarnings' => false]);

		$this->assertNull($client);
	}

	public function testServiceAccountEnvironmentVariableSuccess()
	{
		$serviceAccountFilePath = realpath($this->serviceAccountFilePath);
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS=' .
			$serviceAccountFilePath;
		putenv($environmentVariable);

		$client = Authorizer::authorize(
			Mode::ServiceAccount,
			null,
			null,
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			null,
			['promptUser' => false, 'showWarnings' => false]);

		$this->assertNotNull($client);

		$service = new \Google_Service_Drive($client);
		$about = $service->about;

		$options =
		[
			'fields' => 'storageQuota',
			'prettyPrint' => true
		];

		$response = $about->get($options);
		$this->assertNotNull($response);

		$this->assertInstanceOf('Google\Service\Drive\About', $response);
	}

	public function testServiceAccountFileSuccess()
	{
		$client = Authorizer::authorize(
			Mode::ServiceAccount,
			null,
			$this->serviceAccountFilePath,
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			null,
			['promptUser' => false, 'showWarnings' => false]);

		$this->assertNotNull($client);

		$service = new \Google_Service_Drive($client);
		$about = $service->about;

		$options =
		[
			'fields' => 'storageQuota',
			'prettyPrint' => true
		];

		$response = $about->get($options);
		$this->assertNotNull($response);

		$this->assertInstanceOf('Google\Service\Drive\About', $response);
	}

	public function testTokensFailNoCredentials()
	{
		$client = Authorizer::authorize(
			Mode::Token,
			null,
			null,
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			null,
			['promptUser' => false, 'showWarnings' => false]);
	
		$this->assertNull($client);
	}

	public function testTokensFailNoTokens()
	{
		$client = Authorizer::authorize(
			Mode::Token,
			$this->credentialsFilePath,
			null,
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			null,
			['promptUser' => false, 'showWarnings' => false]);
	
		$this->assertNull($client);
	}

	public function testTokensDirectSuccess()
	{
		$client = Authorizer::authorizeToken(
			$this->credentialsFilePath,
			$this->tokensFilePath,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			false);

		$this->assertNotNull($client);

		$service = new \Google_Service_Drive($client);
		$about = $service->about;

		$options =
		[
			'fields' => 'storageQuota',
			'prettyPrint' => true
		];

		$response = $about->get($options);
		$this->assertNotNull($response);

		$this->assertInstanceOf('Google\Service\Drive\About', $response);
	}

	public function testTokensSuccess()
	{
		$client = Authorizer::authorize(
			Mode::Token,
			$this->credentialsFilePath,
			null,
			$this->tokensFilePath,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive']);
	
		$this->assertNotNull($client);

		$service = new \Google_Service_Drive($client);
		$about = $service->about;

		$options =
		[
			'fields' => 'storageQuota',
			'prettyPrint' => true
		];

		$response = $about->get($options);
		$this->assertNotNull($response);

		$this->assertInstanceOf('Google\Service\Drive\About', $response);
	}
}
