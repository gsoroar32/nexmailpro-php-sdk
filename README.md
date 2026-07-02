# NexMailPro PHP SDK

Standalone PHP SDK for the NexMailPro email verification API.

## Endpoint

This SDK targets:

`POST https://nexmailpro.com/api/v1/verify/email`

## Requirements

- PHP 8.2+

## Installation

```bash
composer require nexmailpro/php-sdk
```

## Usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use NexMailPro\PhpSdk\Client;

$client = new Client(apiKey: 'your-api-key');

$response = $client->verifyEmail('user@example.com', [
    'source' => 'signup-form',
]);

var_dump($response);
```

## API

### `verifyEmail(string $email, array $payload = []): array`

Sends a JSON `POST` request to `/api/v1/verify/email`. The `$email` argument is always included in the request payload as the `email` field.

## Testing

If this package has not been installed with Composer yet, you can still run the test suite by pointing PHPUnit at the bundled config:

```bash
php /path/to/phpunit -c phpunit.xml.dist
```

Once dependencies are installed locally, you can also use:

```bash
composer test
```

## License

MIT
