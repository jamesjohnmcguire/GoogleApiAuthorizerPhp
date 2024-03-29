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

	public function testDiscoverAllFilesNoEnvironmentVariableSuccess()
	{
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS';
		putenv($environmentVariable);

		$client = Authorizer::authorize(
			Mode::Discover,
			$this->credentialsFilePath,
			$this->serviceAccountFilePath,
			$this->tokensFilePath,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			'http://localhost:8000/test.php',
			['promptUser' => false, 'showWarnings' => false]);

		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testDiscoverAllFilesSuccess()
	{
		$client = Authorizer::authorize(
			Mode::Discover,
			$this->credentialsFilePath,
			$this->serviceAccountFilePath,
			$this->tokensFilePath,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			'http://localhost:8000/test.php',
			['promptUser' => false, 'showWarnings' => false]);

		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testDiscoverCredentialsFilesSuccess()
	{
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS';
		putenv($environmentVariable);

		$client = Authorizer::authorize(
			Mode::Discover,
			$this->credentialsFilePath,
			null,
			$this->tokensFilePath,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			'http://localhost:8000/test.php',
			['promptUser' => false, 'showWarnings' => false]);

		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testDiscoverFail()
	{
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS';
		putenv($environmentVariable);

		$client = Authorizer::authorize(
			Mode::Discover,
			null,
			null,
			$this->tokensFilePath,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			'http://localhost:8000/test.php',
			['promptUser' => false, 'showWarnings' => false]);

		$this->assertNull($client);
	}

	public function testDiscoverObjectFail()
	{
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS';
		putenv($environmentVariable);

		$authorizer = new Authorizer('Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			['promptUser' => false, 'showWarnings' => false]);

		$client = $authorizer->apiAuthorize(
			Mode::Discover,
			null,
			null,
			$this->tokensFilePath,
			'http://localhost:8000/test.php');

		$this->assertNull($client);
	}

	public function testDiscoveryObjectCredentialsFilesSuccess()
	{
		$authorizer = new Authorizer('Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			['promptUser' => false, 'showWarnings' => false]);

		$client = $authorizer->apiAuthorize(
			Mode::Discover,
			$this->credentialsFilePath,
			null,
			$this->tokensFilePath,
			'http://localhost:8000/test.php');
	
		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testDiscoveryObjectServiceAccountEnvironmentVariableSuccess()
	{
		$serviceAccountFilePath = realpath($this->serviceAccountFilePath);
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS=' .
			$serviceAccountFilePath;
		putenv($environmentVariable);

		$authorizer = new Authorizer('Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			['promptUser' => false, 'showWarnings' => false]);

		$client = $authorizer->apiAuthorize(
			Mode::Discover,
			null,
			null,
			null,
			'http://localhost:8000/test.php');
	
		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testDiscoveryObjectServiceAccountFileSuccess()
	{
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS';
		putenv($environmentVariable);

		$authorizer = new Authorizer('Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			['promptUser' => false, 'showWarnings' => false]);

		$client = $authorizer->apiAuthorize(
			Mode::Discover,
			null,
			$this->serviceAccountFilePath,
			null,
			'http://localhost:8000/test.php');

		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testDiscoveryObjectSuccess()
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

		$this->assertGoogleAbout($client);
	}

	public function testDiscoveryObjectTokensSuccess()
	{
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS';
		putenv($environmentVariable);

		$authorizer = new Authorizer('Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			['promptUser' => false, 'showWarnings' => false]);

		$client = $authorizer->apiAuthorize(
			Mode::Discover,
			$this->credentialsFilePath,
			null,
			$this->tokensFilePath,
			'http://localhost:8000/test.php');
	
		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testDiscoverServiceAccountFileNoEnvironmentVariableSuccess()
	{
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS';
		putenv($environmentVariable);

		$client = Authorizer::authorize(
			Mode::Discover,
			null,
			$this->serviceAccountFilePath,
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			'http://localhost:8000/test.php',
			['promptUser' => false, 'showWarnings' => false]);

		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testDiscoverServiceAccountEnvironmentVariableSuccess()
	{
		$serviceAccountFilePath = realpath($this->serviceAccountFilePath);
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS=' .
			$serviceAccountFilePath;
		putenv($environmentVariable);

		$client = Authorizer::authorize(
			Mode::Discover,
			null,
			null,
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			'http://localhost:8000/test.php',
			['promptUser' => false, 'showWarnings' => false]);

		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testDiscoverServiceAccountFileSuccess()
	{
		$client = Authorizer::authorize(
			Mode::Discover,
			null,
			$this->serviceAccountFilePath,
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			'http://localhost:8000/test.php',
			['promptUser' => false, 'showWarnings' => false]);

		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testDiscoverTokensSuccess()
	{
		$client = Authorizer::authorize(
			Mode::Discover,
			$this->credentialsFilePath,
			null,
			$this->tokensFilePath,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			'http://localhost:8000/test.php',
			['promptUser' => false, 'showWarnings' => false]);

		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testServiceAccountDirectEnvironmentVariableSuccess()
	{
		$serviceAccountFilePath = realpath($this->serviceAccountFilePath);
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS=' .
			$serviceAccountFilePath;
		putenv($environmentVariable);

		$client = Authorizer::authorizeServiceAccount(
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			false);
	
		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testServiceAccountDirectFileSuccess()
	{
		$client = Authorizer::authorizeServiceAccount(
			$this->serviceAccountFilePath,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			false);

		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testServiceAccountDirectNoFileOrEnvironementVariableFail()
	{
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS';
		putenv($environmentVariable);

		$client = Authorizer::authorizeServiceAccount(
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			false);

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

		$this->assertGoogleAbout($client);
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

		$this->assertGoogleAbout($client);
	}

	public function testServiceAccountNoEnvironementVariableOrFileFail()
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

	public function testServiceAccountObjectEnvironementVariableSuccess()
	{
		$serviceAccountFilePath = realpath($this->serviceAccountFilePath);
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS=' .
			$serviceAccountFilePath;
		putenv($environmentVariable);

		$authorizer = new Authorizer('Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			['promptUser' => false, 'showWarnings' => false]);

		$client = $authorizer->apiAuthorize(
			Mode::ServiceAccount,
			null,
			null,
			null,
			'http://localhost:8000/test.php');

		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testServiceAccountObjectFileSuccess()
	{
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS';
		putenv($environmentVariable);

		$authorizer = new Authorizer('Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			['promptUser' => false, 'showWarnings' => false]);

		$client = $authorizer->apiAuthorize(
			Mode::ServiceAccount,
			null,
			$this->serviceAccountFilePath,
			null,
			'http://localhost:8000/test.php');

		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testServiceAccountObjectNoEnvironementVariableOrFileFail()
	{
		$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS';
		putenv($environmentVariable);

		$authorizer = new Authorizer('Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			['promptUser' => false, 'showWarnings' => false]);

		$client = $authorizer->apiAuthorize(
			Mode::ServiceAccount,
			null,
			null,
			null,
			'http://localhost:8000/test.php');

		$this->assertNull($client);
	}

	public function testTokensDirectFailNoCredentials()
	{
		$client = Authorizer::authorizeToken(
			null,
			$this->tokensFilePath,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			false);

		$this->assertNull($client);
	}

	public function testTokensDirectFailNoTokens()
	{
		$client = Authorizer::authorizeToken(
			$this->credentialsFilePath,
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			false);

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

		$this->assertGoogleAbout($client);
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

	public function testTokensObjectDirectFailNoCredentials()
	{
		$authorizer = new Authorizer('Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			['promptUser' => false, 'showWarnings' => false]);

		$client = $authorizer->apiAuthorizeToken(
			null,
			$this->tokensFilePath);

		$this->assertNull($client);
	}

	public function testTokensObjectDirectFailNoTokens()
	{
		$authorizer = new Authorizer('Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			['promptUser' => false, 'showWarnings' => false]);

		$client = $authorizer->apiAuthorizeToken(
			$this->credentialsFilePath,
			null);

		$this->assertNull($client);
	}

	public function testTokensObjectDirectSuccess()
	{
		$authorizer = new Authorizer('Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			['promptUser' => false, 'showWarnings' => false]);

		$client = $authorizer->apiAuthorizeToken(
			$this->credentialsFilePath,
			$this->tokensFilePath);

		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
	}

	public function testTokensObjectFailNoCredentials()
	{
		$authorizer = new Authorizer('Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			['promptUser' => false, 'showWarnings' => false]);

		$client = $authorizer->apiAuthorize(
			Mode::Token,
			null,
			null,
			null,
			'http://localhost:8000/test.php');

		$this->assertNull($client);
	}

	public function testTokensObjectFailNoTokens()
	{
		$authorizer = new Authorizer('Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			['promptUser' => false, 'showWarnings' => false]);

		$client = $authorizer->apiAuthorize(
			Mode::Token,
			$this->credentialsFilePath,
			null,
			null,
			'http://localhost:8000/test.php');

		$this->assertNull($client);
	}

	public function testTokensObjectSuccess()
	{
		$authorizer = new Authorizer('Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			['promptUser' => false, 'showWarnings' => false]);

		$client = $authorizer->apiAuthorize(
			Mode::Token,
			$this->credentialsFilePath,
			null,
			$this->tokensFilePath,
			'http://localhost:8000/test.php');

		$this->assertNotNull($client);

		$this->assertGoogleAbout($client);
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

		$this->assertGoogleAbout($client);
	}

	private function assertGoogleAbout($client)
	{
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
