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

require_once 'vendor/autoload.php';
require_once 'Mode.php';

/**
 * GoogleAuthorization class.
 *
 * Contians all the core functionality for authorization.
 */
class GoogleAuthorization
{
	/**
	 * Authorize method.
	 *
	 * Main static method for authorization.
	 *
	 * @param Mode   $mode               The file to process.
	 * @param string $credentialsFile    The standard project credentials json
	 *                                   file.
	 * @param string $serviceAccountFile The service account credentials json
	 *                                   file.
	 * @param string $tokensFile         The tokens json file.
	 * @param string $name               The name of the project requesting
	 *                                   authorization.
	 * @param array  $scopes             The requested scopes of the project.
	 * @param string $redirectUrl        The URL which the authorization will
	 *                                   complete to.
	 * @param array  $options            Additional options.
	 *
	 * @return ?object
	 */
	public static function authorize(
		Mode $mode,
		?string $credentialsFile,
		?string $serviceAccountFile,
		?string $tokensFile,
		?string $name,
		?array $scopes,
		?string $redirectUrl = null,
		?array $options = null): ?object
	{
		$client = null;

		$promptUser = true;
		$showWarnings = true;

		// Process options.
		if ($options !== null)
		{
			$keyExists = array_key_exists('promptUser', $options);
			if ($keyExists === true)
			{
				$promptUser = $options['promptUser'];
			}

			$keyExists = array_key_exists('showWarnings', $options);
			if ($keyExists === true)
			{
				$showWarnings = $options['showWarnings'];
			}
		}

		$client = self::authorizeByMode(
			$mode,
			$credentialsFile,
			$serviceAccountFile,
			$tokensFile,
			$name,
			$scopes,
			$redirectUrl,
			$showWarnings);

		// Final fall back, prompt user for confirmation code through web page.
		$client = self::finalFallBack(
			$client,
			$credentialsFile,
			$tokensFile,
			$name,
			$scopes,
			$redirectUrl,
			$promptUser);

		return $client;
	}

	/**
	 * Authorize by mode method.
	 *
	 * Main sub method for authorization.
	 *
	 * @param Mode    $mode               The file to process.
	 * @param string  $credentialsFile    The standard project credentials json
	 *                                    file.
	 * @param string  $serviceAccountFile The service account credentials json
	 *                                    file.
	 * @param string  $tokensFile         The tokens json file.
	 * @param string  $name               The name of the project requesting
	 *                                    authorization.
	 * @param array   $scopes             The requested scopes of the project.
	 * @param string  $redirectUrl        The URL which the authorization will
	 *                                    complete to.
	 * @param boolean $showWarnings       Indicates whether to output warnings
	 *                                    or not.
	 *
	 * @return ?object
	 */
	private static function authorizeByMode(
		Mode $mode,
		?string $credentialsFile,
		?string $serviceAccountFile,
		?string $tokensFile,
		?string $name,
		?array $scopes,
		?string $redirectUrl,
		bool $showWarnings): ?object
	{
		$client = null;

		switch ($mode)
		{
			case Mode::Discover:
				$client = self::authorizeToken(
					$credentialsFile,
					$tokensFile,
					$name,
					$scopes,
					$showWarnings);
				
				if ($client === null)
				{
					$client = self::authorizeServiceAccount(
						$serviceAccountFile,
						$name,
						$scopes,
						$showWarnings);

					// Http fall back, redirect user for confirmation.
					if ($client === null && PHP_SAPI !== 'cli')
					{
						$client = self::requestAuthorization(
							$credentialsFile,
							$tokensFile,
							$name,
							$scopes,
							$showWarnings);
					}
					// Else use final fall back.
				}
				break;
			case Mode::OAuth:
				$client = self::authorizeOauth(
					$credentialsFile,
					$name,
					$scopes,
					$redirectUrl,
					$showWarnings);
				break;
			case Mode::Request:
				$client = self::requestAuthorization(
					$credentialsFile,
					$tokensFile,
					$name,
					$scopes,
					$showWarnings);
				break;
			case Mode::ServiceAccount:
				$client = self::authorizeServiceAccount(
					$serviceAccountFile,
					$name,
					$scopes,
					$showWarnings);
				break;
			case Mode::Token:
				$client = self::authorizeToken(
					$credentialsFile,
					$tokensFile,
					$name,
					$scopes,
					$showWarnings);
				break;
			default:
				// Use final fall back.
				break;
		}

		return $client;
	}

