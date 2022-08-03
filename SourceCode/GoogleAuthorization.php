<?php

/**
 * Google API Authorization Library
 *
 * Description: Google API Authorization Library.
 * Version:     0.1.0
 * Author:      James John McGuire
 * Author URI:  http://www.digitalzenworks.com/

 * @package   GoogleApiAuthorization
 * @author    James John McGuire <jamesjohnmcguire@gmail.com>
 * @copyright 2022 James John McGuire <jamesjohnmcguire@gmail.com>
 * @license   MIT https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace GoogleApiAuthorization;

require_once 'vendor/autoload.php';
require_once 'Mode.php';

/**
 * GoogleAuthorization class.
 *
 * Contians all the core functionality for authorization.
 *
 * @package GoogleAuthorization
 * @author  James John McGuire <jamesjohnmcguire@gmail.com>
 * @since   0.1.0
 */
class GoogleAuthorization
{
	/**
	 * Authorize method.
	 *
	 * Main static method for authorization.
	 *
	 * @param Mode    $mode               The file to process.
	 * @param ?string $credentialsFile    The standard project credentials json file.
	 * @param ?string $serviceAccountFile The service account credentials json file.
	 * @param ?string $tokensFile         The tokens json file.
	 * @param ?string $name               The name of the project requesting authorization.
	 * @param ?array  $scopes             The requested scopes of the project.
	 * @param ?string $redirectUrl        The URL which the authorization will complete to.
	 * @param ?array  $options            Additional options.
	 *
	 * @return ?object
	 */
	public static function Authorize(
		Mode $mode,
		?string $credentialsFile,
		?string $serviceAccountFile,
		?string $tokensFile,
		?string $name,
		?array $scopes,
		?string $redirectUrl = null,
		?array $options = null) : ?object
	{
		$client = null;

		$promptUser = true;
		$showWarnings = true;

		// Process options
		if ($options !== null)
		{
			if (array_key_exists('promptUser', $options))
			{
				$promptUser = $options['promptUser'];
			}

			if (array_key_exists('showWarnings', $options))
			{
				$promptUser = $options['showWarnings'];
			}
		}

		switch ($mode)
		{
			case Mode::Discover:
				$client = self::AuthorizeToken(
					$credentialsFile, $tokensFile, $name, $scopes);
				
				if ($client === null)
				{
					$client = self::AuthorizeServiceAccount(
						$serviceAccountFile, $name, $scopes);

					// Http fall back, redirect user for confirmation
					if ($client === null && PHP_SAPI !== 'cli')
					{
						$client = self::RequestAuthorization(
							$credentialsFile, $tokensFile, $name, $scopes);
					}
					// else use final fall back
				}
				break;
			case Mode::OAuth:
				$client = self::AuthorizeOAuth(
					$credentialsFile, $name, $scopes, $redirectUrl);
				break;
			case Mode::Request:
				$client = self::RequestAuthorization(
					$credentialsFile, $tokensFile, $name, $scopes);
				break;
			case Mode::ServiceAccount:
				$client = self::AuthorizeServiceAccount(
					$serviceAccountFile, $name, $scopes);
				break;
			case Mode::Token:
				$client = self::AuthorizeToken(
					$credentialsFile, $tokensFile, $name, $scopes);
				break;
		}

		// Final fall back, prompt user for confirmation code through web page
		if ($client === null && $promptUser === true)
		{
			if (PHP_SAPI === 'cli')
			{
				$client = self::RequestAuthorization(
					$credentialsFile, $tokensFile, $name, $scopes);
			}
			else
			{
				$client = self::AuthorizeOAuth(
					$credentialsFile, $name, $scopes, $redirectUrl);
			}
		}

		return $client;
	}

