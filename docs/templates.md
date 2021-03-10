## Working with Templates

See [project configuration](./project-configuration.md) for project configuration and template setup.

`spm` supports templates from several sources. These are:

 * composer:
 * git:
 * <folder_name> in the project config `templates` folder
 * empty

### Composer Templates

`composer:` will use `composer create-project` and requires that the source be a valid installation
project either registered with packagist.org or with private packagist.com.

To use a custom repository like with Private Packagist add `?repository=https://repo/source`.

To specify a specific version to use add `&version=XXX`. To use the latest version set the version
to `dev-master`.

The full template source would then look like:
`composer:namespace/project-name?repository=https://some.repo/somewhere&version=2.0.2`.

### Git Templates

`git:` will clone and remove the `.git` folder, essentially using the git repo as a template.

### Static Templates

`<folder_name>` will copy all files in that folder to the new source. Additionally, if the template
folder contains a `post_copy.php` file, this will be run after the files have been copied. This
script can perform any actions needed for setup. Further: if a `post_copy_args.php` exists, then
for each argument, a question will be asked for input that is then provided to the script. The
format of the args is to return an array of key => values that. For example:

```php
<?php
return [
    'name' => 'What is your name? ',
    'dob'  => 'What is your date of birth (CCYY-mm-dd)? ',
];
```

The `post_copy.php` file will be executed in a separate process in the context of the folder that
was created. If the return type of the args is not an array nothing will be asked.

The `post_copy.php` file should check the arg inputs before running.

### Generic Template (fallback)

If the template is left empty then a very basic folder is created with some general defaults including:

 * .gitignore
 * readme.md
 * src/tests folder
 * composer.json
 * phpunit.xml.dist

A blank service template includes a few more files for docker settings.