	/**
	 * Authorize by OAuth method.
	 *
	 * Main static method for OAuth authorization.
	 *
	 * @param string  $credentialsFile The standard project credentials json
	 *                                 file.
	 * @param string  $name            The name of the project requesting
	 *                                 authorization.
	 * @param array   $scopes          The requested scopes of the project.
	 * @param string  $redirectUrl     The URL which the authorization will
	 *                                 complete to.
	 * @param boolean $showWarnings    Indicates whether to output warnings
	 *                                 or not.
	 *
	 * @return ?object
	 */
	private static function authorizeOauth(
		?string $credentialsFile,
		?string $name,
		?array $scopes,
		?string $redirectUrl,
		bool $showWarnings): ?object
	{
		$client = null;

		if (PHP_SAPI === 'cli')
		{
			if ($showWarnings === true)
			{
				echo 'WARNING: OAuth redirecting not supported ' .
					'on the command line' . PHP_EOL;
			}
		}
		else
		{
			$client = self::setClient(
				$credentialsFile,
				$name,
				$scopes,
				true,
				$showWarnings);

			$redirectUrl = filter_var($redirectUrl, FILTER_SANITIZE_URL);
			$client->setRedirectUri($redirectUrl);

			$codeExists = array_key_exists('code', $_GET);

			if ($codeExists === true)
			{
				$code = $_GET['code'];
				$token = $client->fetchAccessTokenWithAuthCode($code);
				$client->setAccessToken($token);
			}
			else
			{
				$authorizationUrl = $client->createAuthUrl();
				header('Location: ' . $authorizationUrl);
			}
		}

		return $client;
	}

	/**
	 * Authorize by service account method.
	 *
	 * Main static method for service account authorization.
	 *
	 * @param string  $serviceAccountFilePath The service account credentials
	 *                                        json file.
	 * @param string  $name                   The name of the project requesting
	 *                                        authorization.
	 * @param array   $scopes                 The requested scopes of the
	 *                                        project.
	 * @param boolean $showWarnings           Indicates whether to output
	 *                                        warnings or not.
	 *
	 * @return ?object
	 */
	private static function authorizeServiceAccount(
		?string $serviceAccountFilePath,
		?string $name,
		?array $scopes,
		bool $showWarnings): ?object
	{
		$client = null;

		$exists = file_exists($serviceAccountFilePath);

		if ($serviceAccountFilePath !== null && $exists === true)
		{
			$serviceAccountFilePath = realpath($serviceAccountFilePath);
			putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $serviceAccountFilePath);
		}

		$serviceAccountFilePath = getenv('GOOGLE_APPLICATION_CREDENTIALS');

		if ($serviceAccountFilePath !== false && $exists === true)
		{
			$client = self::setClient(
				null,
				$name,
				$scopes,
				false,
				$showWarnings);

			// Nothing else to do... Google API will use the file defined in the
			// GOOGLE_APPLICATION_CREDENTIALS environment variable.
			if ($client !== null)
			{
				$client->useApplicationDefaultCredentials();
			}
		}
		elseif ($showWarnings === true)
		{
			echo 'WARNING: Service account credentials not set' . PHP_EOL;
		}

