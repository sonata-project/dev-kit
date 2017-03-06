# {{ package_title }}

{{ package_description }}

[![Latest Stable Version](https://poser.pugx.org/{{ packagist_name }}/v/stable)](https://packagist.org/packages/{{ packagist_name }})
[![Latest Unstable Version](https://poser.pugx.org/{{ packagist_name }}/v/unstable)](https://packagist.org/packages/{{ packagist_name }})
[![License](https://poser.pugx.org/{{ packagist_name }}/license)](https://packagist.org/packages/{{ packagist_name }})

[![Total Downloads](https://poser.pugx.org/{{ packagist_name }}/downloads)](https://packagist.org/packages/{{ packagist_name }})
[![Monthly Downloads](https://poser.pugx.org/{{ packagist_name }}/d/monthly)](https://packagist.org/packages/{{ packagist_name }})
[![Daily Downloads](https://poser.pugx.org/{{ packagist_name }}/d/daily)](https://packagist.org/packages/{{ packagist_name }})

Branch | Travis | Coveralls |
------ | ------ | --------- |
{{ stable_branch }}   | [![Build Status][travis_stable_badge]][travis_stable_link]     | [![Coverage Status][coveralls_stable_badge]][coveralls_stable_link]     |
{{ unstable_branch }} | [![Build Status][travis_unstable_badge]][travis_unstable_link] | [![Coverage Status][coveralls_unstable_badge]][coveralls_unstable_link] |

## Documentation

Check out the documentation on the [official website](https://sonata-project.org/bundles/{{ website_path }}).

## Support

For general support and questions, please use [StackOverflow](http://stackoverflow.com/questions/tagged/sonata).

If you think you found a bug or you have a feature idea to propose, feel free to open an issue
**after looking** at the [contributing guide](CONTRIBUTING.md).

## License

This package is available under the [MIT license](LICENSE).

[travis_stable_badge]: https://travis-ci.org/sonata-project/{{ repository_name }}.svg?branch={{ stable_branch }}
[travis_stable_link]: https://travis-ci.org/sonata-project/{{ repository_name }}
[travis_unstable_badge]: https://travis-ci.org/sonata-project/{{ repository_name }}.svg?branch={{ unstable_branch }}
[travis_unstable_link]: https://travis-ci.org/sonata-project/{{ repository_name }}

[coveralls_stable_badge]: https://coveralls.io/repos/github/sonata-project/{{ repository_name }}/badge.svg?branch={{ stable_branch }}
[coveralls_stable_link]: https://coveralls.io/github/sonata-project/{{ repository_name }}?branch={{ stable_branch }}
[coveralls_unstable_badge]: https://coveralls.io/repos/github/sonata-project/{{ repository_name }}/badge.svg?branch={{ unstable_branch }}
[coveralls_unstable_link]: https://coveralls.io/github/sonata-project/{{ repository_name }}?branch={{ unstable_branch }}
