# Versioning, branching and tags

Use [semver](https://semver.org/) for versioning. Each sprint is considered a minor release until the customer asks for a major, non backwardly compatible re-write.

## Sprint workflow

During sprint development we create numerous branches off develop. work on them, test locally, raise PRs and merge back into develop. This work won't be visible on staging until we create a release candidate.

### Sprint start

 * Pull `main` and `develop` to ensure they are upto date
 * Merge `main` into `develop` to ensure any hot fixes are included

### Sprint development

 * Find the trello card and grab the URL, e.g. `https://trello.com/c/P5aKkOWJ/2056-market-roll-up-repo`
 * Create a branch off develop that uses the ticket number and title, e.g. `dev/2056-market-roll-up-repo`
 * Work on the ticket
 * Raise a PR into develop
 * Add a link to the PR into the Trello card
 * Wait for at least one approval on the PR
 * Merge into develop and delete branch

## Creating the initial release candidate

During the sprint it will be necessary to test the sprint work and show it to the customer. The first time we do this we will create a release candidate.

 * Tag develop with the incremented minor number and release number:
   * If the current latest release on main is tagged at `1.16.7`, the new tag will be 1.**17**.0-**rc1**
   * `git checkout develop && git tag 1.17.0-rc.1`
 * Push the tag, `git push --tags`
 * [Release to staging](https://github.com/neontribe/ARCVInfra/blob/main/ansible/DEPLOY.md#deploying-a-release-candidate-to-staging)

## Updating the release candidate

When sprint work has been done, we want to release this to staging for testing/UAT. This release will deploy work done in the sprint cycle to date onto staging.

 * Tag develop with the release number:
   * If the RC is tagged at `1.17.0-rc2`, the new tag will be 1.17.0-**rc2**
   * `git checkout develop && git tag 1.17.0-rc.2`
 * Push the tag, `git push --tags`
 * [Release to staging](https://github.com/neontribe/ARCVInfra/blob/main/ansible/DEPLOY.md#deploying-a-release-candidate-to-staging)
 * Test/UAT
 * Repeat this stage until we have a signed off piece of work.

## Releasing an RC to live

Sprint work is done and approved or a hotfix is approved. It's been tested on staging and has been accepted. We need to release it to live and staging.

 * merge the RC tag into main `git checkout main && git pull origin main && git merge 1.17.0-rc2`
 * create a new release
 * [release to live](https://github.com/neontribe/ARCVInfra/blob/main/ansible/DEPLOY.md#deploy-and-releasing-to-live)
 * [release to staging](https://github.com/neontribe/ARCVInfra/blob/main/ansible/DEPLOY.md#deploying-a-release-candidate-to-staging)

## Hotfix

 * Find the trello card and grab the URL, e.g. `https://trello.com/c/P5aKkOWJ/2099-HOTFIX-something-is-broken`
 * Create a branch off **main** that uses the ticket number and title, e.g. `hotfix/2099-HOTFIX-something-is-broken`
 * Work on the ticket
 * Raise a PR merging into **main**
 * Add a link to the PR into the Trello card
 * Wait for at least one approval on the PR
 * Merge into main and delete the dev branch
 * Tag main with a patch bump x.y.**Z**
 * [Release to staging](https://github.com/neontribe/ARCVInfra/blob/main/ansible/DEPLOY.md#deploying-a-release-candidate-to-staging)
 * Once testing is passed create a release with an incremented patch number e.g. `1.17.0` will become `1.17.1`
 * Tag and release main to staging *This will change when we are containerised*
 * Test on staging *This will change when we are containerised*
 * Cherry-pick the hotfix commits back into develop
 * [release to live](https://github.com/neontribe/ARCVInfra/blob/main/ansible/DEPLOY.md#deploy-and-releasing-to-live)
