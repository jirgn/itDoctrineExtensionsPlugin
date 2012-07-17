# itDoctrineExtensionsPlugin - Informix Driver for Doctrine

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

* PHP compiled with pdo-informix see http://www.php.net/manual/en/ref.pdo-informix.php
* symfony framwork 1.3.x, 1.4.x
* sfDoctrinePlugin enabled
* sfPHPUnitPlugin (for running the tests) see https://github.com/makasim/sfPhpunitPlugin.git
* IBM Informix 11.5.x Database


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

## Informix

First of all, when working with informix database, you should already know how to set it up.
I figured out it is pretty hard to get a informix server running correctly and also the php-informx module is not bug free.
Nevertheless following resources will be helpfull if you write sql or stored procedures by yourself.
(hope with this plugin you will never neet this)
http://publib.boulder.ibm.com/infocenter/idshelp/v115/index.jsp
http://www.informix-zone.com/

### Setup Database and Connection

The Informix Driver in this plugin works with a few restictions.
So first thing is, the **create database task is correctly not supported**, so you have to create your db by hand with a create database statement in standard Informix style.
You could also use the tool **dbaccess** to get you a gui on shell.

    in sql console
    CREATE DATABASE name_of_the_db;

It is essentioal to enable logging for this database to support transactions in your informix db.
This can be done with the task ''informix:activate-loggin''.
Because this task calls a informix tool called ''ontape'' it could be necessary to run this task as root user. this depends on your setup.
The informix bin dir has to be in your $PATH.

    $ symfony informix:activate-logging --db-name="<your_db_name>"

You need to configure your informix connection in standard symfony style.
The example below also uses differen connections, assuming you use also a mysql database.
Find further details for the pdo driver and the connection string on
http://php.net/manual/en/ref.pdo-informix.connection.php

    ./config/databases.yml
    all:
      other_doctrine_connection:
        class: sfDoctrineDatabase
        param:
          dsn:      mysql:host=localhost;dbname=informix
          username: root
          password:

      ifx_dummy_connection:
        class: sfDoctrineDatabase
        param:
          #dsn: 'informix:host=itools-informix32; service=3306;database=ifx_test;server=itools;protocol=onsoctcp;db_locale=de_DE.819;client_locale=de_DE.UTF8;'
          dsn: 'informix:host=itools-informix32; service=9088;database=ifx_test;server=itools;protocol=onsoctcp;'
          username: root
          password:

To work properly the following directories must be present.

    $ cd <symfony_project_root> && mkdir -p lib/form/doctrine/base && mkdir -p lib/filter/doctrine/base

### create Model

You specify your model in the standard symfony style. For example our model for the testcase is defined like

    config/doctrine/schema.yml

    # if working with differnet connections the corresponding connection for the model has to be specified here
    connection: ifx_dummy_connection

    SimpleRecord:
      columns:
        id:
          type: integer
          primary: true
          autoincrement: true
        varchar_field:
          type: string(25)
          default: default
        text_field:
          type: string
        integer_field:
          type: integer
          default: 0
        boolean_field:
          type: boolean
          default: false
        date_field:
          type: date
        time_field:
          type: time
        timestamp_field:
          type: timestamp

In the second step you have to generate the model classes.
There are two different ways to generate the model

#### standard classes

There is not much to say in this case. You just have to run the standard symfony task

    $ symfony doctrine:build-model

and you are done. you will find your classes in the lib/model dir as you know it.

#### prefixed classes and builder-options for plugins

In some special cases you will need to prefix your classes to mark them in some conventional way.
At least this was the case in the projekct i created this plugin for.

My Usecase for example was to use the (nearly) same model in two
different connections, so to say an internal and external system environment. I know this could be done by switching connection, but i
had to do some tweaks for the different scopes and so decided to build different models, so i can implement them differently.

To handle this, use an additional config file ''doctrine_ext.yml'', to configure the doctrine model_builder_options.

    config/doctrine_ext.yml
    model_builder_options:
      classPrefix:
        itDoctrineExtensionsPlugin: Test_
        #someOtherPlugin: someOtherPrefix
      baseClassName:
        itDoctrineExtensionsPlugin: sfDoctrineRecord
        #someOtherPlugin: someSpecialBaseClassForRecord

Possible options are:

