# ManageGit: technical privacy and data processing

## Scope and sources

This document describes processing in the checked-in `ManageGit` source,
including `src/Job/GitLoaderJob.php` and the Git connector output classes.
It does not assert deployment-wide legal compliance or a verified retention
policy for the live BASE3 Manager installation.

## Data and purpose

The Git loader retrieves repository information from configured GitHub or
GitLab endpoints. The retrieved data may include repository names, owner
logins, branch names, commit SHA/message/date and commit author names and
email addresses. These can be personal data. The repository listing is used
for BASE3 Manager Git reports and statistics.

## Persistence and credentials

`GitLoaderJob` reads endpoint URLs, account tokens and platform identifiers
from the manager data directory's `managegit/managegit-config.ini`. It writes
`managegit/repositories.json` and a `managegit/nextrun` marker in the same
data directory. The loader uses HTTP authorization headers to authenticate
to the configured GitHub or GitLab endpoint. The current implementation does
not encrypt these files itself; filesystem protection is host-operated.

## Readers, recipients and transfers

`GitConnector`, `GitActivityConnector` and `GitActiveBranchConnector` read
`repositories.json` and provide derived JSON results. They check
`IAccesscontrol::getUserId()` for an authenticated user before returning their
JSON responses. The source shown does not establish fine-grained repository
visibility per user, nor does it verify that the configured GitHub/GitLab
endpoints or any transfers remain inside the EU. Configure endpoints and
operational access according to the actual controller's requirements.

## Retention, correction and deletion

The job checks the `nextrun` marker for a six-hour collection interval and
rewrites `repositories.json` on successful runs. No automatic deletion or
maximum retention for the Git account configuration or repository cache is
established by the checked source. Access to the data directory, backup
retention, configuration removal and deletion of old caches are deployment
responsibilities. Revoking an endpoint token must be done with its issuer;
removing the local token does not revoke the remote credential.

## Security and known gaps

Token handling is performed by the Git loader and must be secured by the host.
The available source does not prove secret redaction in downstream logs,
permission separation for private repositories, a specific transport security
policy, backup encryption or GDPR compliance. The provider DI correction in
this patch solely uses `IModuleRegistry` to locate report configuration; it
introduces no new data flow or independent storage.