		return $client;
	}

	/**
	 * Authorize by tokens method.
	 *
	 * Main static method for tokens authorization.
	 *
	 * @param string  $credentialsFile The standard project credentials json
	 *                                 file.
	 * @param string  $tokensFilePath  The tokens json file.
	 * @param string  $name            The name of the project requesting
	 *                                 authorization.
	 * @param array   $scopes          The requested scopes of the project.
	 * @param boolean $showWarnings    Indicates whether to output
	 *                                 warnings or not.
	 *
	 * @return ?object
	 */
	private static function authorizeToken(
		?string $credentialsFile,
		?string $tokensFilePath,
		?string $name,
		?array $scopes,
		bool $showWarnings): ?object
	{
		$client = null;
		$accessToken = self::authorizeTokenFile($tokensFilePath, $showWarnings);

		if ($accessToken === null)
		{
			$accessToken = self::authorizeTokenLocal($showWarnings);
		}

		if ($accessToken !== null)
		{
			$client = self::setClient(
				$credentialsFile,
				$name,
				$scopes,
				true,
				$showWarnings);

			$client = self::setAccessToken(
				$client,
				$accessToken,
				$tokensFilePath,
				$showWarnings);
		}

		return $client;
	}

	/**
	 * Authorize by local tokens method.
	 *
	 * Static method for local tokens authorization.
	 *
	 * @param boolean $showWarnings Indicates whether to output warnings or not.
	 *
	 * @return ?array
	 */
	private static function authorizeTokenLocal(bool $showWarnings): ?array
	{
		// Last chance attempt of hard coded file name.
		$tokenFilePath = 'token.json';

		$accessToken = self::authorizeTokenFile($tokenFilePath, $showWarnings);

		return $accessToken;
	}

	/**
	 * Authorize by tokens method.
	 *
	 * Main static method for tokens authorization.
	 *
	 * @param string  $tokenFilePath The tokens json file.
	 * @param boolean $showWarnings  Indicates whether to output warnings
	 *                               or not.
	 *
	 * @return ?array
	 */
	private static function authorizeTokenFile(
		?string $tokenFilePath,
		bool $showWarnings): ?array
	{
		$accessToken = null;
		$exists = false;

		if ($tokenFilePath !== null)
		{
			$exists = file_exists($tokenFilePath);

			if ($exists === true)
			{
				$fileContents = file_get_contents($tokenFilePath);
				$accessToken = json_decode($fileContents, true);
			}
		}

		if ($exists === false && $showWarnings === true)
		{
			echo 'WARNING: token file doesn\'t exist - ' . $tokenFilePath .
				PHP_EOL;
		}

		return $accessToken;
	}

	/**
	 * Final fall back authorization method.
	 *
	 * Last change method for authorization, usually requiring user interaction.
	 *
	 * @param object  $client          The client object.
	 * @param string  $credentialsFile The standard project credentials json
	 *                                 file.
	 * @param string  $tokensFile      The tokens json file.
	 * @param string  $name            The name of the project requesting
	 *                                 authorization.
	 * @param array   $scopes          The requested scopes of the project.
	 * @param string  $redirectUrl     The URL which the authorization will
	 *                                 complete to.
	 * @param boolean $promptUser      Indicates whether to prompt the user to
	 *                                 continue.
	 *
	 * @return ?object
	 */
	private static function finalFallBack(
		?object $client,
		?string $credentialsFile,
		?string $tokensFile,
		?string $name,
		?array $scopes,
		?string $redirectUrl,
		bool $promptUser)
	{
		if ($client === null && $promptUser === true)
		{
			if (PHP_SAPI === 'cli')
			{
				$client = self::requestAuthorization(
					$credentialsFile,
					$tokensFile,
					$name,
					$scopes,
					true);
			}
			else
			{
				$client = self::authorizeOauth(
					$credentialsFile,
					$name,
					$scopes,
					$redirectUrl,
					true);
			}
		}

		return $client;
	}

	/**
	 * Is valid json method.
	 *
	 * Checks if the given string is in valid json format.
	 *
	 * @param string $string The string to check.
	 *
	 * @return boolean
	 */
	private static function isValidJson(?string $string): bool
	{
		$isValidJson = false;

		json_decode($string);
		$check = json_last_error();

		if ($check === JSON_ERROR_NONE)
		{
			$isValidJson = true;
		}

		return $isValidJson;
	}

	/**
	 * Prompt for authorization code CLI method.
	 *
	 * Prompts the user the authorization code in the command line interface.
	 *
	 * @param string $authorizationUrl The authorization URL to use.
	 *
	 * @return string
	 */
	private static function promptForAuthorizationCodeCli(
		string $authorizationUrl): string
	{
		echo 'Open the following link in your browser:' . PHP_EOL;
		echo $authorizationUrl . PHP_EOL;
		echo 'Enter verification code: ';
		$authorizationCode = fgets(STDIN);
		$$authorizationCode = trim($authorizationCode);

		return $authorizationCode;
	}

	/**
	 * Prompt for authorization code CLI method.
	 *
	 * Prompts the user the authorization code in the command line interface.
	 *
	 * @param string  $credentialsFile The standard project credentials json
	 *                                 file.
	 * @param string  $tokensFile      The tokens json file.
	 * @param string  $name            The name of the project requesting
	 *                                 authorization.
	 * @param array   $scopes          The requested scopes of the project.
	 * @param boolean $showWarnings    Indicates whether to output warnings
	 *                                 or not.
	 *
	 * @return ?object
	 */
	private static function requestAuthorization(
		?string $credentialsFile,
		?string $tokensFile,
		?string $name,
		?array $scopes,
		bool $showWarnings): ?object
	{
		$client = null;

		if (PHP_SAPI !== 'cli')
		{
			if ($showWarnings === true)
			{
				echo 'WARNING: Requesting user authorization only works at ' .
					'the command line' . PHP_EOL;
			}
		}
		else
		{
			$client = self::setClient(
				$credentialsFile,
				$name,
				$scopes,
				true,
				$showWarnings);

			if ($client !== null)
			{
				$authorizationUrl = $client->createAuthUrl();
				$authorizationCode =
					self::promptForAuthorizationCodeCli($authorizationUrl);
		
				$accessToken =
					$client->fetchAccessTokenWithAuthCode($authorizationCode);
				$client =
					self::setAccessToken($client, $accessToken, $tokensFile);
			}
		}

		return $client;
	}

	/**
	 * Set access token method.
	 *
	 * Sets the access token in the client and stores the tokens in a file.
	 *
	 * @param object  $client       The client object.
	 * @param array   $tokens       The authorization URL to use.
	 * @param string  $tokensFile   The tokens json file.
	 * @param boolean $showWarnings Indicates whether to output warnings or not.
	 *
	 * @return ?object
	 */
	private static function setAccessToken(
		?object $client,
		?array $tokens,
		?string $tokensFile,
		bool $showWarnings): ?object
	{
		$updatedClient = null;

		if ($tokens !== null)
		{
			$isArray = is_array($tokens);
			$errorExists = array_key_exists('error', $tokens);

			if ($isArray === true && $errorExists === false)
			{
				$client->setAccessToken($tokens);
				$updatedClient = $client;

				$json = json_encode($tokens);

				$isEmpty = empty($tokensFile);

				if ($isEmpty === false)
				{
					file_put_contents($tokensFile, $json);
				}
			}
		}
		elseif ($updatedClient === null && $showWarnings === true)
		{
			if ($tokens === null)
			{
				echo 'Tokens is null' . PHP_EOL;
			}
			elseif ($isArray === false)
			{
				echo 'Tokens is not an array' . PHP_EOL;
			}
			elseif ($errorExists === true)
			{
				echo 'Error key exists in tokens' . PHP_EOL;
			}
			else
			{
				echo 'Problem with tokens object' . PHP_EOL;
			}
		}

		return $updatedClient;
	}

	/**
	 * Set client method.
	 *
	 * Creates a new client object and sets default properties.
	 *
	 * @param string  $credentialsFile     The standard project credentials json
	 *                                     file.
	 * @param string  $name                The name of the project requesting
	 *                                     authorization.
	 * @param array   $scopes              The requested scopes of the project.
	 * @param boolean $credentialsRequired The authorization URL to use.
	 * @param boolean $showWarnings        Indicates whether to output warnings
	 *                                     or not.
	 *
	 * @return ?object
	 */
	private static function setClient(
		?string $credentialsFile,
		string $name,
		array $scopes,
		bool $credentialsRequired,
		bool $showWarnings): ?object
	{
		$client = null;
		$exists = false;

		$client = new \Google_Client();

		$client->setAccessType('offline');
		$client->setApplicationName($name);
		$client->setPrompt('select_account consent');
		$client->setScopes($scopes);

		if ($credentialsFile !== null)
		{
			$exists = file_exists($credentialsFile);
		}

		if ($exists === true)
		{
			$client->setAuthConfig($credentialsFile);
		}
		elseif ($credentialsRequired === true && $showWarnings === true)
		{
			echo 'credentials not found' . PHP_EOL;
		}

		return $client;
	}
}
