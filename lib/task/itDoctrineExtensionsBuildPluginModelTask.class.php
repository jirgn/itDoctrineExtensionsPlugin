<?php
/**
 * interactive-tools 2010 (c)
 */
require_once sfConfig::get('sf_lib_dir') . '/vendor/symfony/lib/plugins/sfDoctrinePlugin/lib/task/sfDoctrineBuildModelTask.class.php';

class itDoctrineExtensionsBuildPluginModelTask extends itDoctrineExtensionBaseBuildTask {

  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addOptions(array(
    new sfCommandOption('plugin', null, sfCommandOption::PARAMETER_REQUIRED, 'The plugin name', 'itDoctrineExtensionsPlugin'),
    new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
    new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
    ));

    $this->namespace = 'doctrine';
    $this->name = 'build-plugin-model';
    $this->briefDescription = 'Creates classes for the current model of given plugin';

    $this->detailedDescription = <<<EOF
The [doctrine:build-plugin-model|INFO] task creates model classes from the schema files of the given plugin only:

  [./symfony doctrine:build-plugin-model|INFO]

The task read the schema information in [plugins/PLUGIN/config/doctrine/*.yml|COMMENT]
from the defined plugin.

The model classes files are created in [lib/model/doctrine|COMMENT].

This task never overrides custom classes in [lib/model/doctrine|COMMENT].
It only replaces files in [lib/model/doctrine/base|COMMENT].
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())  {
    $this->dispatcher->notify(new sfEvent($this, 'command.pre_build_plugin_model', $options));

    $this->logSection('doctrine', 'generating model classes for plugin ' . $options['plugin']);

    $config = $this->getCliConfig();
    $builderOptions = $this->configuration->getPluginConfiguration('sfDoctrinePlugin')->getModelBuilderOptions();

    $classPrefix = isset($builderOptions['classPrefix']) ? $builderOptions['classPrefix'] : '';
    $stubFinder = sfFinder::type('file')->prune('base')->name($classPrefix.'*'.$builderOptions['suffix']);
    $before = $stubFinder->in($config['models_path']);
    $schema = $this->prepareSchemaFileInternal($config['yaml_schema_path'], $options['plugin']);

    $import = new Doctrine_Import_Schema();
    $import->setOptions($builderOptions);
    $import->importSchema($schema, 'yml', $config['models_path']);

    // markup base classes with magic methods
    foreach (sfYaml::load($schema) as $model => $definition)
    {
      $file = sprintf('%s%s/%s/%sBase%s%s', $config['models_path'], isset($definition['package']) ? '/'.substr($definition['package'], 0, strpos($definition['package'], '.')) : '', $builderOptions['baseClassesDirectory'], $classPrefix, $model, $builderOptions['suffix']);
      $code = file_get_contents($file);

      // introspect the model without loading the class
      if (preg_match_all('/@property (\w+) \$(\w+)/', $code, $matches, PREG_SET_ORDER))  {
        $properties = array();
        foreach ($matches as $match)
        {
          $properties[$match[2]] = $match[1];
        }
        $typePad = max(array_map('strlen', array_merge(array_values($properties), array($model))));
        $namePad = max(array_map('strlen', array_keys(array_map(array('sfInflector', 'camelize'), $properties))));
        $setters = array();
        $getters = array();
        foreach ($properties as $name => $type)  {
          $camelized = sfInflector::camelize($name);
          $collection = 'Doctrine_Collection' == $type;

          $getters[] = sprintf('@method %-'.$typePad.'s %s%-'.($namePad + 2).'s Returns the current record\'s "%s" %s', $type, 'get', $camelized.'()', $name, $collection ? 'collection' : 'value');
          $setters[] = sprintf('@method %-'.$typePad.'s %s%-'.($namePad + 2).'s Sets the current record\'s "%s" %s', $model, 'set', $camelized.'()', $name, $collection ? 'collection' : 'value');
        }

        // use the last match as a search string
        $code = str_replace($match[0], $match[0].PHP_EOL.' * '.PHP_EOL.' * '.implode(PHP_EOL.' * ', array_merge($getters, $setters)), $code);
        file_put_contents($file, $code);
      }
    }

    $properties = parse_ini_file(sfConfig::get('sf_config_dir').'/properties.ini', true);
    $tokens = array(
      '##PACKAGE##'    => isset($properties['symfony']['name']) ? $properties['symfony']['name'] : 'symfony',
      '##SUBPACKAGE##' => 'model',
      '##NAME##'       => isset($properties['symfony']['author']) ? $properties['symfony']['author'] : 'Your name here',
      ' <##EMAIL##>'   => '',
      "{\n\n}"         => "{\n}\n",
    );

    // cleanup new stub classes
    $after = $stubFinder->in($config['models_path']);
    $this->getFilesystem()->replaceTokens(array_diff($after, $before), '', '', $tokens);

    // cleanup base classes
    $baseFinder = sfFinder::type('file')->name($classPrefix.'Base*'.$builderOptions['suffix']);
    $baseDirFinder = sfFinder::type('dir')->name('base');
    $this->getFilesystem()->replaceTokens($baseFinder->in($baseDirFinder->in($config['models_path'])), '', '', $tokens);

    $this->reloadAutoload();
  }

}