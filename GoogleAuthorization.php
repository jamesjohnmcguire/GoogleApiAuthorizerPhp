<?php
declare(strict_types=1);

namespace GoogleApiAuthorization;

include_once 'vendor/autoload.php';

enum Mode
{
	case None;
	case Discover;
	case OAuth;
	case Request;
	case ServiceAccount;
	case Token;
}

class GoogleAuthorization
{
	public static function Authorize (
		Mode $mode,
		?string $credentialsFile,
		?string $serviceAccountFile,
		?string $tokensFile,
		?string $name,
		?array $scopes,
		?string $redirectUrl = null)
	{
		$client = null;

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
					$credentialsFile, $name, $scopes,$redirectUrl);
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
		if ($client === null && PHP_SAPI === 'cli')
		{
			$client = self::RequestAuthorization(
				$credentialsFile, $tokensFile, $name, $scopes);
		}

		return $client;
	}

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

	private static function AuthorizeToken(
		$credentialsFile, $tokensFilePath, $name, $scopes)
	{	
		$client = null;
		$accessToken = self::AuthorizeTokenFile($client, $tokensFilePath);

		if ($accessToken === null)
		{
			$accessToken = self::AuthorizeTokenLocal($client);
		}

		$client = self::SetClient($credentialsFile, $name, $scopes);

		$client = self::SetAccessToken($client, $accessToken, $tokensFilePath);

		return $client;
	}

	private static function AuthorizeTokenLocal($client)
	{
		// last chance attempt of hard coded file name
		$tokenFilePath = 'token.json';

		$accessToken = self::AuthorizeTokenFile($client, $tokenFilePath);

		return $accessToken;
	}

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

	private static function IsValidJson($string)
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

	private static function PromptForAuthorizationCodeCli(
		string $authorizationUrl)
	{
		echo 'Open the following link in your browser:' . PHP_EOL;
		echo $authorizationUrl . PHP_EOL;
		echo 'Enter verification code: ';
		$authorizationCode = fgets(STDIN);
		$$authorizationCode = trim($authorizationCode);

		return $authorizationCode;
	}

	private static function RequestAuthorization(
		string $credentialsFile, $tokensFile, string $name, array $scopes)
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

	private static function SetAccessToken($client, $tokens, $tokensFile)
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

	private static function SetClient(?string $credentialsFile, string $name,
		array $scopes, bool $credentialsRequired = true)
	{
		$client = null;

		if ($credentialsRequired === false ||
			($credentialsFile !== null && file_exists($credentialsFile)))
		{
			$client = new Google_Client();

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
