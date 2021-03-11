## Customising the development environment

`spm` includes a `setup:env` command that will read a YAML file with various setup instructions
intended to get a new user up and running very quickly with the project. This file can include
any number of shell commands to set various aspects on the machine. The file must be named:
`init_[mac|linux|windows].yaml`. This command is aliased to: `init:mac`, `init:linux` and `init:windows`.

For example: you can create steps that install brew, and additional packages; or that creates
overrides / default config files in various places.

The config format is pretty simple:

```yaml
somnambulist:
    steps:
        max_files:
            message: 'Setting max open files override (requires sudo)'
            commands:
                - { run: "echo '%s' | sudo tee /Library/LaunchDaemons/limit.maxfiles.plist", file: 'init.d/limit.maxfiles.plist' }

```

Each step should have a unique name. You must provide a message and then one or more commands
that need to be run in this step. Steps can use sudo and can read files in via echo. A command
must have a `run` property, and an optional `file` for the file source. This file must be
located in the project config folder.

The following environment variables can be used and will be replaced with the appropriate value:

 * `${CONFIG_DIR}` - the project configuration folder, usually: `~/.spm_projects.d/<name>`
 * `${HOME}` - the current users home folder, usually: `~/` (/Users/<name>)
 * `${CWD}` - the current working directory from where `spm` was run (`_SERVER['PWD']`)

If any step fails, the setup halts.

The setup can be run in test mode `--test` or output all the expanded scripts by using `--bash`.
Various steps can be skipped if already applied by using `--skip=X` where X is the number of
the step.

If a particular operation requires a long time or runs as a desktop process (e.g. xcode-select)
a special `exit` command can be added that will automatically halt the setup and output the
instructions on how to continue.

If debugging is used `-vvv` then each command and it's output will be streamed to the console.

When developing a setup script, be sure to do it in a virtual machine to avoid damaging your
own setup.

 * [Getting Started](getting-started.md)
 * [Project Configuration](project-configuration.md)
 * [Managing Services](services.md)
 * [Templates](templates.md)
 * [Changing Config Options](changing-config.md)
 * [Setting up a dev env](init-dev-env.md)
