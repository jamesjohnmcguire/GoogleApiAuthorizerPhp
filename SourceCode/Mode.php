<?php

/**
 * Google API Authorization Library
 *
 * Description: Google API Authorization Library.
 * Version:     1.1.0
 * Author:      James John McGuire
 * Author URI:  http://www.digitalzenworks.com/
 * PHP version  8.1.1

 * @category  PHP
 * @package   GoogleApiAuthorization
 * @author    James John McGuire <jamesjohnmcguire@gmail.com>
 * @copyright 2022 James John McGuire <jamesjohnmcguire@gmail.com>
 * @license   MIT https://opensource.org/licenses/MIT
 * @version   1.1.0
 * @link      https://github.com/jamesjohnmcguire/GoogleApiAuthorizationPhp
 */

declare(strict_types=1);

namespace DigitalZenWorks\GoogleApiAuthorization;

/**
 * The Mode enum.
 *
 * Contains all the modes of authorization.
 */
enum Mode
{
	case None;
	case Discover;
	case OAuth;
	case Request;
	case ServiceAccount;
	case Token;
}
