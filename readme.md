## Badoo gitphp repository browsing and code review tool

The project was originally forked from https://github.com/xiphux/gitphp. 
But we changed almost everything and added lot of new features.

* Branchdiffs - ability to see diffs between branches
* Branchlogs
* Authorisation via JIRA REST API or Atlassian Crowd service
* Code Review including branchdiffs
* Code syntax highlighting using http://alexgorbatchev.com/SyntaxHighlighter 
* Side-by-side review using http://www.mergely.com
* Filters in diffs on-the-fly for different file types and changes
* Search in project heads
* Access control, repositories management
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

Default authorisation is just config-based. See AUTH_USER constant in .include/Config.class.php. Jira types of authorisation can be enabled/disabled there.

Service is using [smarty](http://www.smarty.net) as template engine. So templates_c directory should be writable for web-service user.

Service is storing repositories at PROJECT_ROOT directory path. This directory should be writable for web-service user.

