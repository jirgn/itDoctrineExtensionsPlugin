<?php

/**
 *
 * Abstract TestCase with some Helperfunctions and common TestCases
 * @author jirgn
 *
 */
abstract class ItDoctrineExtensions_AbstractRecordTestCase extends sfBasePhpunitTestCase {

  /**
   * (non-PHPdoc)
   * @see sfBasePhpunitTestCase::_end()
   */
  protected function _end() {
    self::deleteAllRecords();
  }

  /**
   *
   * creates Instance of Modelclass corresponding to actual TestCase
   * @param boolean $withReferences
   * @return sfDoctrineRecord
   */
  protected abstract function create($withReferences=false, $persisted=false);

  public function testSimpleSave()  {
//    $this->markTestSkipped();
    $record = $this->create(false);
    $this->assertTrue($record->isModified(), 'record should be in modified state');
    $record->save();
    $this->assertFalse($record->isModified(), 'record should NOT be in modified state');

    $loaded = Doctrine::getTable(get_class($record))->find($record->getId());
    foreach(array_keys($record->getData()) as $key)	{
      $this->assertEquals($record->get($key), $loaded->get($key), 'values of stored and loaded record fields have to be same.');
    }
    
    
    $this->assertEquals($record->getData(), $loaded->getData(), 'saved and loaded should be same data');

  }

  public function testFindRecord()  {
//    $this->markTestSkipped();
    $record = $this->create(false, true);
    $id = $record->getId();
    $className = get_class($record);
    $tableName = $record->getTable()->getTableName();

    $loaded = Doctrine::getTable($className)->find($id);
    $this->assertEquals($record->getData(), $loaded->getData(), 'saved and loaded record are not same');
  }

  public function testQueryCountFunction()  {
//    $this->markTestSkipped();
    $records = array();
    $totalRecords = 20;
    for($i=0; $i<$totalRecords; $i++)  {
      $records[] = $this->create(false, true);
    }
    $className = get_class($records[0]);
    $q = Doctrine_Query::create()
    ->select('COUNT(c.id) as totalCount')
    ->from($className.' as c');
    $result = $q->execute();

    $count = (integer) $result->getFirst()->totalCount;
    $this->assertEquals(20, $count, 'count funciton did not work as expected');
  }

  public function testSimpleWhere() {
//    $this->markTestSkipped();
    $startId = self::resetNextSrId();
    $records = array();
    $totalRecords = 20;
    for($i=0; $i<$totalRecords; $i++)  {
      $records[] = $this->create(false, true);
    }

    $offsetId = $startId + 10;
    $className = get_class($records[0]);
    $q = Doctrine_Query::create()
    ->select('c.id')
    ->from($className . ' as c')
    ->where('c.id > '.$offsetId);
    $result = $q->execute();
    foreach($result as $r)  {
      $this->assertTrue($r->getId() > $offsetId, 'simple where does not work');
    }
  }

public function testLimitAndOffsetQueries() {
//    $this->markTestSkipped();
    $records = array();
    $totalRecords = 20;
    for($i=0; $i<$totalRecords; $i++)  {
      $records[] = $this->create(false, true);
    }
    $className = get_class($records[0]);

    $limit = 15;
    $q = Doctrine_Query::create()
    ->select()
    ->from($className . ' as c')
    ->limit($limit);
    $result = $q->execute();
    $this->assertEquals($limit, $result->count(), 'limit did not work');

    $limit = 10;
    $offset = 15;
    $q = Doctrine_Query::create()
    ->select()
    ->from($className . ' as c')
    ->offset($offset)
    ->limit($limit);
    $result = $q->execute();
    $this->assertEquals(5, $result->count(), 'limit with offset did not work');

    $offset = 18;
    $q = Doctrine_Query::create()
    ->select()
    ->from($className . ' as c')
    ->offset($offset);
    $result = $q->execute();
    $this->assertEquals(2, $result->count(), 'offset did not work');
  }

  public function testQueryWhereIn()	{
//    $this->markTestSkipped();
    for($i=0; $i<10; $i++)	{
      $records[$i] = $this->create(false, true);
    }
    $idWhereInArray = array($records[1]->getId(), $records[5]->getId());

    $className = get_class($records[0]);
    $q = new Doctrine_Query();
    $q->addFrom($className . ' as r')
      ->whereIn('r.id', $idWhereInArray);
    $results = $q->execute();

    $this->assertEquals(count($idWhereInArray), $results->count());

    $ids = array();
    foreach($results as $r)	{
       $this->assertContains($r->getId(), $idWhereInArray);
    }
  }

