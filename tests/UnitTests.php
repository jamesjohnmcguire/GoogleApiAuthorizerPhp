<?php
namespace GoogleAuthorization\Tests;

require_once 'vendor/autoload.php';
require_once 'GoogleAuthorization.php';

use GoogleApiAuthorization\GoogleAuthorization;
use GoogleApiAuthorization\Mode;
use PHPUnit\Framework\TestCase;

final class UnitTests extends TestCase
{
	public function setUp() : void
	{
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
			null,
			null,
			null,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive']);
	
		$this->assertNull($client);
	}
}
