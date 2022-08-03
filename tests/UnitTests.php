<?php

declare(strict_types=1);
namespace GoogleAuthorization\Tests;


$root = dirname(__DIR__, 1);

require_once $root . '/SourceCode/vendor/autoload.php';
require_once $root . '/SourceCode/GoogleAuthorization.php';

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

	function testTokensFailNoCredentials()
	{
		$client = GoogleAuthorization::authorize(
			Mode::Token,
			null,
			null,
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive']);
	
		$this->assertNull($client);
	}

	function testTokensFailNoTokens()
	{
		$client = GoogleAuthorization::authorize(
			Mode::Token,
			self::$credentialsFilePath,
			null,
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive']);
	
		$this->assertNull($client);
	}
}
