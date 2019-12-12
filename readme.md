# Somnambulist Project Manager

Project Manager is designed to help organise micro-services based PHP projects. It
incorporates commands for creating new services, libraries, and managing them.
Project configuration is stored as YAML files that you commit to a git repo and
share with your team. If you want to stop using it, delete the phar and the files
and continue on. There is no special configuration needed in any project.

## Features

 * groups separate micro services projects together
 * gives a docker overview of running processes
 * supports dependencies between services
 * supports making new services and libraries (configurable source repositories)
 * supports multiple projects on a single machine
 * all configuration is by yaml files

## Setup

Grab the phar archive and copy it to `/usr/local/bin` or add it to your path.
Symlink the phar to `projman` or a.n.other name.

Run: `projman init` to get started.

### Lazy Install

__Caution:__ you use the following at your own risk! No responsibility is taken
if the script causes bad things to happen. You have been warned.

The following will download the current (at the time of writing) phar archive,
verify the SHA384 hash and copy the phar to `/usr/local/bin`, then symlink it to
`projman` and verify it runs by calling `projman --version`. The script has been
set up with verbose output.

```bash
curl --silent --fail --location --retry 3 --output /tmp/somnambulist-project-manager.phar --url https://github.com/dave-redfern/somnambulist-project-manager/releases/download/1.0.0/somnambulist-project-manager.phar \
  && echo "e18ebaf1d7b2166797c33a5149e82d8cb1e810b7c52823615d717b612c6159c3483983992814acd459cd560bf6c7952d  /tmp/somnambulist-project-manager.phar" | shasum -a 384 -c \
  && mv -v /tmp/somnambulist-project-manager /usr/local/bin/somnambulist-project-manager.phar \
  && chmod -v 755 /usr/local/bin/somnambulist-project-manager.phar \
  && ln -vf -s /usr/local/bin/somnambulist-project-manager.phar /usr/local/bin/projman \
  && projman --ansi --version --no-interaction
```

If the hash check fails, remove the phar archive.

### Removing Project Manager

Remove any symlinks you have and delete the phar file. Delete the configuration folder
from `~/.spm_projects.d/`

```bash
unlink /usr/local/bin/projman && rm -v /usr/local/bin/somnambulist-project-manager.phar
```

## Project Configuration


### Sample Config

```yaml
somnambulist:
    common:

    project:

```

The config file will expand any configured env args using `${ENV_NAME}`
notation. `${PROJECT_DIR}` is an alias of `${PWD}`. This expansion is
done at run time only. For a full list of dedicated env vars and the
key to use to access it run: `projman params`

__Note__: no attempt is made to hide or mask sensitive env vars.

### Common


### Project



## Building the phar archive

To build the phar archive, first checkout / clone the project manager project. Run
`composer install` and ensure that `phar.readonly` is set to `0` in your php.ini.

You can then run: `bin/compile` which will create a `somnambulist-project-manager.phar`
file in the project root. The compile will output the SHA384 hash together with the
file location / name.

## Issues / Questions

Make an issue on the github repo: https://github.com/dave-redfern/somnambulist-project-manager/issues

Pull requests are welcome!
