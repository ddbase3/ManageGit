# ManageGit
Base3Framework Base3Manager plugin for Git.

## Configuration

Configuration example of Github and Gitlab accounts in [DATADIR]/managegit/managegit-config.ini

```bash
[my-github-conf]
token = "MY_GITHUB_TOKEN"
endpoint = "https://api.github.com"
platform = "github"

[my-gitlab-conf]
token = "MY_GITLAB_TOKEN"
endpoint = "https://gitlab.example.com/api/v4"
platform = "gitlab"
```



## Report configuration provider

`ManageGitPlugin` registers `Base3\Vizion\Api\IReportConfigProvider` with
`Base3\Vizion\Service\FileReportConfigProvider`. This provider receives the
shared framework `Base3\CoreFoundation\Api\IModuleRegistry` from the standalone
BASE3 bootstrap. It resolves report JSON files through module roots under
`local/Vizion/<report>.json`; the class map does not provide filesystem paths.

The registration change is limited to dependency injection: it does not
migrate data, modify git account tokens or change user permissions.
