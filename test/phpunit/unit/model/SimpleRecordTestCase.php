<?php
/**
 * interactive-tools 2010 (c)
 */
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'AbstractRecordTestCase.php');

/**
 *
 * enter description here ...
 *
 * @package			itDoctrineExtensionsPlugin
 * @subpackage	test
 * @author			jirgn_itools
 * @version			SVN: $Id: file_name $/
 *
 */
class ItDoctrineExtensions_SimpleRecordTestCase extends ItDoctrineExtensions_AbstractRecordTestCase {

  const REFERENCE_COUNT = 20;
  
  /**
   * (non-PHPdoc)
   * @see ItDoctrineExtensions_AbstractRecordTestCase::create()
   * @return Test_SimpleRecord
   */
  protected function create($withReferences=false, $persisted=false)		{
    $record = self::createSimpleRecord();
    if($withReferences)	{
      /* @var $refernceRecordCollection Doctrine_Collection */
      $refernceRecordCollection = $record->getReferenceRecords();
      for($i=0; $i<self::REFERENCE_COUNT; $i++)	{
        $refernceRecordCollection->add(self::createReferenceRecord());
      }
    }
    if($persisted)	{
      $record->save();
    }
    return $record;
  }
  
  public function testUtf8IsoSave()	{
    $this->markTestSkipped('connection converstion fails. field length in informix must be lenth times 4');
    
    //cause informix has to be iso 8859-1 in kbb context we use dsn params for client_locale and db_locale
    //that does the conversion
    
    $record = new Test_SimpleRecord();
    //set full length of varcharfield with special chars (if not correctly converted multi bytes will be used -> will be too long)
    $specialChars = 'ääääääääääÜÜÜÜÜÜÜÜÜÜÖÖÖÖÖ';
    $record->setVarcharField($specialChars);
    $record->save();
    $loaded = Test_SimpleRecordTable::getInstance()->find($record->getId());
    $this->assertEquals($loaded->getVarcharField(), $specialChars);
  }

  public function testTimestampWithDateOnly()	{
    //$this->markTestSkipped();
    $record = self::create(false);
    $record->setTimestampField('2010-10-10');
    $record->save();

    $loaded = Test_SimpleRecordTable::getInstance()->find($record->getId());

    $this->assertEquals($record->getTimestampField(), $record->getTimestampField(), 'timestamp was not saved correctly');
  }

  public function testQueryLimitsOnJoin()	{
    //    $this->markTestSkipped();
    $records = array();
    $totalRecords = 20;
    for($i=0; $i<$totalRecords; $i++)  {
      $records[] = $this->create(true, true);
    }
    $className = get_class($records[0]);

    $limit = 15;
    $q = Doctrine_Query::create()
    ->select()
    ->from($className . ' as c')
    ->leftJoin('c.ReferenceRecords rr')
    ->limit($limit);
    $result = $q->execute();
    $this->assertEquals($limit, $result->count(), 'limit did not work');

    $limit = 10;
    $offset = 15;
    $q = Doctrine_Query::create()
    ->select()
    ->from($className . ' as c')
    ->leftJoin('c.ReferenceRecords rr')
    ->offset($offset)
    ->limit($limit);
    $result = $q->execute();
    $this->assertEquals(5, $result->count(), 'limit with offset did not work');

    $limit = 10;
    $offset = 15;
    $q = Doctrine_Query::create()
    ->select()
    ->from($className . ' as c')
    ->leftJoin('c.ReferenceRecords rr')
    ->addWhere('c.ReferenceRecords.id > ?', 0)
    ->offset($offset)
    ->limit($limit);
    $result = $q->execute();
    $this->assertEquals(5, $result->count(), 'limit with offset and where did not work');

    $limit = 10;
    $offset = 15;
    $q = Doctrine_Query::create()
    ->select()
    ->from($className . ' as c')
    ->leftJoin('c.ReferenceRecords rr')
    ->addWhere('c.ReferenceRecords.id > ?', 0)
    ->offset($offset)
    ->limit($limit)
    ->orderBy('c.varchar_field');
    $result = $q->execute();
    $this->assertEquals(5, $result->count(), 'ordered limit with offset and where did not work');
    
    //offset only does not work like expected -> row limit context instead of object limit context
    //    $offset = 18;
    //    $q = Doctrine_Query::create()
    //    ->select()
    //    ->from($className . ' as c')
    //    ->leftJoin('c.ReferenceRecords rr')
    //    ->offset($offset);
    //    $result = $q->execute();
    //    foreach($result as $r)	{
    //      var_dump($r->getReferenceRecords()->count());
    //    }
    //    $this->assertEquals(2, $result->count(), 'offset did not work');
    //
  }

