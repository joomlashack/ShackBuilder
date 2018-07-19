[![Alledia](https://www.alledia.com/images/logo_circle_small.png)](https://www.alledia.com)

Alledia Builder
===========

Common Build Scripts to build our extensions.

## Requirements

* Phing
* Docker (for the tests)
 * alledia/codeception
 * alledia/joomla-codeception:joomla25
 * alledia/joomla-codeception:joomla34

### Phing properties

Create a new file on your project folder, name as `build.properties`. By default the only required settings are:

    builder.path=/path/to/AllediaBuilder/local/copy
    test.container.joomla25=1
    test.container.joomla34=1


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
                uninstall="false">AllediaFramework</extension>

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

### Merge and minify scripts

Scripts can be minified and optionally merged creating a bundle file. This requires the JsShrink library and setting the global property `home.path` on the AllediaBuilder/global.properties file.

To minify script files you create a `<minify>` tag inside the `<alledia>` tag. 
You can specify single script files, as well create bundle files.

You can specify a custom suffix to be added to the compressed file, right before the extension. The default suffix is ".min".

`<minify suffix="-min">`

The minification is applied while building the package. If you need to run it on developing time, like before committing changes, you can call the task: `phing pre-build`.

#### Single script files

Single files are defined by a `<script>` tag:

    <alledia>
        ...
        <minify>
            <script>media/js/script1.js</script>
            <script>media/js/script2.js</script>
        </minify>
    </alledia>

This will result on new files:

* media/js/script1.min.js
* media/js/script2.min.js

#### Bundle of script files

A bundle can be created merging files defined inside a `<scripts>` tag. The destination is set on the "destination" attribute: 

    <alledia>
        ...
        <minify>
            <script>media/js/script1.js</script>
            <script>media/js/script2.js</script>

            <scripts destination="media/js/script-bundle.js">
                <script>media/js/script3.js</script>
                <script>media/js/script4.js</script>
            </scripts>
        </minify>
    </alledia>

This will result on new files:

* media/js/script1.min.js
* media/js/script2.min.js
* media/js/script-bundle.js
* media/js/script-bundle.min.js

#### Installing JSShrink using composer

    composer global require tedivm/jshrink

### Compiling SCSS Files
SASS SCSS files can be compiled by inclusion of the `<scss>` tag under the `<alledia>` tag. This
requires node-sass to be installed. During development you can use `phing pre-build` if you don't
want to build a new package.

    <alledia>
        ...
        <scss destination="folder-name" style="compressed">
            <file>scss-file-name</file>
        </scss>
    </alledia>

Folder and file paths are all relative to the project source folder. All attributes for
the `<scss>` tag are optional. By default the destination file will be the same folder as
the source file. The default for output style will be `compressed`.

#### Installing node-sass
You need to have npm/node installed first. For full details see
[nvm on github](https://github.com/creationix/nvm). Try this command to install nvm:

```
curl -o- https://raw.githubusercontent.com/creationix/nvm/v0.33.11/install.sh | bash
```
Once npm is installed you can install node-sass:
```
npm -g install node-sass
```   

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

### Requirements

Install Docker and pull the alledia images:

    $ docker pull alledia/codeception
    $ docker pull alledia/joomla-codeception:joomla25
    $ docker pull alledia/joomla-codeception:joomla34

You don't need to have codeception installed locally, since it will run inside the containers.

### Creating tests

If you already have PHPUnit or Codeception tests in your project, rename the `./tests` and `codeception.yml` to anything else, as backup.
Now, run the `test-bootstrap` target to configure the tests.

    phing test-bootstrap

This command does more than run `codecept bootstrap`. It will try to make sure you have the required settings and create the bootstrap file and basic installer tests. So please, do not run `codecept bootstrap` manually.

You can now move your PHPUnit/Codeception tests from the backup or create your own tests based on Codeception.

What this command does?

* Run `codeception bootstrap`
* Make sure you have `test.container.joomla25=1` in your `build.properties` file
* Make sure you have `test.container.joomla34=1` in your `build.properties` file
* Customize tests/acceptance.suite.yml file
* Customize tests/_bootstrap.php

You can choose what joomla version you want to run just customizing the `test.container.joomla25` property.

### Running tests

It is able to run Codeception tests from the project in multiple versions of Joomla at the same time in parallel.
It starts two Docker containers for each Joomla, running PhantomJS and a LAMP + Codeception environment where it runs Joomla.

Use the following command to start the tests, instead of call codeception directly:

    $ phing test

It will use PhantomJS to run headless acceptance tests. To check how the screen is rendered, you can trigger screenshots at any time, using:

    $I->makeScreenshot();

They are saved in the **./tests/_output/debug/** folder.

#### Tests arguments (optional)

* memory: used to set the memory available for the container (default: 512MB).
* params: codeception params (default: none)

How to use?

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

By running the command `phing test` the script will execute the following steps:

* Build an installer package for the project using the current version, grabbing all dependencies
* Start docker containers for each Joomla version your extension support
* Run the following steps in parallel, for each supported Joomla version
 * Install the extension into the containerized Joomla, testing and looking for error messages (You can customize this test)
 * Run your test suites
 * Build a HTML report with the tests result
 * Close and remove the containers

## How to use

To build the extension packages, go inside the extension folder you want to build and run the command:

    $ phing <target>

### Available targets

* build
* build-new
* symlink
* unlink
* test-bootstrap
* test
* test-cleanup
