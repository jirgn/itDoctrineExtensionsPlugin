<?php
class ConfigurationTestCase extends sfBasePhpunitTestCase {
  
  public function _start()	{
    //var_dump(sfConfig::get('sf_app_cache_dir'));
  }
  
  
  public function testLoadModelNoneCache(){
//    $this->markTestSkipped();
    itDoctrineExtensionsPluginConfiguration::unloadModelFiles();
    sfConfig::set('sf_debug', true);
    $cacheFilePath = itDoctrineExtensionsPluginConfiguration::getModelCachePath();
    if(file_exists($cacheFilePath)){
      unlink($cacheFilePath);
    }
    $this->assertFileNotExists($cacheFilePath);
    
    $timer = new sfTimer();
    $timer->startTimer();
    $files = itDoctrineExtensionsPluginConfiguration::loadModelFiles();
    $this->assertTrue(count($files) >0, 'No classes were loaded.');
  }

  public function testLoadModelCache(){
//    $this->markTestSkipped();
    itDoctrineExtensionsPluginConfiguration::unloadModelFiles();
    sfConfig::set('sf_debug', false);
    $cacheFilePath = itDoctrineExtensionsPluginConfiguration::getModelCachePath();
    if(file_exists($cacheFilePath)){
      unlink($cacheFilePath);
    }
    $this->assertFileNotExists($cacheFilePath);
    
    //first call initializes the cache
    $timer = new sfTimer();
    $timer->startTimer();
    $files = itDoctrineExtensionsPluginConfiguration::loadModelFiles();
    $firstCallTime = $timer->getElapsedTime();
    //enshure loads are successfull
    $this->assertTrue(count($files) >0, 'No classes were loaded.');
    $this->assertFileExists($cacheFilePath);

    //a few cache calls (first call is against filecache others against static var)
    itDoctrineExtensionsPluginConfiguration::unloadModelFiles();
    foreach(range(1, 4) as $callIndex)	{
      $timer = new sfTimer();
      $timer->startTimer();
      $files = itDoctrineExtensionsPluginConfiguration::loadModelFiles();
      $callTime = $timer->getElapsedTime();
      //enshure loads are successfull
      $this->assertTrue(count($files) >0, 'No classes were loaded.');
      $this->assertLessThan($firstCallTime, $callTime);
    }
  }
  
}