[![Alledia](https://www.alledia.com/images/logo_circle_small.png)](https://www.alledia.com)

Alledia Builder
===========

Common Build Scripts to build our extensions.

## Requirements

* Phing
* Docker (for the tests)

### Phing properties

Create a new file on your project folder, name as `build.properties`. By default the only required settings are:

    builder.path=/path/to/AllediaBuilder/local/copy
    joomla25.support=1
    joomla34.support=1


#### Optional properties

##### Alias for Related Extension's Path

If you extension has one or more related extension/project, you must set its local copy path alias:

    project.AnotherExtensionName.path=/the/path/to/the/anotherextension

You can set one line per related extension. Use this to map the installer library or any other required project.
Pro extensions, requires to set the path for the Free version, to be able to copy the files.


### Phing script for each project

All the phing tasks should be inside this repository, and every project should import the main build.xml file from here.
Add a `build.xml` file to the repository root folder, with this basic markup:

    <?xml version="1.0" encoding="UTF-8"?>
    <project name="Alledia Extension Builder" default="">
        <if>
            <available file="./build.properties" type="file" />
            <then>
                <property file="./build.properties" />
            </then>
            <else>
                <fail message="Missed build.properties file on the project root folder. Duplicate the build.properties.dist file and customize with your settings" />
            </else>
        </if>

        <fail unless="builder.path" message="Missed builder.path property" />

        <import file="${builder.path}/src/build.xml"/>
    </project>


### Free and Pro extensions
All **free** repositories should be named as the product name.
All **pro** repositories should start with the name of the free product, followed by **-Pro**. The local cloned folders, needs to have the same name as the repository, for both.

The **pro** extension package will be built grabbing the source from the free extension, and copying over it the content from the pro source repository. The folders will be merged and files with the same name will be overwritten. So usually the pro repository will have the language files named with an extension prefix ".pro" and will be merged on the build time.

While building the pro extension, the builder will detect the respective free extension grabbing the property `extra.name` from the composer file.

#### Language files

For both, pro and free extensions, the language files are located inside the `language/en-GB` folder. The files for the free version are named normally.
The files for the pro version must have a `.pro` extension prefix, like: `en-GB.com_myextension.pro.sys.ini` or `en-GB.com_myextension.pro.ini.

They doesn't should be listed as language on the manifest file and will be merged in the build time. But they must be declared as a normal folder.

On your development environment, you can use phing to merge the files:

    $ phing merge-language-dev

This command will create merged language files inside the ./packages/dev/language/en-GB folder.
If you are using the symlink task, it will run this task first and then link the merged file (for pro versions).

### Composer.json file

All extensions needs to have a `composer.json` file on the root folder, wich is used by the phing scripts and deploy server to extract informations about your project.

    {
        "name"             : "mycompany/myextension",
        "description"      : "MyExtension",
        "minimum-stability": "stable",
        "license"          : "GPL-2+",
        "type"             : "joomla.plugin", // extension type
        "extra"            : {
            "element"        : "plg_content_myextension", // Joomla element for the extension
            "element-short"  : "myextension", // the element, without the extension type prefix
            "name"           : "MyExtension", // the extension name. On pro, used to detect the repo for the free extension
            "folder"         : "content", // only for plugins
            "client"         : "site", // client or admin
            "package-license": "free" // free, or pro
        },
        "authors"          : [
            {
                "name" : "Name",
                "email": "hello@myemail.com"
            }
        ],
        "require"          : {
            "php"       : ">= 5.3"
        }
    }

### Related Extensions

You can automatically pack other extensions while building the package. You just need to specify the related extensions on the manifest file, using this tag as example:

    <alledia>
        ...

        <relatedExtensions>
            <extension
                type="library"
                element="allediaframework"
                uninstall="true">AllediaFramework</extension>

            <extension
                type="component"
                element="anotherextension1"
                uninstall="true">AnotherExtension1</extension>

            <extension
                type="plugin"
                element="anyplugin"
                folder="content"
                publish="true"
                uninstall="true"
                ordering="first">AnyPlugin</extension>
        </relatedExtensions>

        ...
    </alledia>

If you already have a newer version installed for any related extension, it will be ignored. Otherwise, it will install or update.

#### Extension attributes

* publish: force to publish the extension right after install it (only if it is new)
* uninstall: flag to indicate that this extension should be uninstalled if the main extension is uninstalled
* ordering: first, last, 1..n - only for plugins, it set an specific order (only if it is new)

### Obsolete items

Obsolete items will be unistalled or deleted before install any related extension.
You can set 3 types of obsolete items: extension, file and folder.
For file and folder, use relative paths to the site root.

    <alledia>
        ...

        <obsolete>
            <extension
                type="plugin"
                group="system"
                element="osoldextension"
                publish="true"
                ordering="first"
                >OSOldExtension</extension>

            <file>/components/com_mycomponent/oldfile.php</file>
            <file>/administrator/components/com_mycomponent/oldfile.php</file>

            <folder>/components/com_mycomponent/oldfolder</folder>
        </obsolete>

        ...
    </alledia>

### Publishing and reordering plugins automatically

    <alledia>
        ...

        <element publish="true" ordering="first">myplugin</element>

        ...
    </alledia>

* publish: force to publish the extension right after install it (only if it is new)
* ordering: first, last, 1..n - only for plugins, it set an specific order (only if it is new)

### Installer views

These views are packed by the installer library and loaded by all extensions.

    ./views
    |   |-- installer
    |   |   |-- default.php
    |   |   |-- default_info.php
    |   |   |-- default_license.php

Your extensions can override any file and add a new one: body_*.

    ./views
    |   |-- installer
    |   |   |-- default_custom.php

#### variables

    $this->version, etc...

## Tests

You will be able to create unit, integration, functional or acceptance tests using Codeception. You don't need to have codeception installed, since it is embeded in a Docker container.

For now we support parallel tests for:

* Joomla 2.5.28
* Joomla 3.4.0-rc

### Creating tests

If you already have PHPUnit or Codeception tests in your project, rename the `./tests` and `codeception.yml` to anything else, as backup.
Now, run the `test-bootstrap` target to configure the tests.

    phing test-bootstrap

This command does more than run `codecept bootstrap`. It will try to make sure you have the required settings and create the bootstrap file and basic installer tests. So please, do not run `codecept bootstrap` manually.

You can now move your PHPUnit/Codeception tests from the backup or create your own tests based on Codeception.

### Running tests

It is able to run Codeception tests from the project in multiple versions of Joomla at the same time in parallel.
It starts two Docker containers for each Joomla, running PhantomJS and a LAMP + Codeception environment where it runs Joomla.

Use the following command to start the tests, instead of call codeception directly:

    $ phing test

It will use PhantomJS to run headless acceptance tests. To check how the screen is rendered, you can trigger screenshots at any time, using:

    $I->makeScreenshot();

They are saved in the **./tests/_output/debug/** folder.

#### Tests arguments

These arguments are optional.

* memory: used to set the memory available for the container (default: 512MB)
* params: codeception params (default: none)

    $ phing test -Dmemory=1GB -Dparams="unit path/to/TestClass:testMethod --debug"

#### Tests results

You have the tests results printed on the terminal, but they are exported as HTML to:

* /path/to/project/tests/_output/report_joomla25.html
* /path/to/project/tests/_output/report_joomla34.html

In case of errors for the acception tests, you will have a screenshot of the screen available in:

* /path/to/project/tests/_output/ClassName.testName.fail.html
* /path/to/project/tests/_output/ClassName.testName.fail.png

As we are testing multiple versions of Joomla in parallel, if a test fails for both versions you will have the screenshot only for the Joomla that last failed.

#### Tests cleanup

If you had any exception while running your tests and are seeing some odd error messages, try to cleanup things, removing the docker containers and cleaning the tests/_output/ folder. Use the command:

    $ phing test-cleanup

### Tests Workflow

* Build a installer package for the project using the current version, grabbing all dependencies
* Start docker containers for PhantomJS and Joomla 2.5 and/or Joomla 3.4
* Run the following steps in parallel, for multiple Joomla versions
 * Install the extension into the containerized Joomla, testing and looking for error messages (You can customize this test)
 * Run your Acceptance tests
 * Run your Functional tests
 * Run your Integration tests
 * Run your Unit tests
 * Build a HTML report with the tests result
 * Close and remove the containers

## How to use

To build the extension packages, go inside the extension folder you want to build and run the command:

    $ phing <target>

### Available targets

* build-new
* build
* symlink
* unlink
* test
