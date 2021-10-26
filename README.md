[![Joomlashack](https://www.joomlashack.com/images/logo_circle_small.png)](https://www.joomlashack.com)

ShackBuilder
============

Common Build Scripts to build Joomlashack extensions.

## Requirements
* Phing v2.16
* php v7.2
* JShrink v1.3.3
* NodeJS v14.5.0
  * node-sass

## Usage
The default target builds and creates an install package using
the current version number in the primary manifest file:
```shell
phing build
```
or just
```shell
phing
```

To update and create an install package for a new version, use this
command (Note that version/creation dates for all local related
extensions will also be updated to the new version):
```shell
phing build-new
```

If you only want to update any minified files or compile the scss files:
```shell
phing pre-build
```

You can see all available targets:
```shell
phing -l
```

## Installing phing
There are several ways to install phing depending on your preferences and
technical skills. We recommend using composer to make management of your
phing installation easier. To install composer:

* MacOS/Homebrew
  * [Install Homebrew](https://docs.brew.sh/Installation)
  * Use the terminal command:\
    `brew install composer`
* MacOS [Install Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos)
* Windows [Install Composer](https://getcomposer.org/doc/00-intro.md#installation-windows)

Use this command in a terminal window to verify that composer is successfully
installed:
```shell
composer --version
```

Then you can install phing globally on your system using this command:
```shell
composer global require phing/phing
```

Verify phing is correctly installed with:
```shell
phing -v
```

### Installing JShrink
The minification feature requires JShrink. If, as recommended you have
installed composer globally:

```shell
composer global require tedivm/jshrink
```

### Installing node.js
We recommend using nvm (Node Version Manager) to manage installation of npm (Node Package Manager)
and Node.

#### MacOS
See [Installing nvm](https://github.com/nvm-sh/nvm#installing-and-updating) for full details.
Summary - Use the following command in a terminal window:

```shell
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.38.0/install.sh | bash
```

#### Windows
nvm is primarily maintained for *nix systems (like MacOS). [Corey Butler](https://butlerlogic.com/)
has created a windows version that works perfectly for windows. For details see his
[Github repository for nvm](https://github.com/coreybutler/nvm-windows).

Download and run the appropriate Windows installer for your system:

Download [Windows installer v1.1.7](https://github.com/coreybutler/nvm-windows/releases/download/1.1.7/nvm-setup.zip)

Verify your installation of nvm/npm/node using these commands:
```bash
nvm version
npm -v
```

### Installing Node Modules
Once you have [installed Node JS](#installing-nodejs), you can easily
install the needed modules using npm.

To process SCSS files, the node-sass node.js package must be installed.
```bash
npm -g install node-sass
node-sass -v
``` 

### Phing properties
Create a new file in your project folder, named `build.properties`.
The only required setting is:

    builder.path=/path/to/AllediaBuilder/local/copy

See [clients/build.dist.properties](src/clients/build.dist.properties)
for more details on available properties.

#### Global properties
Many `build.properties` settings will be common to many projects.
These properties can be set in `global.properties` in your local
clone of this repository.

On some systems, you may have to tell the builder where to find composer's
autoload.php file. If you have installed the composer dependencies globally
as described above, this is typically the home directory for your user account

    home.path=/path/to/user/home/directory

The builder expects to find the file `.composer/vendor/autoload.php` in the specified
directory.

See [global.dist.properties](global.dist.properties) for more properties that are appropriately
set globally.

##### Alias for Related Extension's Path
If you extension has one or more related extension/project,
you must set its local copy path alias:

    project.AnotherExtensionName.path=/the/path/to/anotherextension

You can set one line per related extension. Use this to map the
installer library or any other required project.

If a Pro version has a corresponding Free version, you must indicate
this with two properties:

  project.hasFreeVersion=1
  project.FreeExtensionName.path=/path/to/FreeExtension

### Phing script for each project
Any project using this builder must copy the build.xml file
from the `clients` folder here into the project's root folder.

### Free and Pro extensions
All **free** repositories should be named as the product name.
All **pro** repositories should start with the name of the free
product, followed by **-Pro**. The local cloned folders, need
to have the same name as the repository, for both.

The **pro** extension package will be built grabbing the source
from the free extension, and copying over it the content from the
pro source repository. The folders will be merged and files with
the same name will be overwritten. So usually the pro repository
will have the language files named with an extension prefix ".pro"
and will be merged on the build time.

While building the pro extension, the builder will detect the respective
free extension using the property `extra.name` from the composer file.

#### Language files
For both, pro and free extensions, the language files are in the `language`
folder. The files for the free version should be named normally.

The files for the pro version must have a `.pro` extension prefix, like:
`en-GB/en-GB.com_myextension.pro.sys.ini` or `en-GB/en-GB.com_myextension.pro.ini.
They will be merged during the build process.

You can use phing to merge the files:
```shell
    phing merge-languages
```

This command will create merged language files inside the ./packages/dev/language/en-GB folder.

### Composer.json file

All extensions need to have a `composer.json` file in the root folder, which is used by the phing
scripts and deploy server to extract information about your project.
```json
    {
        "name"             : "mycompany/myextension",
        "description"      : "MyExtension",
        "minimum-stability": "stable",
        "license"          : "GPL-2+",
        "type"             : "joomla-plugin",
        "extra"            : {
            "element"        : "plg_content_myextension",
            "element-short"  : "myextension",
            "name"           : "MyExtension",
            "folder"         : "content",
            "client"         : "site",
            "package-license": "free"
        },
        "authors"          : [
            {
                "name" : "Name",
                "email": "hello@myemail.com"
            }
        ],
        "require"          : {
            "php"       : ">=7.2.5"
        }
    }
```
|Key|Description
|---|---
|type|extension type
|extra.element|Full element name for the extension
|extra.element-short|Short element name, without any type or folder prefix
|extra.name|Extension name. On pro, used to detect the repo for the free extension
|extra.folder|For plugins, the folder/type of plugin
|client|'client' or 'admin'
|package-license|'free', 'pro' or 'paid'

### Related Extensions
You can automatically pack other extensions while building the package. You just need to specify the related extensions on the manifest file, using this tag as example:
```xml
    <alledia>
        <relatedExtensions publish="false"
                           downgrade="false"
                           uninstall="false">
            <extension type="library"
                       element="allediaframework"
                       downgrade="false"
                       uninstall="false">AllediaFramework</extension>

            <extension type="component"
                       element="anotherextension1"
                       downgrade="true"
                       uninstall="true">AnotherExtension1</extension>

            <extension type="plugin"
                       element="anyplugin"
                       folder="content"
                       publish="true"
                       uninstall="true"
                       downgrade="true"
                       ordering="first">AnyPlugin</extension>
        </relatedExtensions>
    </alledia>
```

Defaults can be set in the &lt;relatedExtensions&gt; tag. Defaults
when nothing specified anywhere are in **bold**

|tag|values|Description
|---|------|-----------
|downgrade|true&#124;**false**|Allow downgrade on update of the main extension.
|uninstall|true&#124;**false**|Allow uninstall when the main extension is uninstalled.
|publish|true&#124;**false**|Plugins only - Publish the right after new install (ignored on updates)
|ordering|<ul><li>first, 0, 1</li><li>last, *</li><li>&#91;before&#124;after&#93;:shortelementname</li></ul>|Plugins only - force a specific order on new installs

### Merge and minify scripts
Scripts can be minified and optionally merged creating a bundled file.
This requires the [JShrink library](#installing-jshrink).

To minify script files you create a `<minify>` tag inside the `<alledia>` tag. 
You can specify single script files, as well create bundle files.

You can specify a custom suffix to be added to the compressed file, right before the extension. The default suffix is ".min".
```xml
<minify suffix="-min">
</minify>
```

The minification is applied while building the package. If you need to run it on developing time, like before committing changes, you can call the task: `phing pre-build`.

#### Single script files
Single files are defined by a `<script>` tag:
```xml
    <alledia>
        <minify>
            <script>media/js/script1.js</script>
            <script>media/js/script2.js</script>
        </minify>
    </alledia>
```

This will result in new files:

* media/js/script1.min.js
* media/js/script2.min.js

#### Bundle of script files
A bundle can be created merging files defined inside a `<scripts>` tag. The destination is set on the "destination" attribute: 
```xml
    <alledia>
        <minify>
            <script>media/js/script1.js</script>
            <script>media/js/script2.js</script>

            <scripts destination="media/js/script-bundle.js">
                <script>media/js/script3.js</script>
                <script>media/js/script4.js</script>
            </scripts>
        </minify>
    </alledia>
```

This will result in new files:

* media/js/script1.min.js
* media/js/script2.min.js
* media/js/script-bundle.js
* media/js/script-bundle.min.js

### Compiling SCSS Files
SASS SCSS files can be compiled by inclusion of the `<scss>`
tag under the `<alledia>` tag.
This [requires node-sass](#installing-node-modules) to be installed. 
```xml
    <alledia>
        <scss destination="folder-name" style="compressed">
            <file>scss-file-name</file>
        </scss>
    </alledia>
```

Folder and file paths are all relative to the project source folder.
`<scss>` tag accepts the following attributes:

|Attribute|Value|
|---------|-----|
|destination|Folder name [**default to same as original**]
|style      |nested&#124;expanded&#124;compact&#124;**compressed**|

### Obsolete items
Obsolete items will be unistalled or deleted before install any related extension.
You can set 3 types of obsolete items: extension, file and folder.
For file and folder, use relative paths to the site root.
```xml
    <alledia>
        <obsolete>
            <extension type="plugin"
                       group="system"
                       element="oldshortelementname"/>

            <file>/components/com_mycomponent/oldfile.php</file>
            <file>/administrator/components/com_mycomponent/oldfile.php</file>

            <folder>/components/com_mycomponent/oldfolder</folder>
        </obsolete>
    </alledia>
```

### Publishing and reordering plugins automatically
If the main project is a plugin, the *publish* and *ordering*
can be used similar to their usage in [related extensions](#related-extensions).
```xml
    <alledia>
        <element publish="true" ordering="first">myplugin</element>
    </alledia>
```

### Installer views
These views are packed by the installer library and loaded by all extensions.
```
./views
|   |-- installer
|   |   |-- default.php
|   |   |-- default_info.php
|   |   |-- default_license.php
```

Your extensions can override any file and add a new one: body_*.
```
./views
|   |-- installer
|   |   |-- default_custom.php
```
