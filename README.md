# itDoctrineExtensionsPlugin - Informix for Doctrine

This Plugin adds the following additional funtionality to the standard sfDoctrinePlugin.
Main Part is the Informix Driver Implementation.

*   Informix Driver

    with task informix:activate-logging.
    this is needed cause without active logging transactions are not present and therefore doctrine can not work.

*   Easily define different modelbuilder options for each used plugin

    When used - the ability to build the hole model once will break

    Therfore an Task: ''doctrine:build-plugin-model'' was created to build model only for given plugins

*   fix for doctrine bug http://www.doctrine-project.org/jira/browse/DC-740.

*   migrate only a given connection through a new task ''doctrine:migrate-connection''



## Requirements

* symfony framwork 1.3.x, 1.4.x
* sfDoctrinePlugin enabled
* sfPHPUnitPlugin (for running the tests) see https://github.com/makasim/sfPhpunitPlugin.git
* Informix 11.5.x


## Installation

Please **read this section carefully**, cause
when installing this plugin, you also need to modify the doctrine core classes. Otherwise the plugin will not work correctly.

### Common
Just clone the plugin to your to your projects plugin folder. The best way to do this depends on your usage of the vcs.
At the moment to create a submodule seems as the best way (even if its by far not perfect).

    $ cd <your_symfony_root_path> && git submodule init https://github.com/jirgn/itDoctrineExtenstionsPlugin.git

Alternatively is you user subversion as your favourite vcs, look at https://github.com/blog/966-improved-subversion-client-support

You then just activate the plugin.

    ./config/ProjectConfiguration.php
    class ProjectConfiguration extends sfProjectConfiguration
    {
      public function setup()
      {
        $this->enablePlugins(array(
            'itDoctrineExtensionsPlugin', //have to be registred before sfDoctrinePlugin
            'sfDoctrinePlugin',
            'sfPhpunitPlugin',
            ...
        ));
      }
    }

### Patching Doctrine

To make the Informix Driver work correctly it was necessary to patch the Doctrine Core. This is cause of crappy switches through drivernames in essential methods
Here is how to apply the patch.

You find the patch file in ./data/patch/doctrine.patch. Recognize, that the patches base dir is the projectroot of the symfony project.
So on linux or osx systems simply use the following command on bash.

    $ cd <your_symfony_root_path> && patch -p0 plugins/itDoctrineExtensionsPlugin/data/patch/doctrine.patch

I only tested patching versions 1.2.3 and 1.2.4, but this should be fine with the latest 1.3 or 1.4 symfony versions.

### Informix Environment


## Building a Simple Informix Project

First of all, when working with informix database, you should already know how to do it.
I figured out it is pretty hard to get a informix server running correctly and also the php-informx module is not bug free.
Nevertheless following resources will be helpfull if you write sql or stored procedures by yourself.
(hope with this plugin you will never neet this)
http://publib.boulder.ibm.com/infocenter/idshelp/v115/index.jsp
http://www.informix-zone.com/

## Class Prefixes

## Tasks

### doctrine:build-plugin-model

### informix:activate-logging

### doctrine:migrate-connection

## Known Issues