	/**
	 * Authorize by OAuth method.
	 *
	 * Main static method for OAuth authorization.
	 *
	 * @param ?string $credentialsFile The standard project credentials json file.
	 * @param ?string $name            The name of the project requesting authorization.
	 * @param ?array  $scopes          The requested scopes of the project.
	 * @param ?string $redirectUrl     The URL which the authorization will complete to.
	 *
	 * @return ?object
	 */
	private static function AuthorizeOAuth(string $credentialsFile,
		string $name, array $scopes, string $redirectUrl)
	{
		$client = null;

		if (PHP_SAPI === 'cli')
		{
			echo 'WARNING: OAuth redirecting only works on the web' . PHP_EOL;
		}
		else
		{
			$client = self::SetClient($credentialsFile, $name, $scopes);

			$redirectUrl = filter_var($redirectUrl, FILTER_SANITIZE_URL);
			$client->setRedirectUri($redirectUrl);

			if (isset($_GET['code']))
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
	 * @param ?string $serviceAccountFilePath The service account credentials json file.
	 * @param ?string $name                   The name of the project requesting authorization.
	 * @param ?array  $scopes                 The requested scopes of the project.
	 *
	 * @return ?object
	 */
	private static function AuthorizeServiceAccount(
		$serviceAccountFilePath, $name, $scopes)
	{
		$client = null;

		if ($serviceAccountFilePath !== null &&
			file_exists($serviceAccountFilePath))
		{
			$serviceAccountFilePath = realpath($serviceAccountFilePath);
			putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $serviceAccountFilePath);
		}

		$serviceAccountFilePath = getenv('GOOGLE_APPLICATION_CREDENTIALS');

		if ($serviceAccountFilePath !== false &&
			file_exists($serviceAccountFilePath))
		{
			$client = self::SetClient(null, $name, $scopes, false);

			// nothing else to do... Google API will use
			// GOOGLE_APPLICATION_CREDENTIALS file.

			if ($client !== null)
			{
				$client->useApplicationDefaultCredentials();
			}
		}
		else
		{
			echo "WARNING: Service account credentials not set" . PHP_EOL;
		}

		return $client;
	}

	/**
	 * Authorize by tokens method.
	 *
	 * Main static method for tokens authorization.
	 *
	 * @param ?string $credentialsFile The standard project credentials json file.
	 * @param ?string $tokensFilePath  The tokens json file.
	 * @param ?string $name            The name of the project requesting authorization.
	 * @param ?array  $scopes          The requested scopes of the project.
	 *
	 * @return ?object
	 */
	private static function AuthorizeToken(
		$credentialsFile, $tokensFilePath, $name, $scopes)
	{
		$client = null;
		$accessToken = self::AuthorizeTokenFile($client, $tokensFilePath);

		if ($accessToken === null)
		{
			$accessToken = self::AuthorizeTokenLocal($client);
		}

		if ($accessToken !== null)
		{
			$client = self::SetClient($credentialsFile, $name, $scopes);
			$client =
				self::SetAccessToken($client, $accessToken, $tokensFilePath);
		}

		return $client;
	}

	/**
	 * Authorize by local tokens method.
	 *
	 * Static method for local tokens authorization.
	 *
	 * @param ?object $client The client object.
	 *
	 * @return ?array
	 */
	private static function AuthorizeTokenLocal(?object $client)
	{
		// last chance attempt of hard coded file name
		$tokenFilePath = 'token.json';

		$accessToken = self::AuthorizeTokenFile($client, $tokenFilePath);

		return $accessToken;
	}

	/**
	 * Authorize by tokens method.
	 *
	 * Main static method for tokens authorization.
	 *
	 * @param ?object $client        The client object.
	 * @param ?string $tokenFilePath The tokens json file.
	 *
	 * @return ?array
	 */
	private static function AuthorizeTokenFile($client, $tokenFilePath)
	{
		$accessToken = null;

		if ($tokenFilePath !== null && file_exists($tokenFilePath))
		{
			$fileContents = file_get_contents($tokenFilePath);
			$accessToken = json_decode($fileContents, true);
		}
		else
		{
			echo 'WARNING: token file doesn\'t exist - ' . $tokenFilePath .
				PHP_EOL;
		}

		return $accessToken;
	}

	/**
	 * Is valid json method.
	 *
	 * Checks if the given string is in valid json format.
	 *
	 * @param ?string $string The string to check.
	 *
	 * @return bool
	 */
	private static function IsValidJson($string) : bool
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
	 * @param ?string $authorizationUrl The authorization URL to use.
	 *
	 * @return string
	 */
	private static function PromptForAuthorizationCodeCli(
		string $authorizationUrl) : string
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
	 * @param ?string $credentialsFile The standard project credentials json file.
	 * @param ?string $tokensFile      The tokens json file.
	 * @param ?string $name            The name of the project requesting authorization.
	 * @param ?array  $scopes          The requested scopes of the project.
	 *
	 * @return ?object
	 */
	private static function RequestAuthorization(?string $credentialsFile,
		?string $tokensFile, ?string $name, ?array $scopes) : ?object
	{
		$client = null;

		if (PHP_SAPI !== 'cli')
		{
			echo 'WARNING: Requesting user authorization only works at the ' .
			'command line' . PHP_EOL;
		}
		else
		{
			$client = self::SetClient($credentialsFile, $name, $scopes);

			if ($client !== null)
			{
				$authorizationUrl = $client->createAuthUrl();
				$authorizationCode =
					self::PromptForAuthorizationCodeCli($authorizationUrl);
		
				$accessToken =
					$client->fetchAccessTokenWithAuthCode($authorizationCode);
				$client = self::SetAccessToken($client, $accessToken, $tokensFile);
			}
		}

		return $client;
	}

	/**
	 * Set access token method.
	 *
	 * Sets the access token in the client and stores the tokens in a file.
	 *
	 * @param ?object $client     The client object.
	 * @param ?array  $tokens     The authorization URL to use.
	 * @param ?string $tokensFile The tokens json file.
	 *
	 * @return ?object
	 */
	private static function SetAccessToken($client, $tokens, $tokensFile)
		: ?object
	{
		$updatedClient = null;

		if ((is_array($tokens)) &&
			(!array_key_exists('error', $tokens)))
		{
			$client->setAccessToken($tokens);
			$updatedClient = $client;

			$json = json_encode($tokens);

			if (!empty($tokensFile))
			{
				file_put_contents($tokensFile, $json);
			}
		}
		else if ($tokens === null)
		{
			echo 'Tokens is null' . PHP_EOL;
		}
		else if (!is_array($tokens))
		{
			echo 'Tokens is not an array' . PHP_EOL;
		}
		else if (array_key_exists('error', $tokens))
		{
			echo 'Error key exists in tokens' . PHP_EOL;
		}
		else
		{
			echo 'Problem with tokens object' . PHP_EOL;
		}

		return $updatedClient;
	}

	/**
	 * Set client method.
	 *
	 * Creates a new client object and sets default properties.
	 *
	 * @param ?string $credentialsFile     The standard project credentials json file.
	 * @param ?string $name                The name of the project requesting authorization.
	 * @param ?array  $scopes              The requested scopes of the project.
	 * @param ?string $credentialsRequired The authorization URL to use.
	 *
	 * @return ?object
	 */
	private static function SetClient(?string $credentialsFile, string $name,
		array $scopes, bool $credentialsRequired = true) : ?object
	{
		$client = null;

		if ($credentialsRequired === false ||
			($credentialsFile !== null && file_exists($credentialsFile)))
		{
			$client = new \Google_Client();

			$client->setAccessType('offline');
			$client->setApplicationName($name);
			$client->setPrompt('select_account consent');
			$client->setScopes($scopes);
		}

		if ($credentialsFile !== null && file_exists($credentialsFile))
		{
			$client->setAuthConfig($credentialsFile);
		}
		else if ($credentialsRequired === true)
		{
			echo 'credentials not found - can\'t create client' . PHP_EOL;
		}

		return $client;
	}
}