  public function testQueryJoin()	{
    //    $this->markTestSkipped();
    self::resetNextRrId();
    self::resetNextSrId();
    for($i=0; $i<10; $i++)	{
      $records[] = $this->create(true, true);
    }
    for($i=0; $i<10; $i++)	{
      $records[] = $this->create(false, true);
    }

    $className = get_class($records[0]);
    $q = new Doctrine_Query();
    $q->addFrom($className.' as r')
    ->leftJoin('r.ReferenceRecords rr')
    ->where('rr.id > 200');
    $results = $q->execute();

    $this->assertEquals(5, $results->count());
  }

  public function testQueryOrderBy()	{
    //    $this->markTestSkipped();
    $records = array();
    $count = 1;
    $records[] = self::createSimpleRecord(array('id'=>$count++, 'varchar_field'=>'b'));
    $records[] = self::createSimpleRecord(array('id'=>$count++, 'varchar_field'=>'c'));
    $records[] = self::createSimpleRecord(array('id'=>$count++, 'varchar_field'=>'a'));
    foreach($records as $r)	{
      $r->save();
    }

    $className = get_class($records[0]);
    $q = new Doctrine_Query();
    $q->addFrom($className . ' as r')
    ->orderBy('r.varchar_field ASC');

    $results = $q->execute();
    $orderdFields = array();
    foreach($results as $r)	{
      $orderdFields[] = $r->getVarcharField();
    }
    $this->assertEquals(array('a', 'b', 'c'), $orderdFields);

    $q = new Doctrine_Query();
    $q->addFrom($className . ' as r')
    ->orderBy('r.varchar_field DESC');

    $results = $q->execute();
    $orderdFields = array();
    foreach($results as $r)	{
      $orderdFields[] = $r->getVarcharField();
    }
    $this->assertEquals(array('c', 'b', 'a'), $orderdFields);
  }

  public function testQueryLike()	{
//    $this->markTestSkipped();
    $records = array();
    $count = 1;
    $records[] = self::createSimpleRecord(array('id'=>$count++, 'varchar_field'=>'ab'));
    $records[] = self::createSimpleRecord(array('id'=>$count++, 'varchar_field'=>'abc'));
    $records[] = self::createSimpleRecord(array('id'=>$count++, 'varchar_field'=>'bcd'));
    foreach($records as $r)	{
      $r->save();
    }
    $className = get_class($records[0]);
    $q = new Doctrine_Query();
    $q->addFrom($className . ' as r')
      ->andWhere('varchar_field like ?', 'ab%');
    $results = $q->execute();

    $this->assertEquals(2, $results->count());
  }
  
  public function testQueryWhereOnTypes()	{
    $records = array();
    foreach(range(1, 6) as $index)	{
      $record = new Test_SimpleRecord();
      $record->setVarcharField('test'.$index);
      $record->setTimestampField('2010-'.$index.'-01');
      $record->setBooleanField(true);
      $record->save();
    }
    foreach(range(7, 10) as $index)	{
      $record = new Test_SimpleRecord();
      $record->setVarcharField('test'.$index);
      $record->setBooleanField(false);
      $record->setTimestampField('2010-'.$index.'-01');
      $record->save();
      $records[] = $record;
    }
    
    //boolean
    $query = Doctrine_Query::create()
      ->select()
      ->from('Test_SimpleRecord as s')
      ->where('s.boolean_field = ?', false); 
      
    $res = $query->execute();
    $this->assertEquals(4, $res->count(), 'boolean compare did not work correctly');

    //timestamp
    $query = Doctrine_Query::create()
      ->select()
      ->from('Test_SimpleRecord as s')
      ->where('s.timestamp_field < ?', '2010-06-01')
      ->andWhere('s.timestamp_field > ?', '2010-03-01');
      
    $res = $query->execute();
    $this->assertEquals(2, $res->count(), 'timestamp compare did not work correctly');
  }
  
  public function testTypeAccess()	{
    $this->markTestSkipped('text type does not work like expected');
    /* @var $record Test_SimpleRecord */
    $record = $this->create(false, false);
//    $debugger = new itDoctrineInformixDebugger(Test_SimpleRecordTable::getInstance()->getConnection());
//    $debugger->start();
    $file_path = $this->getOwnFixtureDir().DIRECTORY_SEPARATOR.'text_field_data.txt';
    $record->setTextField(file_get_contents($file_path));
    var_dump($record->getData());
    $record->save();
    
    $laoded = Test_SimpleRecordTable::getInstance()->find($record->getId(), Doctrine_Core::HYDRATE_ARRAY);
    var_dump($laoded, stream_get_contents($laoded['text_field']));exit;
    
  }
}