# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

ShackBuilder is a shared Phing (v3.x, PHP >=8.1) build system for Joomlashack Joomla extensions. It does not build itself. Each extension repo copies `src/clients/build.xml` into its root and adds a `build.properties` with `builder.path=/path/to/ShackBuilder`. Phing then imports `src/build.xml` from this repo. There is no test suite. To exercise a change, run phing inside a client extension repo.

## Commands (run from a client extension repo, not here)

```shell
phing                 # same as `phing build`: package the current manifest version to ./packages/<element>_<license>_<version>.zip
phing build-new       # prompt for release type, bump version + creationDate (including related extensions), then package
phing set-version     # bump version/creationDate without packaging
phing pre-build       # only compile SCSS (`sass` CLI) and minify/merge JS (JShrink)
phing merge-languages # merge free + .pro language files into ./packages/dev/...
phing standards       # normalize composer.json / manifest formatting (StandardsFixesTask)
phing -l              # list targets
```

Set `builder.debug=1` to keep `packages/tmp` and dump the properties (`package.properties`, `standards.properties`).

## Architecture

- **Entry flow:** client `build.xml` → `src/build.xml` (or `src/${builder.type}.xml`). This loads `global.properties` from this repo (machine-wide defaults; gitignored, template in `global.dist.properties`), registers the custom tasks from `src/tasks/`, reads the client's `composer.json` into `composer.*` properties, finds the manifest (`<element-short>.xml`, then `<type>_<element-short>.xml`, then `templateDetails.xml`), and loads it as `manifest.*` properties via `xmlproperty`. It then imports every file in `src/projects/*.xml`: `packager.xml` (most of the logic), `development.xml`, and `releaser.xml` (mostly TODO stubs).
- **Configuration comes from the client's files.** `composer.json` `extra` keys (`element`, `element-short`, `name`, `package-license`, …) and the manifest's custom `<alledia>` block (`relatedExtensions`, `include`, `minify`, `scss`, `obsolete`) drive the build. README.md documents these formats in full.
- **Free/Pro merge:** for `package-license=pro`, the free repo's `src/` (located via `project.<composer.extra.name>.path`) is copied into `packages/tmp` first. The pro `src/` is copied over it, and `*.pro.ini` language files are merged in.
- **Recursive builds:** related extensions and includes are built by shelling out to `phing -f <other repo>/build.xml build|copy -Dpackages.tmp.path=...`. Passing `packages.tmp.path` is how a sub-build knows it is not "building directly" (`buildingdirectly=0`), so it skips zipping. Other repos are found through `project.<Name>.path` properties. `src/build-include.xml` is the lightweight entry used by include-only repos (target `copy`).
- **Parallelism:** when the `pcntl` extension is loaded, related extensions and includes are built through `ForeachParallelTask`, a modified copy of Phing's foreach, with `builder.thread.count` threads.
- **Custom tasks** (`src/tasks/*.php`): classes in the `ShackBuilder\Tasks` namespace, extending `Phing\Task`, PSR-4 autoloaded (via this repo's own `vendor/autoload.php`, registered with `<autoloader>` in `src/build.xml`) and registered with `<taskdef classname="ShackBuilder\Tasks\XxxTask">`. Shared helpers (the composer-type ↔ prefix maps, `getProperty`/`setProperty`, XML helpers) live in `TraitShack.php`. Running `composer install` inside this repo is required for the autoloader to exist.
- If a manifest has a `<scriptfile>`, ShackInstaller's `src/` is copied into the package. For components it goes into `admin/`. Templates take a separate path (`build-template`/`build-assets` in packager.xml).

## Releasing ShackBuilder

The builder version is hard-coded in three places, and all of them must be bumped together: `src/build.xml`, `src/build-include.xml`, and `src/clients/build.xml` (`builder.version` / `builder.version.client`). If the required tool versions change, update the Requirements section of README.md too.
