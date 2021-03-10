## Changing Config Options

Many of the settings in the `project.yaml` file can be changed from the CLI using the `config`
command. This can operate on the project as a whole or individual services and libraries. You
will be prompted for the subject to work with if the command is run without arguments.

The following configuration options can be changed using this command:

 * docker:name
 * docker:network
 * git:remote
 * git:branch
 * service:container:name
 * service:dependency:add
 * service:dependency:remove
 * template:add
 * template:remove

### Changing docker options

From `spm` 0.19.0, when changing either the docker network or project name, you will be prompted
if all linked services should be updated. If `y` then each mapped services docker-compose or .env
files will be updated appropriately.

These should still be reviewed manually - especially if the values in the `project.yaml` where not
consistent with the main `docker-compose.yml` in a service.

On a per service basis, additional containers can be added via a CLI command, see [services](./services.md)
for more details.

### Setting Remote Repositories

By default when using `project:create`, `libraries:create` or `services:create` a git repository
is started, but no remote is set. You can set this after the fact by using:

```shell script
spm config git:remote <project_name> <repo>
```

If the option is not provided it will be prompted for; similarly if the project name is not
specified, the current list of projects will be presented.

__Note:__ when changing the remote repository, only a remote named `origin` will be modified.
If you used a different name, you must manually change the remote and update the project config
file yourself.

If the `branch` configuration directive is set in the project config, this will be used as the
remote branch name to track.

### Setting the default branch

From `spm` v0.19.0 it is possible to set a default branch on a per library / service basis. This
will be used as the default when running any of the commands that modify the Git repository;
for example: the project update command will stash and then switch to the default branch instead
of forcing the one provided via arguments.

### Service dependencies

`spm` allows for services to depend on other services in the project. When starting or stopping
these will be started or stopped together. Use the config options to add or remove these. The
dependencies are defined as an array in the `project.yaml` on a per service basis.

 * [Getting Started](./getting-started.md)
 * [Project Configuration](./project-configuration.md)
 * [Managing Services](./services.md)
 * [Templates](./templates.md)
 * [Changing Config Options](./changing-config.md)
 * [Setting up a dev env](./init-dev-env.md)
