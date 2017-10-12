# {{ package_title }}

[![Latest Stable Version](https://poser.pugx.org/{{ packagist_name }}/v/stable)](https://packagist.org/packages/{{ packagist_name }})
[![Latest Unstable Version](https://poser.pugx.org/{{ packagist_name }}/v/unstable)](https://packagist.org/packages/{{ packagist_name }})
[![License](https://poser.pugx.org/{{ packagist_name }}/license)](https://packagist.org/packages/{{ packagist_name }})

[![Total Downloads](https://poser.pugx.org/{{ packagist_name }}/downloads)](https://packagist.org/packages/{{ packagist_name }})
[![Monthly Downloads](https://poser.pugx.org/{{ packagist_name }}/d/monthly)](https://packagist.org/packages/{{ packagist_name }})
[![Daily Downloads](https://poser.pugx.org/{{ packagist_name }}/d/daily)](https://packagist.org/packages/{{ packagist_name }})

Branch | Travis | Coveralls |
------ | ------ | --------- |
{{ legacy_branch }}   | [![Build Status][travis_legacy_badge]][travis_legacy_link]     | [![Coverage Status][coveralls_legacy_badge]][coveralls_legacy_link]     |
{{ stable_branch }}   | [![Build Status][travis_stable_badge]][travis_stable_link]     | [![Coverage Status][coveralls_stable_badge]][coveralls_stable_link]     |
{{ unstable_branch }} | [![Build Status][travis_unstable_badge]][travis_unstable_link] | [![Coverage Status][coveralls_unstable_badge]][coveralls_unstable_link] |

{{ package_description }}

## Requirements

* PHP 5.6 / 7
* Symfony 2.8 / 3
* See also the `require` section of [composer.json](composer.json)

## Documentation

For the install guide and reference, see:

* [{{ packagist_name }} Documentation](http://symfony.com/doc/master/cmf/bundles/{{ website_path }}/index.html)

See also:

* [All Symfony CMF documentation](http://symfony.com/doc/master/cmf/index.html) - complete Symfony CMF reference
* [Symfony CMF Website](http://cmf.symfony.com/) - introduction, live demo, support and community links

## Support

For general support and questions, please use [StackOverflow](http://stackoverflow.com/questions/tagged/symfony-cmf).

## Contributing

Pull requests are welcome. Please see our
[CONTRIBUTING](https://github.com/symfony-cmf/symfony-cmf/blob/master/CONTRIBUTING.md)
guide.

Unit and/or functional tests exist for this bundle. See the
[Testing documentation](http://symfony.com/doc/master/cmf/components/testing.html)
for a guide to running the tests.

Thanks to 
[everyone who has contributed](contributors) already.

## License

This package is available under the [MIT license](src/Resources/meta/LICENSE).

[travis_legacy_badge]: https://travis-ci.org/symfony-cmf/{{ repository_name }}.svg?branch={{ legacy_branch }}
[travis_legacy_link]: https://travis-ci.org/symfony-cmf/{{ repository_name }}
[travis_stable_badge]: https://travis-ci.org/symfony-cmf/{{ repository_name }}.svg?branch={{ stable_branch }}
[travis_stable_link]: https://travis-ci.org/symfony-cmf/{{ repository_name }}
[travis_unstable_badge]: https://travis-ci.org/symfony-cmf/{{ repository_name }}.svg?branch={{ unstable_branch }}
[travis_unstable_link]: https://travis-ci.org/symfony-cmf/{{ repository_name }}

[coveralls_legacy_badge]: https://coveralls.io/repos/github/symfony-cmf/{{ repository_name }}/badge.svg?branch={{ legacy_branch }}
[coveralls_legacy_link]: https://coveralls.io/github/symfony-cmf/{{ repository_name }}?branch={{ legacy_branch }}
[coveralls_stable_badge]: https://coveralls.io/repos/github/symfony-cmf/{{ repository_name }}/badge.svg?branch={{ stable_branch }}
[coveralls_stable_link]: https://coveralls.io/github/symfony-cmf/{{ repository_name }}?branch={{ stable_branch }}
[coveralls_unstable_badge]: https://coveralls.io/repos/github/symfony-cmf/{{ repository_name }}/badge.svg?branch={{ unstable_branch }}
[coveralls_unstable_link]: https://coveralls.io/github/symfony-cmf/{{ repository_name }}?branch={{ unstable_branch }}
