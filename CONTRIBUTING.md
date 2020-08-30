# Sonata project contribution

Thanks for you interest onto Sonata projects!

Want to contribute? Please read the [following documentation](templates/project/CONTRIBUTING.md) instead.

This document is for maintainers.

The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD",
"SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL" in this document are to be
interpreted as described in [RFC 2119](https://www.ietf.org/rfc/rfc2119.txt).

## Summary

* [Code Reviews](#code-reviews)
* [Manual merges](#manual-merges)
* [Releases](#releases)

## Code Reviews

Grooming a PR until it is ready to get merged is a contribution by itself.
Indeed, why contribute a PR if there are hundreds of PRs already waiting to get reviewed and hopefully, merged?
By taking up this task, you will try to speed up this process by making sure the merge can be made with peace of mind.

### Commenting on a PR

Before doing anything refrain to dive head-first in the details of the PR and try to see the big picture,
to understand the subject of the PR. If the PR fixes an issue, read the issue first.
This is to avoid the pain of making a reviewer rework their whole PR and then not merging it.

Things to hunt for :

- missing docs . This is what you SHOULD look for first. If you think the PR lacks docs,
ask for them, because you will be better at reviewing it if you understand it better,
and docs help a lot with that.
- missing tests : Encourage people to do TDD by making clear a PR will not get merged
if it lacks tests. Not everything is easy to test though, keep that in mind.
- BC breaks : If there is a BC-break, ask for a deprecation system to be created instead,
and make sure the `master` branch is used.
- Unclear pieces of code : does the code use clear, appropriate variable or class names,
or does it use names like `data`, `result`, `WhateverManager`, `SomethingService`?
Are exception names still meaningful if you remove the `Exception` suffix? Do all
exceptions have a custom message?
Is the contributor trying to be clever or to be clear?
- Violations of the [SOLID][solid] principles :
    - S : If a class is 3000 lines long, maybe it does too many things?
    - O : Is there a big switch statement that might grow in the future?
    - L : Does the program behave reasonably when replacing a class with a child class?
    - I : Are interfaces small and easy to implement? If not, can they be split into smaller interfaces?
    - D : Is the name of a class hardcoded in another class, with the `new` keyword or a static call?
- Spelling / grammar mistakes, including in commit messages or UPGRADE / CHANGELOG notes.
- Dependency modifications : is anything new introduced, if yes is it worth it?

[solid]: https://en.wikipedia.org/wiki/SOLID_(object-oriented_design)

Leave no stone unturned. When in doubt, ask for a clarification. If the
clarification seems useful, and does not appear in a code comment or in a commit
message, say so and / or make use a squash-merge to customize the commit message.
Ideally, the project history SHOULD be understandable without an internet connection,
and the PR SHOULD be understandable without having a look at the changes.

Also, make sure your feedback is actionable, it is important to keep the ball rolling,
so if you raise a question, try to also provide solutions.

### Labelling the PR

Applying labels requires write access to PRs, but you can still advise if you do not have them.
There are several labels that will help determine what the next version number will be.
Apply the first label that matches one of this conditions, in that order:

- `major`: there is a BC-break. The PR SHOULD target the `master` branch.
- `minor`: there is a backwards-compatible change in the API. The PR SHOULD target the stable branch.
- `patch`: this fixes an issue (not necessarily reported). The PR SHOULD target the stable branch.
- `docs`: this PR is solely about the docs. `pedantic` is implied.
- `pedantic`: this change does not warrant a release.

Also if you see that the PR lacks documentation, tests, a changelog note,
or an upgrade note, use the appropriate label.

### Reviewing PRs with several commits

If there are several commits in a PR, make sure you review it commit by commit,
so that you can check the commit messages, and make sure the commit are independent
and atomic.

### Merging

Do not merge something you wrote yourself. Do not merge a PR you reviewed alone,
instead, merge PRs that have already be reviewed and approved by another reviewer.
If the commit history is unclear or irrelevant, prefer the "Squash and merge" feature, otherwise, always
use the "Rebase and merge" feature.
And finally, use your common sense : if you see a PR about a typo,
or if there is a situation (faulty commit, revert needed) maybe you can merge it directly.

#### Dependencies version dropping

Do not merge any merge request dropping a dependency version support.
To achieve that, mark them as `RTM`, and mention then on Slack when asking for a release.

This rule SHOULD be applied for these reasons:

- Even if it's semver compatible, we don't maintain minor branches.
So it's preferable to give priority to bugfixes over version-dropping PRs.
- Some dependencies need a dev-kit update. By the way, you can make a PR on dev-kit and link it to your own.

### Be nice to the contributor

Thank them for contributing. Encourage them if you feel this is going to be long.
In short, try to make them want to contribute again. If they are stuck, try to provide them with
code yourself, or ping someone who can help.

## Manual merges

Thanks to dev-kit, stable branches are regularly merged into master branches.
It is great when it works, but often, there will be git conflicts and a human
intervention will be needed. Let us assume we are working on a repository where
the stable branch is 42.x. To do the merge manually, follow these steps:
1. Fetch the latest commits: `git fetch --all`
2. Checkout the master branch, and make sure it is up to date:
   `git checkout -B master origin/master`
3. Proceed with the merge: `git merge origin/42.x`
4. Fix the conflicts (if you are doing this, it is because of conflicts,
   right?) `git mergetool`
5. Create a merge commit `git commit`
6. Push the result to your fork: `git push fork 42.x`
7. Create a pull request from `fork/42.x` to `origin/42.x`
8. When the PR can be merged, do not merge it. Instead, use
   `git push origin 42.x`.

## Releases

### Minor releases and patch releases

Releasing software is the last step to getting your bugfixes or new features to your user base,
and SHOULD be done regularly, so that users are not tempted to use development branches.
To know what you are going to release on branch 42.x, given that the last release on this branch is 42.3.1,
go to `https://github.com/sonata-project/SonataAdminBundle/compare/42.3.1...42.x`.
You should see a list of commits, some of which SHOULD contain links to pull requests.

#### Determining the next release number

First of all, you MUST find the exact datetime of the last release.
Go to the project releases section and display the last release page:

![Release page](https://user-images.githubusercontent.com/1698357/39665568-d9dd74bc-5096-11e8-8c25-dff0d75ce717.png)

Then, click on the commit hash to show it and open the browser dev toolbar from the commit date:

![Commit page](https://user-images.githubusercontent.com/1698357/39665576-f9fa09ea-5096-11e8-8ddb-e5860a7b2122.png)

Then you know the exact datetime of the released commit is `2018-04-20T09:47:48Z`. Copy it.

It's important to NOT use the tag datetime because the tag MAY be written later after the commit.

An alternative way to do this is to issue the following command in your shell:
```shell
git describe --abbrev=0 --tags| xargs git show --pretty=format:%aI --no-patch|tail -n 1
```

After that, go on the pull requests page of the repo and replace the default filter by this one:

```
base:3.x merged:>2018-04-20T09:47:48Z
```

 - `base`: The base branch where the PR are merged.
 It MUST be the current stable branch, or the legacy branch where you want to make a release.
 - `merged`: All the pull request merged **after** the given datetime.

If any of those pull requests is labeled `minor`, then the next release SHOULD be a minor release (42.4.0).
Otherwise, if there are any pull requests labeled `patch`,
the next release SHOULD be a patch release (42.3.2).
If there are neither minor nor patch pull requests, all the others SHOULD be labeled `docs` or `pedantic`,
and you SHOULD not make a release.

![Pull requests page](https://user-images.githubusercontent.com/1698357/39665578-031aa2e6-5097-11e8-9f68-9ea32eec2b79.png)

In this case, it will be a patch release.

:warning: All the pull requests MUST have only one `patch`, `minor`, `pedantic` or `docs` label.
If you find a non-labelled PR or a `major` one, a mistake was made and MUST be fixed before the release.

#### Adding the release to the UPGRADE file

If there are any deprecations, then the release SHOULD be minor and the UPGRADE-42.x file SHOULD be changed,
moving the instructions explaining how to bypass the deprecation messages,
that SHOULD hopefully be there, to a new section named `UPGRADE FROM 42.3.1 to 42.4.0`.

```patch
 UPGRADE 42.x
 ===========

+UPGRADE FROM 42.3.1 to 42.4.0
+=============================
+
 ## Deprecated use of `getSchmeckles` in `Sonata\Defraculator\Vortex`
```

#### Upgrading code comments and deprecation messages

All occurrences of `42.x` in comments or deprecation messages SHOULD be updating
by resolving `x` to its value at the time of the release.

:warning: You can do it quickly with a "Search and Replace" feature, but be careful not to replace unwanted matches.

#### Compiling the changelog

Each non-pedantic (and therefore non-docs) PR SHOULD contain a `CHANGELOG` section,
that you need to copy manually into the `CHANGELOG.md` file.
The title is in the following format :
`[42.3.2](https://github.com/sonata-project/SonataNewsBundle/compare/42.3.1...42.3.2) - YYYY-MM-DD`.

:warning: Do not hesitate to review the changelog before the copy.
The entries SHOULD be short, clear and MUST tell what have been fixed/improved for **the end user**, not how.

#### Creating the release commit

The changes above SHOULD be added to a signed commit with the following message : `42.3.2`. Nothing else.
You MUST sign the tag with your GPG key, after which all you have to do is push it.
If you don't have push access, you can still create a PR with the relevant changes
and have them signed off by someone who has it.

Commands summary (we assume `upstream` corresponds to the Sonata repository):

```
git commit -am 42.3.2
git tag -s 42.3.2 -m 42.3.2
git push upstream && git push upstream --tags
```

#### Fill the release note on GitHub

Copy the changelog entries except the release number line and paste them on the release note form:

![Release note form](https://user-images.githubusercontent.com/1698357/39665555-bcf18aa0-5096-11e8-9249-dd7ea1eb15d2.png)

Submit the data, and you are done!

### Major releases

Major releases SHOULD be done on a regular basis,
to avoid branches getting too far from their more stable counterparts:
the biggest the gap, the harder the merges are.
We use a 3 branch system, which means releasing 42.0.0 implies that:

- the master branch is aliased to 43.x;
- 42.x becomes the stable branch;
- 41.x becomes the legacy branch;
- 40.x is abandoned.

#### Preparing the unstable branch

- Every `NEXT_MAJOR` instruction SHOULD be followed **before** the release.
- If possible, the latest version of every dependency SHOULD be supported
(`composer outdated` SHOULD NOT output anything).
- If sensible, the old major versions of every dependency SHOULD be dropped.

#### Pre-release cleanup

Before releasing anything, it is best to reduce the gap between branches:

- Merge the legacy branch into the stable branch.
- Merge the stable branch into the unstable branch.

#### Releasing last versions

Before abandoning it, the legacy branch MUST receive a last patch version.
Likewise, the stable branch MUST receive a last version if that version is minor,
it SHOULD receive one if that version is a patch version.

#### Creating the new stable branch and files

If the current major is `42`, a new `43.x` branch SHOULD be created from master,
then a commit SHOULD be done on master to bump the `branch-alias` and version numbers in the README.

Also, the following files MUST be created/updated on the new stable branch:

 - `UPGRADE-43.x.md`, containing only the main title
 - `UPGRADE-43.0.md`, containing the upgrade notes fetched from the major PRs.
 - `CHANGELOG.md`, containing the changelog of the major PRs.
 - `composer.json`, the `branch-alias` MUST be changed to `43.x-dev`

Push the new branch with a commit containing the modified files and "43.x-dev" as comment.

#### Tagging the release

Finally, a signed tag SHOULD be created on the newly-created stable branch and the release note MUST be filled.

[sphinx_install]: http://www.sphinx-doc.org/en/stable/
[pip_install]: https://pip.pypa.io/en/stable/installing/
[sf_docs_standards]: https://symfony.com/doc/current/contributing/documentation/standards.html
[semver_dependencies_update]: http://semver.org/#what-should-i-do-if-i-update-my-own-dependencies-without-changing-the-public-api
[php_supported_versions]: http://php.net/supported-versions.php
