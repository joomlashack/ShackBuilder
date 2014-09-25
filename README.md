Alledia Builder
===========

Common Build Scripts to build our extensions.

## Setup and environment

Make sure you heve phing [installed and configured](http://www.phing.info/trac/wiki/Users/Installation).

### Filesystem Structure

All folders needs to have the same parent folder and be named as the repository name:

    ./
    +-- AllediaBuilder
    +-- AllediaInstaller
    +-- AllediaFramework
    |-- OurExtension1
    |    +-- packages
    |    |-- src
    |    |    +-- language
    |    |    +-- library
    |    |    +-- views
    |    |    |-- ourextension1.xml
    |    |-- composer.json
    |    |-- build.xml
    |
    |-- OurExtension1-Pro
    |    +-- packages
    |    |-- src
    |    |    +-- language
    |    |    |-- library
    |    |    |    +-- pro
    |    |    +-- views
    |    |    |-- ourextension1.xml
    |    |-- composer.json
    |    |-- build.xml
    |
    |-- OurExtension2
    |    +-- packages
    |    |-- src
    |    |    +-- language
    |    |    +-- library
    |    |    +-- views
    |    |    |-- ourextension1.xml
    |    |-- composer.json
    |    |-- build.xml
    |
    |-- OurExtension2-Pro
    |    +-- packages
    |    |-- src
    |    |    +-- language
    |    |    |-- library
    |    |    |    +-- pro
    |    |    +-- views
    |    |    |-- ourextension1.xml
    |    |-- composer.json
    |    |-- build.xml
    |
    |-- build.global.properties


### Phing global properties

Create a new file on the main project root folder, name as `build.global.properties` file you will set the global properties for the phing script.

    builder.path=/path/to/AllediaBuilder/

For now, you just need to set the path to AllediaBuilder repository.

### Phing script for each project

All the phing tasks should be inside this repository, and every project should import the main build.xml file from here.
Add a `build.xml` file to the repository root folder, with this basic markup:

    <?xml version="1.0" encoding="UTF-8"?>
    <project name="OurExtension1-Pro Builder" default="">
        <property file="../build.global.properties" />

        <import file="${builder.path}/src/build.xml"/>
    </project>

Replace the `OurExtension1-Pro` with the repository (folder) name

### Free and Pro extensions
All **free** repositories should be named as the product name.
All **pro** repositories should start with the name of the free product, followed by **-Pro**. The local cloned folders, needs to have the same name as the repository, for both.

The **pro** extension package will be built grabbing the source from the free extension, and copying over it the content from the pro source repository. The folders will be merged and files with the same name will be overwritten. So usually the pro repository will have the language files named with an extension prefix ".pro" and will be merged on the build time.

While building the pro extension, the builder will detect the respective free extension grabbing the property `extra.name` from the composer file.

#### Language files

For both, pro and free extensions, the language files are located inside the `language/en-GB` folder. The files for the free version are named normally.
The files for the pro version must have a `.pro` extension prefix, like: **en-GB.com_myextension.pro.sys.ini** or **en-GB.com_myextension.pro.ini**.

They doesn't should be listed as language on the manifest file and will be merged in the build time. But they must be declarad as a normal folder.

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

If you already have a newer version installed for any related extension, it will be ignored. Otherwise, it will install or update.

#### Extension attributes

* publish: force to publish the extension right after install it (only if it is new)
* uninstall: flag to indicate that this extension should be uninstalled if the main extension is uninstalled
* ordering: first, last, 1..n - only for plugins, it set an specific order (only if it is new)

### Obsolete items

*Needs refactoring*

    <obsoleteItems>
        <folder>library</folder>
        <filename>style.css</filename>
        <extension
            type="plugin"
            element="osvimeo"
            group="content">OSVimeo</extension>
    </obsoleteItems>

### Publishing and reordering plugins automatically

    <element publish="true" ordering="first">myplugin</element>

* publish: force to publish the extension right after install it (only if it is new)
* ordering: first, last, 1..n - only for plugins, it set an specific order (only if it is new)

### Installer views

These views are packed by the installer library and loaded by all extensions.

    ./views
    |   |-- installer
    |   |   |-- header_default.php
    |   |   |-- header_install.php
    |   |   |-- header_update.php
    |   |   |-- footer_default.php
    |   |   |-- footer_install.php
    |   |   |-- footer_update.php

Your extensions can override any file and add a new one: body_*.

    ./views
    |   |-- installer
    |   |   |-- body_default.php
    |   |   |-- body_install.php
    |   |   |-- body_update.php

#### variables

    $this->version, etc...

## How to use

To build the extension packages, go inside the extension folder you want to build and run the command:

    $ phing current-release

## Main tasks

* new-release
* current-release
* symlink
* unlink
