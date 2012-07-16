# itDoctrineExtenstionsPlugin - Informix for Doctrine

This Plugin adds the followin additional funtionality to the standard sfDoctrinePlugin.
Main Part is the Informix Driver Implementation

* Informix Driver
* Ability to easily define different modelbuilder options for different plugins
  When used - the ability to build the hole model once will break.
  Therfore an
  Task: doctrine:build-plugin-model
  was created to build model only for given plugins
* fix for doctrine bug http://www.doctrine-project.org/jira/browse/DC-740.
* Task: informix:activate-logging for logging activation on informix databases.
  this is needed cause without active logging transactions are not present and therefore doctrine can not work.

## Requirements

* symfony framwork 1.3.x, 1.4.x
* sfDoctrinePlugin enabled
* sfPHPUnitPlugin (for running the tests)
* Informix 11.5.x


## Installation

### Common

### Patching Doctrine



## Informix Environment

