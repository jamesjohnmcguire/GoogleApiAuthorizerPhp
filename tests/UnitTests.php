<?php
namespace GoogleAuthorization\Tests;

require_once 'vendor/autoload.php';
require_once 'GoogleAuthorization.php';

use GoogleApiAuthorization\GoogleAuthorization;
use GoogleApiAuthorization\Mode;
use PHPUnit\Framework\TestCase;

final class UnitTests extends TestCase
{
	protected static ?string $credentialsFilePath = null;

	public function setUp() : void
	{
	}

	public static function setUpBeforeClass() : void
	{
		if (!empty($argv[4]))
		{
			self::$credentialsFilePath = $argv[4];
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

	function testTokensFailNoCredentials()
	{
		$client = GoogleAuthorization::Authorize(
			Mode::Token,
			self::$credentialsFilePath,
			null,
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive']);
	
		$this->assertNull($client);
	}

	function testTokensFailNoTokens()
	{
		$client = GoogleAuthorization::Authorize(
			Mode::Token,
			null,
			null,
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive']);
	
		$this->assertNull($client);
	}
}
