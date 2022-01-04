# Somnambulist Project Manager

[![GitHub Actions Build Status](https://img.shields.io/github/workflow/status/somnambulist-tech/project-manager/release?logo=github)](https://github.com/somnambulist-tech/project-manager/actions?query=workflow%3Arelease)
[![Issues](https://img.shields.io/github/issues/somnambulist-tech/project-manager?logo=github)](https://github.com/somnambulist-tech/project-manager/issues)
[![License](https://img.shields.io/github/license/somnambulist-tech/project-manager?logo=github)](https://github.com/somnambulist-tech/project-manager/blob/master/LICENSE)

Project Manager is designed to help organise micro-services based PHP projects. It
incorporates commands for creating new services, libraries, and managing them.
Project configuration is stored as YAML files that you commit to a git repo and
share with your team. If you want to stop using it, delete the phar and the files
and continue on. There is no special configuration needed in any project.

## Features

 * groups separate micro-service projects together
 * gives an overview of running docker processes across all services
 * supports dependencies between services
 * supports multiple projects on a single machine
 * supports templates for libraries and services
 * supports docker container definitions for easier adding to services
 * all configuration is by yaml files

## Installation

Project Manager requires PHP 8.0+ to be installed and in your shell path.

Grab the phar archive and copy it to `/usr/local/bin` or add it to your path.
Symlink the phar to `spm` or a.n.other name. Be sure to verify the SHA checksum with
the ones in the release checksums text file.

Or:

    $ brew install somnambulist-tech/somnambulist/spm

Run: `spm init` to create the standard configuration (see later).

See [getting started](docs/getting-started.md) for the next steps.

More:

 * [Project Configuration](docs/project-configuration.md)
 * [Managing Services](docs/services.md)
 * [Templates](docs/templates.md)
 * [Changing Config Options](docs/changing-config.md)
 * [Setting up a dev env](docs/init-dev-env.md)

### Removing Project Manager

If installed via brew: `brew remove somnambulist-tech/somnambulist/spm`

Remove any symlinks you have and delete the phar file. Delete the configuration folder
from `~/.config/spm_projects.d/` or `~/.spm_projects.d/`

```bash
unlink /usr/local/bin/spm && rm -v /usr/local/bin/somnambulist-project-manager.phar
```

## Building the phar archive

To build the phar archive, first checkout / clone the project manager project. Run
`composer install` and ensure that `phar.readonly` is set to `0` in your php.ini.

You can then run: `bin/compile` which will create a `somnambulist-project-manager.phar`
file in the project root. The compile command will output the SHA384 hash together with the
file location / name.

## Issues / Questions

Make an issue on the github repo: https://github.com/somnambulist-tech/project-manager/issues

Pull requests are welcome!
