<?php
/**
 * 
 * TODO check if preSerialize / postUnserialise are valid to have correct values in cache
 *
 * @package			itDoctrineExtensionsPlugin
 * @subpackage	listener
 * @author			jirgn_itools
 * @version			SVN: $Id: $/
 *
 */
class itDoctrineInformixUtf8IsoConversion extends Doctrine_Record_Listener	{
  
  /**
   * (non-PHPdoc)
   * @see Doctrine_Record_Listener::preHydrate()
   */
  public function preHydrate(Doctrine_Event $event) {
    $table = $event->getInvoker();
    $data = $event->data;
    $converted = self::getConverted($data, $table, 'ISO-8859-1', 'UTF-8');
    foreach($converted as $fieldName => $value)	{
      $data[$fieldName] = $value;
    }
    $event->data = $data;
  }
  
  /**
   * (non-PHPdoc)
   * @see Doctrine_Record_Listener::preInsert()
   */
  public function preInsert(Doctrine_Event $event) {
    $this->convertUtf8ToIso($event->getInvoker());
  }
  
  /**
   * (non-PHPdoc)
   * @see Doctrine_Record_Listener::preUpdate()
   */
  public function preUpdate(Doctrine_Event $event) {
    $this->convertUtf8ToIso($event->getInvoker());
  }

  /**
   * (non-PHPdoc)
   * @see Doctrine_Record_Listener::postInsert()
   */
  public function postInsert(Doctrine_Event $event) {
    $this->convertIsoToUtf8($event->getInvoker());
  }
  
  /**
   * (non-PHPdoc)
   * @see Doctrine_Record_Listener::postUpdate()
   */
  public function postUpdate(Doctrine_Event $event) {
    $this->convertIsoToUtf8($event->getInvoker());
  }
  
  /**
   * 
   * converts string based record data from utf8 to iso 8859-1
   * @param Doctrine_Record $record
   */
  private function convertUtf8ToIso(Doctrine_Record $record)	{
    $convertedData = self::getConverted($record->getData(), $record->getTable(), 'UTF-8', 'ISO-8859-1');
    foreach($convertedData as $fieldName => $value)	{
      $record->set($fieldName, $value, false);
    }
  }
  
  /**
   * 
   * converts string based record data from iso 8859-1 to utf8
   * @param Doctrine_Record $record
   */
  private function convertIsoToUtf8(Doctrine_Record $record)	{
    $convertedData = self::getConverted($record->getData(), $record->getTable(), 'ISO-8859-1', 'UTF-8');
    foreach($convertedData as $fieldName => $value)	{
      $record->set($fieldName, $value, false);
    }
  }

  /**
   * 
   * Enter description here ...
   * @param array $data record data corresponding to Doctrine Table defintion
   * @param Doctrine_Table $table
   * @param string $fromEncoding
   * @param string $toEncoding
   */
  private static function getConverted(array $data, Doctrine_Table $table, $fromEncoding, $toEncoding)	{
    $convertedData = array();
    foreach($table->getColumns() as $fieldName => $definition)	{
      if($definition['type'] === 'string')	{
        if(($value = $data[$fieldName]) && is_string($value))	{
          $convertedData[$fieldName] = mb_convert_encoding($value, $toEncoding, $fromEncoding);
        }
      }
    }
    return $convertedData;
  }
}