  const ID_START_RR = 100;
  const ID_START_SR = 1000;
  const ID_START_SRR = 10000;

  protected static $nextRrId = self::ID_START_RR;
  protected static $nextSrId = self::ID_START_SR;
  protected static $nextSrrId = self::ID_START_SRR;

  protected static function getNextRrId()  {
    return self::$nextRrId++;
  }

  protected static function getNextSrId()	{
    return self::$nextSrId++;
  }

  protected static function getNextSrrId()	{
    return self::$nextSrrId++;
  }

  /**
   *
   * resets the global reference record id counter
   * @return integer start
   */
  protected static function resetNextRrId()	{
    self::$nextRrId = self::ID_START_RR;
    return self::$nextRrId;
  }

  /**
   * resets the global self reference record id counter
   * @return integer start
   */
  protected static function resteNextSrrId()	{
    self::$nextSrrId = self::ID_START_SRR;
    return self::$nextSrrId;
  }

  /**
   *
   * resets the global simple record id counter
   * @return integer start
   */
  protected static function resetNextSrId()	{
    self::$nextSrId = self::ID_START_SR;
    return self::$nextSrId;
  }

  /**
   * @return Test_ReferenceRecord
   */
  protected static function createReferenceRecord($data=null)  {
    $record = new Test_ReferenceRecord();
    if($data !== null)	{
      $simpleRecord = new Text_SimpleRecord();
      foreach($data as $name => $value)	{
        $simpleRecord->set($name, $value);
      }
    }
    else	{
      $id = self::getNextRrId();
      $record->setId($id);
      $record->setVarcharField1('field_01_'.$id);
      $record->setVarcharField2('field_02_'.$id);
    }
    return $record;
  }

  /**
   *
   * creates a already initialized instance of SelfReferenceRecord
   * @param unknown_type $data
   * @return Test_SelfReferenceRecord
   */
  protected static function createSelfReferenceRecord($data=null)	{
    $record = new Test_SelfReferenceRecord();
    if($data !== null)	{
      $record = new Test_SelfReferenceRecord();
      foreach($data as $name => $value)	{
        $record->set($name, $value);
      }
    }
    else	{
      $record->setVarcharField('record_'.$record->getId());
    }
    return $record;
  }

  /**
   *
   * @return Test_SimpleRecord
   */
  protected static function createSimpleRecord($data = null)	{
    $simpleRecord = new Test_SimpleRecord();
    if($data !== null)	{
      foreach($data as $name => $value)	{
        $simpleRecord->set($name, $value);
      }
    }
    else	{
      //$simpleRecord->setId(self::getNextSrId());
      $simpleRecord->setVarcharField('referencedBy');
      $simpleRecord->setBooleanField(true);
      $simpleRecord->setIntegerField(1111);
      
      //TODO set time field
      //$simpleRecord->setTimeField('12:00:30');
      $simpleRecord->setDateField('1976-04-25');
      $simpleRecord->setTimestampField('1976-04-25 12:00:30');
    }
    return $simpleRecord;
  }

  /**
   *
   * deletes all Reference and Simple Records by native pdo query
   * @return void
   */
  protected static function deleteAllRecords()  {
    self::deleteAllReferenceRecords();
    self::deleteAllSelfReferenceRecords();
    self::deleteAllSimpleRecords();
  }

  /**
   *
   * deletes all ReferenceRecords by native pdo query
   * @return void
   */
  protected static function deleteAllReferenceRecords()  {
    $q = Test_ReferenceRecordTable::getInstance()->createQuery('rr');
    $q->delete();
    $q->execute();
  }

  /**
   *
   * deletes all SimpleRecords by native pdo query
   * @return void
   */
  protected static function deleteAllSimpleRecords()  {
    $q = Test_SimpleRecordTable::getInstance()->createQuery('rr');
    $q->delete();
    $q->execute();
  }

  /**
   *
   * deletes all SelfReferenceRecords by native pdo query
   * @return void
   */
  protected static function deleteAllSelfReferenceRecords()  {
    $q = Test_SelfReferenceRecordTable::getInstance()->createQuery('rr');
    $q->delete();
    $q->execute();
  }

  /**
   * executes native PDO Query
   * @param string $sql
   * @return PDOStatement
   */
  protected static function executeDoctrineStandaloneQuery($sql)  {
    $connection = Doctrine::getTable('Test_ReferenceRecord')->getConnection();
    /* @var $pdoStatement PDOStatement */
    $pdoStatement = $connection->standaloneQuery($sql);
    $pdoStatement->execute();
    return $pdoStatement;
  }

}