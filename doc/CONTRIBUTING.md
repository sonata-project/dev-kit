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

#### The content

A Pull Request should concern one and **only one** subject.

If you want to fix a typo and improve the performance of a process, you have to do two **separated** PR.

The goal is to have a clear commit history and make possible revert easier.

If you found an issue/typo while writing your change that is not related to your work, please do another PR for that.

#### The base branch

Before writing a PR, you have to check on which branch your changes should be based.

Each project follows [semver](http://semver.org/) convention for release management.

Here is a short table resuming on which you have to start:

Kind of modification | Backward Compatible (BC) | Type of release | Branch to target | Label |
-------------------- | ------------------------ | --------------- | ---------------- | ----- |
Bug fixes            | Yes                      | Patch           | `[latest].x`     | |
Bug fixes            | No (Only if no choice)   | Major           | `master`         | |
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

Be aware that pull requests with BC breaks could be not accepted
or reported for next major release if BC is not possible.

If you are not sure of what to do, don't hesitate to open an issue about your PR project.

#### The commit message

The commit message has to be clear and related to the PR content.

The first line of the commit message must be short.
The other lines must contains a complete description of what you done and why.

The description is optional but recommended. It could be asked by the team if needed.

Bad commit message:

```
Update Admin.php
```

Good commit message:

```
Improve search indexing speed for sub-categories
```

Good commit message with description

```
Change web UI background color to pink

This is a consensus made on #4242 in addition to #1337.

We agreed that blank color is boring and so deja vu. Pink is the new way to do.
```

(Obviously, this commit is fake. :wink:)
