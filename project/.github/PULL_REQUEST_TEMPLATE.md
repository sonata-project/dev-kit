<!-- THE PR TEMPLATE IS NOT AN OPTION. DO NOT DELETE IT, MAKE SURE YOU READ AND EDIT IT! -->

<!--
    Show us you choose the right branch.
    Different branches are used for different things :
    - {{ stable_branch }} is for everything backwards compatible, like patches, features and deprecation notices
    - {{ unstable_branch }} is for deprecation removals and other changes that cannot be done without a BC-break
    More details here: https://github.com/sonata-project/{{ repository_name }}/blob/{{ stable_branch }}/CONTRIBUTING.md#the-base-branch
-->
I am targeting this branch, because {reason}.

<!--
    Specify which issues will be fixed/closed.
    Remove it if this is not related.
-->

Closes #{put_issue_number_here}

## Changelog

<!-- MANDATORY
    Fill the changelog part inside the code block.
    Follow this schema: http://keepachangelog.com/
-->

<!-- REMOVE EMPTY SECTIONS -->
```markdown
### Added
- Added some `Class::newMethod` to do great stuff

### Changed

### Deprecated

### Removed

### Fixed

### Security
```

<!--
    If this is a work in progress, uncomment this section.
    You can add as many tasks as you want.
    If some are not relevant, just remove them.
    
    ## To do
    
    - [ ] Update the tests
    - [ ] Update the documentation
    - [ ] Add an upgrade note
-->

## Subject

<!-- Describe your Pull Request content here -->
