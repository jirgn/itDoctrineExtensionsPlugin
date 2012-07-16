<?php
abstract class itDoctrineExtensionBaseBuildTask extends sfDoctrineBuildModelTask	{
  
  
  protected function prepareSchemaFileInternal($yamlSchemaPath, $pluginName)  {
    $models = array();
    $finder = sfFinder::type('file')->name('*.yml')->sort_by_name()->follow_link();

    $plugin = $this->configuration->getPluginConfiguration($pluginName);
    foreach ($finder->in($plugin->getRootDir().'/config/doctrine') as $schema)	{
      $pluginModels = (array) sfYaml::load($schema);
      $globals = $this->filterSchemaGlobals($pluginModels);

      foreach ($pluginModels as $model => $definition)  {
        $definition = $this->canonicalizeModelDefinition($model, $definition);
        $definition = array_merge($globals, $definition);

        // merge this model into the schema
        $models[$model] = isset($models[$model]) ? sfToolkit::arrayDeepMerge($models[$model], $definition) : $definition;

        // the first plugin to define this model gets the package
        if (!isset($models[$model]['package']))  {
          $models[$model]['package'] = $plugin->getName().'.lib.model.doctrine';
        }

        if (!isset($models[$model]['package_custom_path']) && 0 === strpos($models[$model]['package'], $plugin->getName()))  {
          $models[$model]['package_custom_path'] = $plugin->getRootDir().'/lib/model/doctrine';
        }
      }
    }

    // create one consolidated schema file
    $file = realpath(sys_get_temp_dir()).'/doctrine_schema_'.rand(11111, 99999).'.yml';
    $this->logSection('file+', $file);
    file_put_contents($file, sfYaml::dump($models, 4));

    return $file;
  }
}