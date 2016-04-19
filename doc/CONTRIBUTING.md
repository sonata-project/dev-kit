# Sonata project contribution

Thanks for you interest onto Sonata projects!

## Summary

* [Issues]()
* [Pull Requests](#pull-requests)
* [Label rules]()

## Pull Requests

All the sonata team will be glad to review your code changes propositions! :smile:

But please, read the following before.

### Coding style

Each project follows [PSR-1](http://www.php-fig.org/psr/psr-1/), [PSR-2](http://www.php-fig.org/psr/psr-2/)
and [Symfony Coding Standards](http://symfony.com/doc/current/contributing/code/standards.html) for coding style,
[PSR-4](http://www.php-fig.org/psr/psr-4/) for autoloading.

Please [install PHP Coding Standard Fixer](http://cs.sensiolabs.org/#installation)
and run this command before committing your modifications:

```bash
php-cs-fixer fix --verbose
```

### Writing a Pull Requests

#### The base branch

Before writing a PR, you have to check on which branch your changes should be based.

Each project follows [semver](http://semver.org/) convention for release management.

Here is a short table resuming on which you have to start:

Kind of modification | Backward Compatible (BC) | Type of release | Branch to target | Label |
-------------------- | ------------------------ | --------------- | ---------------- | ----- |
Bug fixes            | Yes                      | Patch           | `[latest].x`     | |
Bug fixes            | No                       | Patch           | `master`         | |
Feature              | Yes                      | Minor           | `[latest].x`     | |
Feature              | No (Only if no choice)   | Major           | `master`         | |
Deprecation          | Yes (Have to)            | Minor           | `[latest].x`     | |
Deprecation removal  | No (Can't be)            | Major           | `master`         | |

Notes:
  * Branch `[latest].x` means the branch of the **latest stable** minor release (e.g. `3.x`).
  Please refer to the branch list of the project and pick the **higher** one.
  * If you PR is not **Backward Compatible** but can be, it **must** be:
    * Changing a function/method signature? Prefer create a new one and deprecated the old one.
    * Code deletion? Don't. Please deprecate it instead.
    * If your BC PR is accepted, you can do a new one on the `master` branch which remove the deprecated code.
    * SYMFONY DOC REF (same logic)?
