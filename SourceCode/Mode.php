<?php

/**
 * Google API Authorization Library
 *
 * Description: Google API Authorization Library.
 * Version:     0.1.0
 * Author:      James John McGuire
 * Author URI:  http://www.digitalzenworks.com/
 * PHP version  8.1.1

 * @category  PHP
 * @package   GoogleApiAuthorization
 * @author    James John McGuire <jamesjohnmcguire@gmail.com>
 * @copyright 2022 James John McGuire <jamesjohnmcguire@gmail.com>
 * @license   MIT https://opensource.org/licenses/MIT
 * @version   0.1.0
 * @link      https://github.com/jamesjohnmcguire/GoogleApiAuthorizationPhp
 */

declare(strict_types=1);

namespace GoogleApiAuthorization;

/**
 * The Mode enum.
 *
 * Contians all the modes of authorization.
 *
 * @package GoogleApiAuthorization;
 * @author  James John McGuire <jamesjohnmcguire@gmail.com>
 * @since   0.1.0
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
