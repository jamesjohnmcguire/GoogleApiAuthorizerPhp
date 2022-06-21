<?php
namespace NotarizationPlugIn\Tests;

require_once 'vendor/autoload.php';
require_once 'GoogleAuthorization.php';

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
}
