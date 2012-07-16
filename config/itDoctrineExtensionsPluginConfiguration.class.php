<?php
/**
 * interactive-tools 2010 (c)
 */

/**
 * Configures Plugin
 *
 * @category plugin
 * @package itDoctrineExtionsPlugin
 * @subpackage config
 * @version SVN: $Id$
 * @author jirgn <juergen.messner@interactive-tools.de>
 *
 */
class itDoctrineExtensionsPluginConfiguration extends sfPluginConfiguration {

  protected $modelBuilderOptions = array();
  protected $conflictingPlugins = array();
  protected $builderOptionsChecked = false;
  protected $buildPluginName = false;

  private static $loaded = false;
  private static $modelCachePath = false;
  private static $modelClassNames = array();
  private static $conversionListenerAdded = false;

  /**
   * @see sfPluginConfiguration::initialize
   */
  public function initialize()  {
    if(!in_array('sfDoctrinePlugin', sfApplicationConfiguration::getActive()->getPlugins())) {
      throw new sfConfigurationException($name.' needs sfDoctrinePlugin to be activated. Check your ProjectConfiguration.class');
    }
    $this->_initByConfigFile();
    $this->dispatcher->connect('command.pre_build_plugin_model', array($this, 'onPreBuildPluginModel'));
    $this->dispatcher->connect('doctrine.filter_model_builder_options', array($this, 'onFilterModelBuilderOptions'));
    $this->dispatcher->connect('doctrine.configure', array($this, 'onDoctrineConfigure'));
  }

  /**
   * Handler for event 'doctrine.configure'
   * registres Informix
   * @param sfEvent $e
   */
  public function onDoctrineConfigure(sfEvent $e) {
    $this->loadModelFiles();
    
    /* @var $manager Doctrine_Manager */
    $manager = $e->getSubject();
    $manager->registerConnectionDriver('informix', 'Doctrine_Connection_Informix');
    
    //TODO @ramon
//    if(!self::$conversionListenerAdded)	{
//      $manager->addRecordListener(new itDoctrineInformixUtf8IsoConversion());
//      self::$conversionListenerAdded = true;
//    }
  }

  /**
   * Handler for doctrine.filter_model_builder_options event
   * overrides modelbuilder options for doctine if configured
   * @param sfEvent $e
   * @param unknown_type $options
   */
  public function onFilterModelBuilderOptions(sfEvent $e, $options) {
    if($this->_hasBuilderOptionsConflict()) {
      $msg = sprintf('can not build model, more than one of the following plugins %s are active. Activate only one for model-building or use doctrine:build-plugin-model task', print_r($this->conflictingPlugins, true));
      throw new sfConfigurationException($msg);
    }
    return array_merge($options, $this->modelBuilderOptions);
  }

  /**
   * Handler for command.pre_build_plugin_model
   * @param sfEvent $e
   */
  public function onPreBuildPluginModel(sfEvent $e)  {
    $taskOptions = $e->getParameters();
    $this->buildPluginName = $taskOptions['plugin'];
    $this->builderOptionsChecked = false;
  }

  /**
   * init modelbuilderoptions by 'doctrine_ext.yml' config file
   * @throws sfConfigurationException
   */
  private function _initByConfigFile() {
    $configFiles = $this->configuration->getConfigPaths('config/doctrine_ext.yml');
    $config = sfRootConfigHandler::getConfiguration($configFiles);
    foreach ($config as $name => $value) {
      sfConfig::set('it_doctrine_ext_'.$name, $value);
      if($name === 'model_builder_options')  {
        $this->modelBuilderOptions = $value;
      }
      else  {
        throw new sfConfigurationException('config values set in doctrine_ext.yml are not handeld yet');
      }
    }
  }

  /**
   * detects multidefintions (for plugin) and tries to get a clean modelbuilderoptions array
   * by the activated plugin or the one triggert directly on build-plugin-model task
   */
  private function _cleanModelBuilderOptions()  {
    if(! $this->builderOptionsChecked)  {
      $conflictingPlugins = array();
      $activePlugins = sfApplicationConfiguration::getActive()->getPlugins();
      $newOptions = array();
      foreach ($this->modelBuilderOptions as $optionName => $optValue) {
        if(is_array($optValue)) {
          if($this->buildPluginName && in_array($this->buildPluginName, array_keys($optValue))) {
            $newOptions[$optionName] = $optValue[$this->buildPluginName];
            continue;
          }
          $relPlugins = array_intersect($activePlugins, array_keys($optValue));
          if(count($relPlugins) === 1)  {
            $newOptions[$optionName] = $optValue[array_pop($relPlugins)];
          }
          else  {
            $conflictingPlugins = array_merge($conflictingPlugins, $relPlugins);
          }
        }
        else  {
          $newOptions[$optionName] = $optValue;
        }
      }
      $this->conflictingPlugins = $conflictingPlugins;
      $this->modelBuilderOptions = $newOptions;
      $this->builderOptionsChecked = true;
    }
  }

  /**
   *
   * @return boolean indicating if has builderOptions conflict
   */
  private function _hasBuilderOptionsConflict() {
    if( ! $this->builderOptionsChecked )  {
      $this->_cleanModelBuilderOptions();
    }
    if($this->buildPluginName)  {
      return false;
    }

    return (boolean) count($this->conflictingPlugins);
  }

  /**
   * bruteforce loading of modelfiles for Doctrine to work with multible connections
   * @see Doctrine BUG http://www.doctrine-project.org/jira/browse/DC-740
   * @return array with modelclassnames;
   */
  public static function loadModelFiles() {
    if(!self::$loaded) {
      $isDebug = sfConfig::get('sf_debug', true);
      $fileName = self::getModelCachePath();
      if(!$isDebug && file_exists($fileName))  {
          require $fileName;
          self::$modelClassNames = $modelClassNames;
      }
      else  {
        self::initModelFiles();
        if (!$isDebug)   {
          $content = '<?php $modelClassNames = array("'.implode('", "', self::$modelClassNames).'"); ?>';
          file_put_contents($fileName, $content);
        }
      }
      foreach (self::$modelClassNames as $file) {
        $class_name = str_replace('.class.php', '', basename($file));
        Doctrine_Core::loadModel($class_name, $file);
      }
      self::$loaded = true;
    }
    return self::$modelClassNames;
  }
  
  /**
   * 
   * function to reset internal state of modelfile loading (does not unload Doctrine Model)
   * used for test purposes
   */
  public static function unloadModelFiles()	{
    self::$modelClassNames = array();
    self::$loaded = false;
  }
    
  /**
   * 
   * @return string path to modelFileCache
   */
  public static function getModelCachePath()	{
    return sfConfig::get('sf_app_cache_dir').'/modelClassNames.php';
  }
  
    
  private static function initModelFiles($pluginName=false)	{
    if(!$pluginName)  {
      foreach(sfApplicationConfiguration::getActive()->getPlugins() as $pluginName) {
        self::initModelFiles($pluginName);
      }
    }
    $path = sfConfig::get('sf_lib_dir') . '/model/doctrine/' . $pluginName;
    $files = sfFinder::type('file')
    ->maxdepth(0)
    ->not_name('*Table.class.php')
    ->name('*.class.php')
    ->in($path);
    foreach ($files as $file) {
      self::$modelClassNames[] = $file;
    }
    return self::$modelClassNames;
  }
  
}