<?php
class itDoctrineExtensionsMigrateTask extends sfDoctrineBaseTask	{
  protected function configure()  {
    $this->addArguments(array(
      new sfCommandArgument('connection', sfCommandArgument::REQUIRED, 'The connection to use for migration'),
      new sfCommandArgument('version', sfCommandArgument::OPTIONAL, 'The version to migrate to'),
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('up', null, sfCommandOption::PARAMETER_NONE, 'Migrate up one version'),
      new sfCommandOption('down', null, sfCommandOption::PARAMETER_NONE, 'Migrate down one version'),
      new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Do not persist migrations'),
    ));

    $this->namespace = 'doctrine';
    $this->name = 'migrate-connection';
    $this->briefDescription = 'migrates database for specified connection to given version';

    $this->detailedDescription = <<<EOF
The [migrate-connection|INFO] migrates database for specified connection to given version.
--dry-run will be ignored if no db support for transactions on schemas.

  [./symfony doctrine:migrate-connection|INFO]
EOF;

  }
   /**
    * inspired (and nearly) copied from @see sfDoctrineMigrateTask
    * @see sfTask::execute()
    */
  protected function execute($arguments = array(), $options = array())	{
    $databaseManager = new sfDatabaseManager($this->configuration);
    sfContext::createInstance($this->configuration);

    $config = $this->getCliConfig();
    $connection = $arguments['connection'];
    $migration = new Doctrine_Migration($config['migrations_path'].DIRECTORY_SEPARATOR.$connection, $connection);
    $from = $migration->getCurrentVersion();

    if (is_numeric($arguments['version']))
    {
      $version = $arguments['version'];
    }
    else if ($options['up'])
    {
      $version = $from + 1;
    }
    else if ($options['down'])
    {
      $version = $from - 1;
    }
    else
    {
      $version = $migration->getLatestVersion();
    }

    if ($from == $version)
    {
      $this->logSection('doctrine', sprintf('Already at migration version %s', $version));
      return;
    }

    $this->logSection('doctrine', sprintf('Migrating from version %s to %s%s', $from, $version, $options['dry-run'] ? ' (dry run)' : ''));
    try
    {
      $migration->migrate($version, $options['dry-run']);
    }
    catch (Exception $e)
    {
    }

    // render errors
    if ($migration->hasErrors())
    {
      if ($this->commandApplication && $this->commandApplication->withTrace())
      {
        $this->logSection('doctrine', 'The following errors occurred:');
        foreach ($migration->getErrors() as $error)
        {
          $this->commandApplication->renderException($error);
        }
      }
      else
      {
        $this->logBlock(array_merge(
        array('The following errors occurred:', ''),
        array_map(create_function('$e', 'return \' - \'.$e->getMessage();'), $migration->getErrors())
        ), 'ERROR_LARGE');
      }

      return 1;
    }

    $this->logSection('doctrine', 'Migration complete');
  }
}