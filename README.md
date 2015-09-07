# Laravel Repository

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status](https://travis-ci.org/czim/laravel-repository.svg?branch=master)](https://travis-ci.org/czim/laravel-repository)
[![Latest Stable Version](http://img.shields.io/packagist/v/czim/laravel-repository.svg)](https://packagist.org/packages/czim/laravel-repository)

Repository setup inspired by the Bosnadev/Repository package. This package is an extended, adjusted (but entirely independent) version of that, with its own interfaces.

One major difference to the Bosnadev repository is that this one is able to deal with repeated and varying calls to the same repository instance, without breaking down or undesirable repeated application of Criteria.
You can instantiate a repository once and do anything with it in any order, and both queries and model manipulation methods will keep working.

Among the added functionality is the ability to override or 'temporarily' set and remove Criteria, post-processing models after retrieval.

I'm well aware that there is *much* to say against using Repositories like this (and the repository pattern in general), but I find they have their uses.
I prefer using them to make for easier unit testing in large projects.


## Install

Via Composer

``` bash
$ composer require czim/laravel-repository
```

If you run into problems with `phpextra/enum`, please run its installation separately beforehand:

``` bash
$ composer require phpextra/enum 'dev-master'
```

## Basic Usage

Simply extend the (abstract) repository class of your choice, either `Czim\Repository\BaseRepository`, `Czim\Repository\ExtendedRepository` or `Czim\Repository\ExtendedPostProcessingRepository`.

The only abstract method that must be provided is the `model` method (this is just like the way Bosnadev's repositories are used). 

### Base-, Extended- and PostProcessing

Depending on what you require, three different abstract repository classes may be extended:

* `BaseRepository`

    Only has the retrieval and simple manipulation methods (`create()`, `update()` and `delete`), and Criteria handling.

* `ExtendedRepository`

    Handles an **active** check for Models, which will by default exclude any model which will not have its `active` attribute set to true (configurable by setting `hasActive` and/or `activeColumn`).
    Handles caching, using [dwightwatson/rememberable](https://github.com/dwightwatson/rememberable) by default (but you can use your own Caching Criteria if desired).
    Allows you to set Model scopes, for when you want to use an Eloquent model scope to build your query.

* `ExtendedPostProcessingRepository`

    Just like Extended, but also allows for altering/decorating models after they are retrieved. By default, the only PostProcessor active is one that allows you to hide/unhide attributes on Models.

### Using the repository to retrieve models

Apart from the basic stuff (inspired by Bosnadev), there are some added methods for retrieval:
 
* `query()`: returns an Eloquent\Builder object reflecting the active criteria, for added flexibility  
* `count()`
* `first()`
* `findOrFail()`: just like `find()`, but throws an exception if nothing found
* `firstOrFail()`: just like `first()`, but throws an exception if nothing found

Every retrieval method takes into account the currently active Criteria (including one-time overrides), see below.

For the `ExtendedPostProcessingRepository` goes that postprocessors affect all models returned, and so are applied in all the retrieval methods (`find()`, `firstOrFail()`, `all()`, `allCallback`, etc).
The `query()` method returns a Builder object and therefore circumvents postprocessing. If you want to manually use the postprocessors, simply call `postProcess()` on any Model or Collection of models.


#### Handling Criteria

Just like Bosnadev's repository, Criteria may be pushed onto the repository to build queries.
It is also possible to set default Criteria for the repository by overriding the `defaultCriteria()` method and returning a Collection of Criteria instances.

Criteria may be defined or pushed onto the repository by **key**, like so:

``` php
    $repository->pushCriteria(new SomeCriteria(), 'KeyForCriteria');
```

This allows you to later remove the Criteria by referring to its key:

``` php
    // you can remove Criteria by key
    $repository->removeCriteria('KeyForCriteria'); 
```

To change the Criteria that are to be used only for one call, there are helper methods that will preserve your currently active Criteria.
If you use any of the following, the active Criteria are applied (insofar they are not removed or overridden), and additional Criteria are applied only for the next retrieval method.

``` php
    // you can push one-time Criteria
    $repository->pushCriteriaOnce(new SomeOtherCriteria());
    
    // you can override active criteria once by using its key
    $repository->pushCriteriaOnce(new SomeOtherCriteria(), 'KeyForCriteria');

    // you can remove Criteria *only* for the next retrieval, by key
    $repository->removeCriteriaOnce('KeyForCriteria');
```

Note that this means that *only* Criteria that have keys can be removed or overridden this way.
A `CriteriaKey` Enum is provided to more easily refer to the standard keys used in the `ExtendedRepository`, such as 'active', 'cache' and 'scope'.
 

## Configuration
No configuration is required to start using the repository. You use it by extending an abstract repository class of your choice. 

### Extending the classes
Some properties and methods may be extended for tweaking the way things work.
For now there is no documentation about this (I will add some later), but the repository classes contain many comments to help you find your way (mainly check the `ExtendedRepository` class). 
 
### Traits
Additionally, there are some traits that may be used to extend the functionality of the repositories, see `Czim\Repository\Traits`:

* `FindsModelsByTranslationTrait` (only useful in combination with the [dimsav/laravel-translatable](https://github.com/dimsav/laravel-translatable) package)
* `HandlesEloquentRelationManipulationTrait`
* `HandlesEloquentSavingTrait`
* `HandlesListifyModelsTrait` (only useful in combination with the [lookitsatravis/listify](https://github.com/lookitsatravis/listify) package)

I've added these mainly because they may help in using the repository pattern as a means to make unit testing possible without having to mock Eloquent models. 


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

- [Coen Zimmerman][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/czim/laravel-repository.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/czim/laravel-repository.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/czim/laravel-repository
[link-downloads]: https://packagist.org/packages/czim/laravel-repository
[link-author]: https://github.com/czim
[link-contributors]: ../../contributors
