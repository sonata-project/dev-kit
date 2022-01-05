# Sonata project development kit

This repository contains all common documentation and tools for all Sonata projects.

This one **must** be the **only** reference for how to contribute on those projects.

Base URL: https://master-7rqtwti-ptm4dx6rjpjko.eu-5.platformsh.site/

## Installation

```bash
git clone git@github.com:sonata-project/dev-kit.git
composer install
symfony serve
```
And you can now access locally to dev-kit at http://127.0.0.1:8000/.

Many variables in `.env` are tokens you will need to change.
- `GITHUB_OAUTH_TOKEN` can be obtained at https://github.com/settings/tokens.
- `DEV_KIT_TOKEN`
- `SLACK_TOKEN` can be obtained at https://api.slack.com/apps/A018X725S0K/install-on-team.

If your symfony connect account was added to the symfony cloud server, you can
- Deploy the master branch with `symfony deploy`.
- Access to the server by ssh (in order to run commands) with `symfony ssh`.

This project implements many useful commands, you'll may need:
- `bin/console auto-merge`: Merges branches of repositories if there is no conflict.
- `bin/console release`: Helps with a project release.
- `bin/console dispatch:files`: Dispatches files for all sonata projects.

For the list of all commands run `bin/console list`.
