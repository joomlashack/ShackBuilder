Alledia Builder
===========

Common Build Scripts to build our extensions.

## Setup and environment

Make sure you heve phing [installed and configured](http://www.phing.info/trac/wiki/Users/Installation).

### Filesystem Structure

All folders needs to have the same parent folder and be named as the repository name:

    ./
    +-- AllediaBuilder
    +-- AllediaLibrary
    |-- OurExtension1
    |    +-- packages
    |    |-- src
    |    |    +-- language
    |    |    +-- library
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
    |    |    |-- ourextension1.xml
    |    |-- composer.json
    |    |-- build.xml
    |
    |-- OurExtension2
    |    +-- packages
    |    |-- src
    |    |    +-- language
    |    |    +-- library
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
    |    |    |-- ourextension1.xml
    |    |-- composer.json
    |    |-- build.xml
    |
    |-- build.global.properties


### Phing global properties

Create a new file on the main project root folder, name as `build.global.properties` file you will set the global properties for the phing script.

    builder.path=/Volumes/Projects/repositories/PhingScripts

For now, you just need to set the builder path.

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

The **pro** extension package will be built grabbing the source from the free extension, and copying over it the content from the pro source repository. The folders will be merged and files with the same name will be overwritten. So usually the pro repository will have the language files and manifest duplicated, but customized.

While building the pro extension, the builder will detect the respective free extension grabbing the property `extra.name` from the composer file.

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

### Language files

Joomla is able to read by itself two language files .sys.ini. One should be located inside the extension, on the language folder. The other one will be placed inside the system language folder (which overrides the other one).

For **free** extensions we just need one .sys.ini file, which will be located inside the extension folder and **not** listed on the manifest file.
For **pro** extensions, we need two files. The file coming from the free extension and a custom file for the new language terms. The second one needs to placed inside a folder called `language-pro` (both files have the same name). This second language file **must be** listed on the manifest file, which will force it to be copied to the system's language folder, overriding the free language file.

For **pro** version manifest:

    <languages folder="language-pro">
       <language tag="en-GB">en-GB/en-GB.plg_content_osyoutube.sys.ini</language>
    </languages>

    <files>
        <folder>language</folder>
        <folder>language-pro</folder>
        ...

## How to use

To build the extension packages, go inside the extension folder you want to build and run the command:

    $ phing current-release

## Main tasks

* new-release
* current-release
* symlink
* unlink