*   baseClassesDirectory :: string (default : 'base')
*   baseClassName :: string (default : 'sfDoctrineRecord')
*   generateBaseClasses :: boolean (default : true)
*   generateTableClasses :: boolean (default : true)
*   packagesPrefix :: string (default : 'Plugin')
*   suffix :: string (default : '.class.php')

All the options can be specified per PluginName. **BE CAREFUL** this can lead to totally unexpected behaviour.
You should a have a clear idea about what to reach and handle the pros and cons not to go the default way.

So when you choose, you need this config part, you will see, that **the standard doctrine:build-model task will break**.
This is, cause the doctine model builder can only handle one config on time.
To get arround this use the new task

    $ symfony doctrine:build-plugin-model --plugin="name_of_plugin"

for every plugin that holds its own model definition.

Another drawback when using this special configuration is, that some of the doctrine stanard tasks whould not work anymore.
This is for example the case, for creating the database tables.
So here you have to generate the sql by using task

    $ symfony doctrine:build-sql

This will generate a sql file with initial schema in data/sql/schema.sql
Use this and create the tables directly in the informix db console or dbaccess.

In development process this should only be used once. Use migrations to handle further schema changes.
This will be nomally supportet through the standard doctrine:migrate task.

### testing if everything works

To enshure everything works correctly, there are some integrationlevel unittests.
Before they can be run, you have to do some tasks an config stuff. See this as a simple example how to use
this plugin.

#### setup configuration

Uncomment the schema in plugins/itDoctrineExtionsPlugin/config/doctrine/ifx_test_schema.yml so that it looks like

    # plugins/itDoctrineExtionsPlugin/config/doctrine/ifx_test_schema.yml
    connection: ifx_dummy_connection

    SimpleRecord:
      columns:
        id:
    ...

    ReferenceRecord:
      tableName: test_table_02
      columns:
    ...

    SelfReferenceRecord:
      columns:
    ...

configure your database.

    # config/databases.yml
    all:
      ifx_dummy_connection:
           class: sfDoctrineDatabase
           param:
             dsn: 'informix:host=<your_informix_host>; service=<your_port>; database=<your_db_name>; server=<your_server_name>; protocol=onsoctcp;'
             username: <your_ifx_user>
             password: <your_ifx_password>

make shure prefixes for tests are active

    # plugins/itDoctrineExtensionsPlugin/config/doctrine_ext.yml
    model_builder_options:
      classPrefix:
        itDoctrineExtensionsPlugin: Test_
      baseClassName:
        itDoctrineExtensionsPlugin: sfDoctrineRecord

generate your model

    $ symfony doctrine:build-plugin-model

create your db in informix dbaccess or console

    $ CREATE DATABASE <your_db_name>;

activate logging

    $ symfony informix:activate-logging --db-name="<your_db_name>"

generate initial schema sql

    $ symfony doctrine:build-sql

build schema directly in informix by executing generated file data/sql/schema.sql

setup missing folder structure

    $ cd <symfony_project_root> && mkdir -p lib/form/doctrine/base && mkdir -p lib/filter/doctrine/base

init sfPHPUnitPlugin

    $ symfony phpunit:init

run doctrine tests to enshure connection and simple operations work

    $ symfony phpunit:runtest plugins/itDoctrineExtensionsPlugin/test/phpunit/unit/doctrine/

run model test to enshure doctrine queries and references work

    $ php symfony phpunit:runtest plugins/itDoctrineExtensionsPlugin/test/phpunit/unit/model/

Enshure that the db is empty before running the tests. At this time some tests are skipped but this should be ok.


## multible connections

As you have seen above symfony/doctrine gives you the possiblity to work with different connections simultaniously.
Problem is - this does not realy work very well, if you have models that should work only on one connection, or you want to
have models with different default connection.

To give you a possibility to deal with this we build a special migrate task

    $ symfony doctrine:migrate-connection <name_of_connection>

to only migrate models for the given connection name.

**If you need the multible connections, you need to write your migrations by hand.**

## Tasks

This Plugin adds some additional Tasks that should help you working on your project.
See symfony help for more information

*   doctrine:build-plugin-model
*   informix:activate-logging
* doctrine:migrate-connection

## Known Issues

*   when using multible connections some standard doctrine tasks will break
*   doctrine:create database is not supported for informix
