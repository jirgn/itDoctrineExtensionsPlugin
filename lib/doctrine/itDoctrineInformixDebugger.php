<?php
/**
 *
 * provides the posibility to dump informix explain statements into a specified file
 * @example
 * $debugger = new itDoctrineInformixDebugger($q->getConnection());
 * $debugger->start();
 *  $q->addFrom($className.' as r')
 *    ->leftJoin('r.ReferenceRecords rr')
 *    ->where('rr.id > 200');
 *  $results = $q->execute();
 *
 *  $debugger->stop();
 * @author jirgn
 *
 */
class itDoctrineInformixDebugger 	{

  protected $options = array();
  protected $connection = null;

  protected $defaultOptions = array(
  'trace_output_file' => '/tmp/ifx_explain.txt'
  );


  public function __construct($connection)	{
    $this->setOptions($this->defaultOptions);
    $this->connection = $connection;
  }

  public function start()	{
    $query = "SET EXPLAIN FILE TO '{$this->getOption('trace_output_file')}';";
    $query .= "SET EXPLAIN ON;";
    $this->connection->execute($query);
  }

  public function stop()	{
    $query .= 'SET EXPLAIN OFF; ';
    $this->connection->execute($query);
  }

  protected function getOptions()	{
    $this->executeTraceEnd();
  }

  protected function getOption($name)	{
    if(isset($this->options[$name]))	{
      return $this->options[$name];
    }
    return null;
  }

  protected function setOptions(array $options, $merge=true)	{
    if($merge)	{
      $this->options = array_merge($this->options, $options);
    }
    else 	{
      $this->options = $options;
    }
  }
}