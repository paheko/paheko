# Security Policy

We take the security of Paheko very seriously.

## Supported Versions

Only the latest stable branch is supported.

## Bug bounty

Security issues are eligible to our bug bounty program. Please contact us.

## Reporting a Vulnerability

**IMPORTANT: Do not use public websites (eg. GitHub) for reporting security vulnerabilities.**

If you find a security issue, please contact us: security/at/paheko.cloud

Please note that we are a small non-profit organization, so we might take more than a couple of days to reply to you.

Please attach as much details as you can, including logs, screenshots, etc. Sending us a proof of concept would be ideal.

Researchers may submit reports anonymously or provide contact information, including how and when our team should contact them. We may contact researchers to clarify aspects of the submitted report or gather other technical information.

By submitting a report to us, you affirm that the report and any attachments do not violate the intellectual property rights of any third party. You also grant us a non-exclusive, royalty-free, worldwide, perpetual license to use, reproduce, create derivative works, and publish the report and any attachments.

We may share vulnerability reports. We will not share the names or contact data of security researchers unless given explicit permission.

## Scope

Example of security issues that will be inside our scope:

* Injection of SQL code in parameters or forms, outside of where `SELECT` queries are allowed (Paheko allows to make arbitrary `SELECT` queries in advanced search, and in the `SQL` page in Configuration -> Advanced, this is by design, and is not a security issue (see below))
* Ability to execute SQL queries altering the database in read-only forms (advanced search and 'SQL' page)
* Ability for a regular user (non-admin) to XSS other users, including administrators
* Missing / invalid CSRF
* Remote code execution
* Directory traversal
* Ability for a user to get access to stuff only users with more permissions should have access to (eg. deleting an accounting transaction if you only have the "read" permission for accounting)
* Official plugins ([repo](https://fossil.kd2.org/paheko-plugins/)) and modules ([repo](https://fossil.kd2.org/paheko-modules/))
* KD2 libraries used by the project ([repo](https://fossil.kd2.org/kd2fw/)), but not the ones not used by Paheko (obviously)
* etc.

We also welcome security findings on Paheko.cloud as well as other hosted Paheko services.

Issues found inside the Windows packaging will be welcome, but they might have a lower priority to us.

## Out of scope

Note: users inside Paheko have different permissions, according to the category they are in.

* Paheko allows users to make `SELECT` SQL queries:
  * users with the "write" permission on "users" can access any data from the "users" table as well as users subscriptions
  * users with the "write" permission on "accounting" can access any data from the accounting tables
  * users with the "admin" permission on configuration can access any table of the database
  * access to users security credentials (password, TOTP secret, PGP public key) is forbidden to everyone by a SQLite authorizer
  * this means that accessing the SQL tables in read-only mode inside forms designed to make `SELECT` queries is not a security issue, unless accessing a table/column outside of the authorizer scope is possible
* Paheko allows users with "admin" permission in "config" to create custom HTML documents in Modules, this is by design
  * an administrator injecting a XSS is not a security issue, as they already have access to everything
* Paheko allows users with "admin" permission in "config" to restore a SQLite database form their own file. Some integrity checks are performed when doing that, but it is still possible to import a database with a broken schema or data that will trigger bugs.
  * importing a broken database file is not a security issue
  * but if importing this broken database may trigger PHP code execution, or opening of database files (eg. on the server filesystem) other than the main database, are security issues.
* Spam
* Social engineering techniques
* Denial-of-service attacks
* Content injection is out of scope unless you can clearly demonstrate a significant risk
* Executing scripts on sandboxed domains
* Bugs that require exceedingly unlikely user interactions
* Fossil bugs (please report those to the Fossil developers)
* Proof of concepts that require physical access to the device
* Flaws impacting out-of-date browsers
* Self-injection of code when having root access
* Unofficial (community) plugins and modules (but please report issues to their authors)
