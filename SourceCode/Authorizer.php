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
 * Authorizer class.
 *
 * Contians all the core functionality for authorization.
 */
class Authorizer
{
	/**
	 * Name.
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * Scopes.
	 *
	 * @var array
	 */
	public array $scopes;

	/**
	 * Prompt user.
	 *
	 * @var boolean
	 */
	public bool $promptUser = true;

	/**
	 * Show warnings.
	 *
	 * @var boolean
	 */
	public bool $showWarnings = true;

	/**
	 * Constructor.
	 *
	 * Constuctor method.
	 *
	 * @param string $name    The name of the project requesting authorization.
	 * @param array  $scopes  The requested scopes of the project.
	 * @param array  $options Additional options.
	 */
	public function __construct(
		?string $name,
		?array $scopes,
		?array $options = null)
	{
		$this->name = $name;
		$this->scopes = $scopes;

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
	}

	/**
	 * Authorize method.
	 *
	 * Main static method for authorization.
	 *
	 * @param Mode   $mode                   The file to process.
	 * @param string $credentialsFilePath    The standard project credentials
	 *                                       json file.
	 * @param string $serviceAccountFilePath The service account credentials
	 *                                       json file.
	 * @param string $tokensFilePath         The tokens json file.
	 * @param string $name                   The name of the project requesting
	 *                                       authorization.
	 * @param array  $scopes                 The requested scopes of the
	 *                                       project.
	 * @param string $redirectUrl            The URL which the authorization
	 *                                       will complete to.
	 * @param array  $options                Additional options.
	 *
	 * @return ?object
	 */
	public static function authorize(
		Mode $mode,
		?string $credentialsFilePath,
		?string $serviceAccountFilePath,
		?string $tokensFilePath,
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
			$credentialsFilePath,
			$serviceAccountFilePath,
			$tokensFilePath,
			$name,
			$scopes,
			$redirectUrl,
			$showWarnings);

		// Final fall back, prompt user for confirmation code through web page.
		$client = self::finalFallBack(
			$client,
			$credentialsFilePath,
			$tokensFilePath,
			$name,
			$scopes,
			$redirectUrl,
			$promptUser);

