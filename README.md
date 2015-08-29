# repository

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

Repository setup inspired by the Bosnadev/Repository package. This package is an extended, adjusted version of that, with its own interfaces.
Extended functionality is the handling of overriding and setting 'temporary' Criteria, PostProcessors, and fixes for using the same instance of a repository multiple times without breaking the query-building or the means to update/manipulate models.

## Install

Via Composer

``` bash
$ composer require czim/repository
```

## Usage

Simply extend the (abstract) repository class of your choice, either FullRepository or FullRepositoryWithoutPostProcessing.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email coen@pixelindustries.com instead of using the issue tracker.

## Credits

- [Coen Zimmerman][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/czim/repository.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/czim/repository.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/czim/repository
[link-downloads]: https://packagist.org/packages/czim/repository
[link-author]: https://github.com/czim
[link-contributors]: ../../contributors
