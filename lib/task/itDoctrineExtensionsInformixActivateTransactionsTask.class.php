<?php
class itDoctrineExtensionsInformixActivateTransactionsTask extends sfTask	{

  protected function configure()  {
    $this->addOptions(array(
      new sfCommandOption('db-name', null, sfCommandOption::PARAMETER_REQUIRED, 'The informix database name', null)
    ));

    $this->namespace = 'informix';
    $this->name = 'activate-logging';
    $this->briefDescription = 'Activates the Logging of given database. Needed to enable Transactions';

    $this->detailedDescription = <<<EOF
The [activate|INFO] enables logging on given informix database:

  [./symfony informix:activate-logging|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())  {
    $dbName = $options['db-name'];
    $this->logSection('informix', 'activating logging for ' . $dbName);

    $cmd = $this->getLoggingCmd($dbName);
    $fs = new sfFilesystem();
    $fs->execute($cmd, null, array($this, 'onActivateLoggingError') );
  }

  public function onActivateLoggingError()	{
    throw new Exception('could not activate logging. Try "'.$this->getLoggingCmd().'" in your Terminal as root');
  }

  private function getLoggingCmd($dbName=false)	{
    if(!$dbName)	{
      $dbName = "<db-name>";
    }
    return sprintf('ontape -s -L 0 -U %s -t /dev/null', $dbName);
  }
}