		return $client;
	}

	/**
	 * Authorize by OAuth method.
	 *
	 * Main static method for OAuth authorization.
	 *
	 * @param string  $credentialsFilePath The standard project credentials json
	 *                                     file.
	 * @param string  $name                The name of the project requesting
	 *                                     authorization.
	 * @param array   $scopes              The requested scopes of the project.
	 * @param string  $redirectUrl         The URL which the authorization will
	 *                                     complete to.
	 * @param boolean $showWarnings        Indicates whether to output warnings
	 *                                     or not.
	 *
	 * @return ?object
	 */
	public static function authorizeOauth(
		?string $credentialsFilePath,
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
				$credentialsFilePath,
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
	public static function authorizeServiceAccount(
		?string $serviceAccountFilePath,
		?string $name,
		?array $scopes,
		bool $showWarnings): ?object
	{
		$client = null;
		$exists = false;

		if ($serviceAccountFilePath !== null)
		{
			$exists = file_exists($serviceAccountFilePath);

			if ($exists === true)
			{
				$serviceAccountFilePath = realpath($serviceAccountFilePath);
				$environmentVariable = 'GOOGLE_APPLICATION_CREDENTIALS=' .
					$serviceAccountFilePath;
				putenv($environmentVariable);
			}
		}

		// Even if specified file in variable is invalid, perhaps environment
		// variable ok.
		$serviceAccountFilePath = getenv('GOOGLE_APPLICATION_CREDENTIALS');

		if ($serviceAccountFilePath !== false)
		{
			$exists = file_exists($serviceAccountFilePath);
		}

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
	 * @param string  $credentialsFilePath The standard project credentials json
	 *                                     file.
	 * @param string  $tokensFilePath      The tokens json file.
	 * @param string  $name                The name of the project requesting
	 *                                     authorization.
	 * @param array   $scopes              The requested scopes of the project.
	 * @param boolean $showWarnings        Indicates whether to output
	 *                                     warnings or not.
	 *
	 * @return ?object
	 */
	public static function authorizeToken(
		?string $credentialsFilePath,
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
				$credentialsFilePath,
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
	 * Prompt for authorization code CLI method.
	 *
	 * Prompts the user the authorization code in the command line interface.
	 *
	 * @param string  $credentialsFilePath The standard project credentials json
	 *                                     file.
	 * @param string  $tokensFilePath      The tokens json file.
	 * @param string  $name                The name of the project requesting
	 *                                     authorization.
	 * @param array   $scopes              The requested scopes of the project.
	 * @param boolean $showWarnings        Indicates whether to output warnings
	 *                                     or not.
	 *
	 * @return ?object
	 */
	public static function requestAuthorization(
		?string $credentialsFilePath,
		?string $tokensFilePath,
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
				$credentialsFilePath,
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
				$client = self::setAccessToken(
					$client,
					$accessToken,
					$tokensFilePath);
			}
		}

		return $client;
	}

	/**
	 * Authorize method.
	 *
	 * Main static method for authorization.
	 *
	 * @param Mode   $mode                   The file to process.
	 * @param string $credentialsFilePath    The standard project credentials
	 *                                       json file.
	 * @param string $serviceAccountFilePath The service account credentials
	 *                                       json file.
	 * @param string $tokensFilePath         The tokens json file.
	 * @param string $redirectUrl            The URL which the authorization
	 *                                       will complete to.
	 *
	 * @return ?object
	 */
	public function apiAuthorize(
		Mode $mode,
		?string $credentialsFilePath,
		?string $serviceAccountFilePath,
		?string $tokensFilePath,
		?string $redirectUrl = null): ?object
	{
		$client = self::authorize(
			$mode,
			$credentialsFilePath,
			$serviceAccountFilePath,
			$tokensFilePath,
			$this->name,
			$this->scopes,
			$redirectUrl,
			$this->options);

		return $client;
	}

	/**
	 * Authorize by OAuth method.
	 *
	 * Main static method for OAuth authorization.
	 *
	 * @param string $credentialsFilePath The standard project credentials json
	 *                                    file.
	 * @param string $redirectUrl         The URL which the authorization will
	 *                                    complete to.
	 *
	 * @return ?object
	 */
	public function apiAuthorizeOauth(
		?string $credentialsFilePath,
		?string $redirectUrl): ?object
	{
		$client = self::authorizeOauth(
			$credentialsFilePath,
			$this->name,
			$this->scopes,
			$redirectUrl,
			$this->showWarnings);

		return $client;
	}

	/**
	 * Authorize by service account method.
	 *
	 * Main static method for service account authorization.
	 *
	 * @param string $serviceAccountFilePath The service account credentials
	 *                                       json file.
	 *
	 * @return ?object
	 */
	public function apiAuthorizeServiceAccount(
		?string $serviceAccountFilePath): ?object
	{
		$client = self::authorizeServiceAccount(
			$serviceAccountFilePath,
			$this->name,
			$this->scopes,
			$this->showWarnings);

		return $client;
	}

	/**
	 * Authorize by tokens method.
	 *
	 * Main static method for tokens authorization.
	 *
	 * @param string $credentialsFilePath The standard project credentials json
	 *                                    file.
	 * @param string $tokensFilePath      The tokens json file.
	 *
	 * @return ?object
	 */
	public function apiAuthorizeToken(
		?string $credentialsFilePath,
		?string $tokensFilePath): ?object
	{
		$client = self::authorizeToken(
			$credentialsFilePath,
			$tokensFilePath,
			$this->name,
			$this->scopes,
			$this->showWarnings);

		return $client;
	}

	/**
	 * Prompt for authorization code CLI method.
	 *
	 * Prompts the user the authorization code in the command line interface.
	 *
	 * @param string $credentialsFilePath The standard project credentials json
	 *                                    file.
	 * @param string $tokensFilePath      The tokens json file.
	 *
	 * @return ?object
	 */
	public function requestApiAuthorization(
		?string $credentialsFilePath,
		?string $tokensFilePath): ?object
	{
		$client = self::requestAuthorization(
			$credentialsFilePath,
			$tokensFilePath,
			$this->name,
			$this->scopes,
			$this->showWarnings);

		return $client;
	}

	/**
	 * Authorize by mode method.
	 *
	 * Main sub method for authorization.
	 *
	 * @param Mode    $mode                   The file to process.
	 * @param string  $credentialsFilePath    The standard project credentials
	 *                                        json file.
	 * @param string  $serviceAccountFilePath The service account credentials
	 *                                        file.
	 * @param string  $tokensFilePath         The tokens json file.
	 * @param string  $name                   The name of the project requesting
	 *                                        authorization.
	 * @param array   $scopes                 The requested scopes of the
	 *                                        project.
	 * @param string  $redirectUrl            The URL which the authorization
	 *                                        will complete to.
	 * @param boolean $showWarnings           Indicates whether to output
	 *                                        warnings or not.
	 *
	 * @return ?object
	 */
	private static function authorizeByMode(
		Mode $mode,
		?string $credentialsFilePath,
		?string $serviceAccountFilePath,
		?string $tokensFilePath,
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
					$credentialsFilePath,
					$tokensFilePath,
					$name,
					$scopes,
					$showWarnings);
				
				if ($client === null)
				{
					$client = self::authorizeServiceAccount(
						$serviceAccountFilePath,
						$name,
						$scopes,
						$showWarnings);

					// Http fall back, redirect user for confirmation.
					if ($client === null && PHP_SAPI !== 'cli')
					{
						$client = self::requestAuthorization(
							$credentialsFilePath,
							$tokensFilePath,
							$name,
							$scopes,
							$showWarnings);
					}
					// Else use final fall back.
				}
				break;
			case Mode::OAuth:
				$client = self::authorizeOauth(
					$credentialsFilePath,
					$name,
					$scopes,
					$redirectUrl,
					$showWarnings);
				break;
			case Mode::Request:
				$client = self::requestAuthorization(
					$credentialsFilePath,
					$tokensFilePath,
					$name,
					$scopes,
					$showWarnings);
				break;
			case Mode::ServiceAccount:
				$client = self::authorizeServiceAccount(
					$serviceAccountFilePath,
					$name,
					$scopes,
					$showWarnings);
				break;
			case Mode::Token:
				$client = self::authorizeToken(
					$credentialsFilePath,
					$tokensFilePath,
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
	 * @param string  $tokensFilePath The tokens json file.
	 * @param boolean $showWarnings   Indicates whether to output warnings
	 *                                or not.
	 *
	 * @return ?array
	 */
	private static function authorizeTokenFile(
		?string $tokensFilePath,
		bool $showWarnings): ?array
	{
		$accessToken = null;
		$exists = false;

		if ($tokensFilePath !== null)
		{
			$exists = file_exists($tokensFilePath);

			if ($exists === true)
			{
				$fileContents = file_get_contents($tokensFilePath);
				$accessToken = json_decode($fileContents, true);
			}
		}

		if ($exists === false && $showWarnings === true)
		{
			echo 'WARNING: token file doesn\'t exist - ' . $tokensFilePath .
				PHP_EOL;
		}

		return $accessToken;
	}

	/**
	 * Final fall back authorization method.
	 *
	 * Last change method for authorization, usually requiring user interaction.
	 *
	 * @param object  $client              The client object.
	 * @param string  $credentialsFilePath The standard project credentials json
	 *                                     file.
	 * @param string  $tokensFilePath      The tokens json file.
	 * @param string  $name                The name of the project requesting
	 *                                     authorization.
	 * @param array   $scopes              The requested scopes of the project.
	 * @param string  $redirectUrl         The URL which the authorization will
	 *                                     complete to.
	 * @param boolean $promptUser          Indicates whether to prompt the user
	 *                                     to continue.
	 *
	 * @return ?object
	 */
	private static function finalFallBack(
		?object $client,
		?string $credentialsFilePath,
		?string $tokensFilePath,
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
					$credentialsFilePath,
					$tokensFilePath,
					$name,
					$scopes,
					true);
			}
			else
			{
				$client = self::authorizeOauth(
					$credentialsFilePath,
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
	 * Set access token method.
	 *
	 * Sets the access token in the client and stores the tokens in a file.
	 *
	 * @param object  $client         The client object.
	 * @param array   $tokens         The authorization URL to use.
	 * @param string  $tokensFilePath The tokens json file.
	 * @param boolean $showWarnings   Indicates whether to output warnings or
	 *                                not.
	 *
	 * @return ?object
	 */
	private static function setAccessToken(
		?object $client,
		?array $tokens,
		?string $tokensFilePath,
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

				$isEmpty = empty($tokensFilePath);

				if ($isEmpty === false)
				{
					file_put_contents($tokensFilePath, $json);
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
	 * @param string  $credentialsFilePath The standard project credentials json
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
		?string $credentialsFilePath,
		string $name,
		array $scopes,
		bool $credentialsRequired,
		bool $showWarnings): ?object
	{
		$client = null;
		$exists = false;

		if ($credentialsFilePath !== null)
		{
			$exists = file_exists($credentialsFilePath);
		}

		if ($credentialsRequired === false || $exists === true)
		{
			$client = new \Google_Client();

			$client->setAccessType('offline');
			$client->setApplicationName($name);
			$client->setPrompt('select_account consent');
			$client->setScopes($scopes);
	
			if ($exists === true)
			{
				$client->setAuthConfig($credentialsFilePath);
			}
		}
		elseif ($credentialsRequired === true && $showWarnings === true)
		{
			echo 'credentials not found' . PHP_EOL;
		}

		return $client;
	}
}
