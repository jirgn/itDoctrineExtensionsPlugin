<?php


/**
 * Base class for environment aware migration.
 * Only use these function: upEnv(); , downEnv();
 * You need to init the valid environments: getValidEnvironments()
 * @author Ramon
 *
 */
abstract class itDoctrine_Migration_Environment_Base extends Doctrine_Migration_Base {
  
  protected 
    $skipMigration = false;
    
  public function up(){
    $this->checkEnvironment();
    if($this->shouldMigrationSkipped()){
      return;
    }
    $this->upEnv();
  }
  
  public function down(){
    $this->checkEnvironment();
    if($this->shouldMigrationSkipped()){
      return;
    }
    $this->downEnv();
  }
  
  /**
   * Upgrades environmental aware to the new Version.
   */
  abstract function upEnv();
  
   /**
   * Downgrades environmental aware to the new Version.
   */
  abstract function downEnv();
  
  /**
   * Gets the valid environements.
   * @return array
   */
  abstract function getValidEnvironments();
  
  /**
   * Checks the environment.
   */
  protected function checkEnvironment(){
    $context = sfContext::getInstance();
    $env = $context->getConfiguration()->getEnvironment();
    $validEnvironments = $this->getValidEnvironments();
    if(array_search($env, $validEnvironments) === false){
      print('Skipped migration for the current environment: '. $env."\n");
      print('Valid environments: '. implode(',', $validEnvironments)."\n");
      $this->skipMigration = true;
    }
  }
  
  /**
   * Tests if the migration should be skipped.
   * @return boolean
   */
  protected function shouldMigrationSkipped(){
    return $this->skipMigration;
  }
  


}