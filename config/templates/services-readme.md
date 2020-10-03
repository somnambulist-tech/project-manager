# New Service

## Exposed Services

The following URI will be bound to the main Traefik instance provided via data-service:

 * http://service.example.dev/

### API End Points

All end points are versioned via the URL. Whenever possible backwards compatibility will be maintained.

Any error should be returned as a JSON response with an appropriate header:

 * 200 - OK
 * 400 - invalid arguments to the end point e.g.: invalid UUID string
 * 404 - requested entity was not found (e.g.: listing)
 * 500 - internal error, details in error message

When in debug mode, the error response will contain debug information including the stack trace from
the error (if any). This should be passed to the backend devs to debug what went wrong.

#### Debugging Information

The following are only available when running in debug mode:

 * X-Debug-Token - Symfony Profiler debug token for the request
 * X-Debug-Token-Link - a link to access the profiler data 

## QUICK STEPS / TL;DR / Run the service

These steps are to be used when you start a new feature branch (not when you have just done a clean clone)

1. Clone the project into an empty folder - or completely remove any existing folder and re-clone
2. `docker-compose up -d`
3. `docker-compose ps`

## Updating and starting the project development environment

Assuming you have already performed a fresh `git pull` to update your project repository, you can then
proceed with the steps below:

### Preparation

Open the terminal and cd to the directory containing this project repository.

It is recommended that you re-build all the docker images (unless you know the docker environment has not changed).
You only need to do this once after performing a `git pull` of the main develop branch.

`docker-compose build`

### Setting Up

This is the normal way of starting the docker environment. You only need to do the steps above if you've
just pulled the latest develop branch. Now you should create your feature branch and:

`docker-compose up -d`

This will launch the development environment. These steps can take a little while the first time while docker downloads
images for the various containers. It will be a lot quicker on subsequent `up` commands.

`docker-compose ps`

You should see several running containers.

### Install the project dependencies

__Note:__ this is only needed if the build steps failed.

You might like to install the dependencies using [composer](https://getcomposer.org/), but these are not
necessary (they will be installed in the Docker context)

`composer install --no-scripts`

The dependencies should be installed without errors. If you encounter an auth issue with github, ensure
you have configured your local environment and SSH keys.

### Tail the output (logs) from the docker containers

Docker compose aggregates the output from each container. You can see the most
recent output with

`docker-compose logs -f` or `docker-compose logs -f <container_name>`.

Exit by pressing `CTRL+C`

### Stop the containers

You can stop the containers with

`docker-compose down`

Alternatively, stop the containers with

`docker-compose stop`

Then you can start them again with

`docker-compose start`

`stop` and `start` are different from `down` and `up` because they don't completely destroy
and re-commission the containers. Container data is persisted.

### Starting Up Again (without having done a git pull or similar)

If you like to `dc down` your environment at the end of the day and start up again in the morning
then all you have to do to start work again is:

`docker-compose up -d`

### Troubleshooting

Docker can be difficult to troubleshoot. The first step is to check `dc ps`. If the containers
have stopped try re-starting them. Next try `dc logs <container_name>` to see if there was any
output.

For the application, there may be information in: `var/logs/<env>.log`. Tail this file for errors,
for example: `tail -f var/logs/dev.log`.

Sometimes the build process does not work correctly and the containers may need building again:

 * `docker-compose down`
 * rm -rf vendor
 * rm -f bin/doctrine* bin/phpunit
 * `docker system prune`
 * `docker-compose up -d --build --force-recreate`

## Test Suite

The unit suite can be run either against:

 * a local setup (requires redis, postgres and the accounts db loaded)
 * the docker setup

First ensure that the application is correctly setup and running:

 * local setup: `bin/console`
 * docker setup: `bin/dc-console` (requires the app service is running)

The console command should run without error and list all available commands. 
Next check the index API page, this should respond with the list of available endpoints.

__Note:__ do not mix docker and local setups, unless they are running under distinctly separate
environments set in the .env file and appended to the console as `--env=test|dev|prod`.

#### Running tests in the docker container

__Note:__ the default environment is that of `docker`. The test suite should run without the need for a custom .env.test 
unless a specific need is present to re-route services or resources.

Run the unit suite in the context of the docker container by: `bin/dc-phpunit`. This will run the full test suite
within the container and reduce the need for having multiple resources locally. Arguments can be given to run specific
test groups just as if they were being run locally.

#### Running tests locally

Run the unit suite by: `bin/phpunit` or a specific group of tests: `bin/phpunit --group=...`
Groups are defined in the tests and usually follow the form: domain-... or infrastructure-...
A full list of the groups can be obtained from: `bin/phpunit --list-groups`.

The unit suite **must** be further configured via a custom .env.test that will be preferentially loaded
during kernel booting (when needed). This is required if attempting to run the unit tests against
local services (not in the docker container).
