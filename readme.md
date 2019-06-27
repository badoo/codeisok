# Badoo repository browsing and code review tool

![logo](https://raw.githubusercontent.com/badoo/codeisok/master/images/codeisok-logo.png "logo")

### [Installation guide](https://github.com/badoo/codeisok/wiki/Installation)

### [Authorisation](https://github.com/badoo/codeisok/wiki/Authorisation)

### [Administration](https://github.com/badoo/codeisok/wiki/Administration)
* [Users management](https://github.com/badoo/codeisok/wiki/Administration#users-management)
* [Repositories management](https://github.com/badoo/codeisok/wiki/Administration#repositories-management)
* [Access management](https://github.com/badoo/codeisok/wiki/Administration#access-management)

### [Get started](https://github.com/badoo/codeisok/wiki/Get-started)

### [Code review](https://github.com/badoo/codeisok/wiki/Code-review)
* [Commitdiff review](https://github.com/badoo/codeisok/wiki/Code-review#commitdiff-review)
   1. [Unified mode](https://github.com/badoo/codeisok/wiki/Code-review#unified-mode)
   2. [Side-by-side mode](https://github.com/badoo/codeisok/wiki/Code-review#side-by-side-mode)
* [Branchdiff review](https://github.com/badoo/codeisok/wiki/Code-review#branchdiff-review)
* [Blob review](https://github.com/badoo/codeisok/wiki/Code-review#blob-review)
* [Review for diff between two commits](https://github.com/badoo/codeisok/wiki/Code-review#review-for-diff-between-two-commits)



The project was originally forked from https://github.com/xiphux/gitphp. 
But we changed almost everything and added lot of new features.

* Branchdiffs - ability to see diffs between branches
* Branchlogs
* treediff mode - as unified but with folders/files tree on the left side
* toggle comments mode - ability to see all comments in review in a one view
* Authorisation via JIRA REST API, Atlassian Crowd service or Redmine REST API
* Comments about review to Jira or Redmine tickets
* Code Review including branchdiffs
* Code syntax highlighting using http://alexgorbatchev.com/SyntaxHighlighter with a lot of additional languages support
* Side-by-side review using http://www.mergely.com
* Filters in diffs on-the-fly for different file types and changes
* Search in project heads
* Access control, repositories management - no gitosis is reguired, everything is done inside codeisok
* and even more

Please find the full documentation in [codeisok wiki](https://github.com/badoo/codeisok/wiki)

### Installation
For manual installation explore .setup dir and find all nesessary scripts and tools. Service requires mysql, php and nginx to work. Setup scenario can be found in .setup/Dockerfile.

### Docker
For docker build run "docker build -t codeisok .setup" from project root.

To run docker container use start.sh script in project root.
Docker container exposes 2 ports:
 * 80 for HTTP instance (http://localhost/).
 * 22 as ssh-source for git operations (ssh://git@localhost/testrepo.git)

To run container in non-interactive mode (background) - replace `-it` options with `-d` one in `docker run` command

### Internals

Default authorisation is just config-based. You can use 'user' user and 'password' password. To change it look for \GitPHP_Config::AUTH_METHOD and \GitPHP_Config::CONFIG_AUTH_USER fields in .config/gitphp.conf.php file.

Service is using [smarty](http://www.smarty.net) as template engine. So templates_c directory should be writable for web-service user.

Service is storing repositories at PROJECT_ROOT directory path. This directory should be writable for web-service user.

