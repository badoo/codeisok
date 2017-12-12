## Badoo gitphp repository browsing and code review tool

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
* Access control, repositories management - no gitosis is reguired, everything is done inside gitphp
* and even more

### Installation
For manual installation explore .setup dir and find all nesessary scripts and tools. Service requires mysql, php and nginx to work. Setup scenario can be found in .setup/Dockerfile.

### Docker
For docker build run "docker build -t gitphp .setup" from project root.

To run docker container use start.sh script in project root.
Docker container exposes 2 ports:
 * 8080 for HTTP instance (http://localhost:8080/).
 * 2222 as ssh-source for git operations (ssh://git@localhost:2222/testrepo.git)

### Internals

Default authorisation is just config-based. You can use 'user' user and 'password' password. To change it look for \GitPHP_Config::AUTH_METHOD and \GitPHP_Config::CONFIG_AUTH_USER fields in .config/gitphp.conf.php file.

Service is using [smarty](http://www.smarty.net) as template engine. So templates_c directory should be writable for web-service user.

Service is storing repositories at PROJECT_ROOT directory path. This directory should be writable for web-service user.

