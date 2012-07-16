<?php

/**
 * generates diff by plugin 
 * //TODO does not work properly with pluing prefix class setting @see doctrine_ext::config::classPrefix
 */
//class itDoctrineExtensionsPluginMigrationsDiffTask extends itDoctrineExtensionBaseBuildTask	{
//  
//  private $_migrationsPath = '',
//          $_pluginName = '';
//  
//  protected function configure()  {
//    $this->addOptions(array(
//      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment to load', null),
//      new sfCommandOption('plugin', null, sfCommandOption::PARAMETER_REQUIRED, 'The plugin name', null)
//    ));
//    $this->addArgument('from', sfCommandArgument::OPTIONAL, 'path to schema or model of starting point', '');
//    $this->addArgument('to', sfCommandArgument::OPTIONAL, 'path to schema or model of targeting point', '');
//
//    $this->namespace = 'doctrine';
//    $this->name = 'generate-plugin-migrations-diff';
//    $this->briefDescription = 'generates migrations for given plugin';
//
//    $this->detailedDescription = <<<EOF
//The [activate|INFO] enables logging on given informix database:
//
//  [./symfony doctrine:generate-plugin-migrations-diff|INFO]
//EOF;
//  }
//  
//  protected function execute($arguments = array(), $options = array())  {
//    if(! isset($options['plugin']) || empty($options['plugin']))	{
//      throw new RuntimeException('madatory option plugin not set');
//    }
//    $this->_pluginName = $options['plugin'];
//    
//    //Hack fake connection -
//    $databaseManager = new sfDatabaseManager($this->configuration);
//    $fs = new sfFilesystem();
//    $fs->mkdirs($this->getMigrationsPath());
//    $migration = new Doctrine_Migration($this->getMigrationsPath());
//    $from = $this->getFromPath($arguments);
//    $to = $this->getToPath($arguments);
//    $diff = new Doctrine_Migration_Diff($from, $to, $migration);
//    $changes = $diff->generateMigrationClasses();
//    var_dump($from, $to);
//    $numChanges = count($changes, true) - count($changes);
//
//    if ( ! $numChanges) {
//        throw new Doctrine_Task_Exception('Could not generate migration classes from difference');
//    } else {
//        $this->logSection('info','Generated migration classes successfully from difference');
//    }
//  }
//  
//  private function getMigrationsPath()	{
//    if(!$this->_migrationsPath)	{
//      $parts[] = sfConfig::get('sf_lib_dir');
//      $parts[] = 'migration';
//      $parts[] = 'doctrine';
//      $parts[] = $this->_pluginName;
//      $this->_migrationsPath = implode(DIRECTORY_SEPARATOR, $parts);
//    }
//    return $this->_migrationsPath;
//  }
//  
//  private function getFromPath($arguments)	{
//    if(isset($arguments['from']) && !empty($arguments['from']))	{
//      $path = $arguments['from'];
//      if(! file_exists($path))	{
//        throw new RuntimeException('dir or file does not exist in filesystem');
//      }
//      return $path;
//    }
//    //default from is current model 
//    $parts[] = sfConfig::get('sf_lib_dir');
//    $parts[] = 'model';
//    $parts[] = 'doctrine';
//    $parts[] = $this->_pluginName;
//    return implode(DIRECTORY_SEPARATOR, $parts);
//  }
//  
//  private function getToPath($arguments)	{
//    if(isset($arguments['to']) && !empty($arguments['to']))	{
//      $path = $arguments['to'];
//      if(! file_exists($path))	{
//        throw new RuntimeException('dir or file does not exist in filesystem');
//      }
//      return $path;
//    }
//    //default to is current scheme 
//    $parts[] = sfConfig::get('sf_plugins_dir');
//    $parts[] = $this->_pluginName;
//    $parts[] = 'config';
//    $parts[] = 'doctrine';
//    $yamlSchemePath = implode(DIRECTORY_SEPARATOR, $parts);
//    $schemafile = $this->prepareSchemaFileInternal($yamlSchemePath, $this->_pluginName);
//    return $schemafile;
//  }
//}