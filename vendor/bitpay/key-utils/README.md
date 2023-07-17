bitpay/bitpay-php-keyutils
=================

[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](https://raw.githubusercontent.com/bitpay/bitpay-php-keyutils/master/LICENSE.md)
[![Packagist](https://img.shields.io/packagist/v/bitpay/key-utils.svg?style=flat-square)](https://packagist.org/packages/bitpay/key-utils)
[![Total Downloads](https://poser.pugx.org/bitpay/key-utils/downloads.svg)](https://packagist.org/packages/bitpay/key-utils)
[![Latest Unstable Version](https://poser.pugx.org/bitpay/key-utils/v/unstable.svg)](https://packagist.org/packages/bitpay/key-utils)

This dependency file provides utilities for use with the BitPay API. It enables creating keys, retrieving public keys, retrieving private keys, creating the SIN that is used in retrieving tokens from BitPay, and signing payloads for the `X-Signature` header in a BitPay API request.

# Installation

## Composer

### Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
```

### Install via composer by hand

Add to your composer.json file by hand.

```javascript
{
    ...
    "require": {
        ...
        "bitpay/key-utils": "~1.0"
    }
    ...
}
```

Once you have added this, just run:

```bash
php composer.phar update bitpay/key-utils
```

### Install using composer

```bash
php composer.phar require bitpay/key-utils:~1.0
```

# Usage

## Autoloader

To use the library's autoloader (which doesn't include composer dependencies)
instead of composer's autoloader, use the following code:

```php
<?php
$autoloader = __DIR__ . '/relative/path/to/Bitpay/Autoloader.php';
if (true === file_exists($autoloader) &&
    true === is_readable($autoloader))
{
    require_once $autoloader;
    \Bitpay\Autoloader::register();
} else {
    throw new Exception('BitPay Library could not be loaded');
}
```

## Documentation

Please see the [examples.php](https://github.com/bitpay/bitpay-php-keyutils/blob/master/examples.php) script for examples on using this library.

# Support

* https://github.com/bitpay/bitpay-php-keyutils/issues
* https://support.bitpay.com

# License

MIT License

Copyright (c) 2019 BitPay

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.