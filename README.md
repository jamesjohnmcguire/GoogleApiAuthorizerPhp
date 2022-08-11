# GoogleApiAuthorization
A library for making authorization for Google API services easy.

## Getting Started

### Requirements/dependencies

* [PHP >= 8.1.1](http://php.net/)

## Installation
### Git
git clone https://github.com/jamesjohnmcguire/GoogleApiAuthorizationPhp

### Composer
composer require https://packagist.org/packages/digitalzenworks/google-api-authorizer


## Usage:

There is one main class with one main static method.  You can specify the authorization mode, such as 'by tokens' or 'service account'  You can also use Mode::Discover, which will attempt to go through several different types of authorization until success.  If none of the automatic modes succeed and promptUser is true, it will try some of the user interaction modes.  You can call it like this:

```php
require_once  'vendor/autoload.php';

use GoogleApiAuthorization\GoogleAuthorization;
use GoogleApiAuthorization\Mode;

$client = GoogleAuthorization::authorize(
	Mode::Discover,
	$credentialsFilePath,
	$serviceAccountFilePath,
	$tokensFilePath,
	'Google Drive API File Uploader',
	['https://www.googleapis.com/auth/drive'],
	'http://localhost:8000/test.php',
	['promptUser' => false, 'showWarnings' => false]);

if ($client === null)
{
	echo 'Oops, authorization failed';
}
else
{
	echo 'Client is initialized and authorized, lets go';
}
```

The main method parameters are:

| Type:    | Parameter:                                        |
| -------- | ------------------------------------------------- |
| Mode     | The Mode to use.                                  |
| ?string  | The standard project credentials json file.       |
| ?string  | The service account credentials json file.        |
| ?string  | The tokens json file.                             |
| ?string  | The name of the project requesting authorization. |
| ?array   | The requested scopes of the project.              |
| ?string  | The URL which the authorization will complete to. |
| ?array   | Additional options.                               |

The different modes are:

| Mode:           |                                                            |
| --------------- | ---------------------------------------------------------- |
| Discover        | Try several different types of authorization until success |
| OAuth           | Prompt the user for OAuth approval (HTTP)                  |
| Request         | Prompt the user for custom approval (CLI)                  |
| ServiceAccount  | Use serice account credentials                             |
| Token           | Use existing OAuth tokens                                  |

The different options are:

| Option:       |                                                  | default |
| ------------- | ---------------------------------------------------------- |
| promptUser    | If needed, prompt the user for additional action | true    |
| showWarnings  | Output warnings, if present                      | true    |

## Additional Examples
You can call the authorization modes directly, such as:
```php
		$client = GoogleAuthorization::authorizeServiceAccount(
			$this->serviceAccountFilePath,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			false);

		$client = GoogleAuthorization::authorizeToken(
			$this->credentialsFilePath,
			$this->tokensFilePath,
			'Google Drive API File Uploader',
			['https://www.googleapis.com/auth/drive'],
			false);
```

View the tests.php or UnitTests.php files for more examples.

## Additional Notes
Even when using the 'service account' mode, the service account file is optional.  If a valid file doesn't exist, it will attempt to use the file specified in the environment variable GOOGLE_APPLICATION_CREDENTIALS.

## Contributing

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also simply open an issue with the tag "enhancement".

### Process:

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Coding style
Please match the current coding style.  Most notably:  
1. One operation per line
2. Use complete English words in variable and method names
3. Attempt to declare variable and method names in a self-documenting manner


## License

Distributed under the MIT License. See `LICENSE` for more information.

## Contact

James John McGuire - [@jamesmc](https://twitter.com/jamesmc) - jamesjohnmcguire@gmail.com

Project Link: [https://github.com/jamesjohnmcguire/GoogleApiAuthorizationPhp](https://github.com/jamesjohnmcguire/GoogleApiAuthorizationPhp)
