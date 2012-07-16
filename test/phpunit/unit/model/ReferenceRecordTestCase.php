<?php
/**
 * interactive-tools 2010 (c)
 */

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'AbstractRecordTestCase.php');

class ItDoctrineExtensions_ReferenceRecordTestCase extends ItDoctrineExtensions_AbstractRecordTestCase {


  protected function create($withReferences=false, $persisted=false)	{
    $record = self::createReferenceRecord();
    if($withReferences)	{
      $simpleRecord = self::createSimpleRecord();
      $record->setSimpleRecord($simpleRecord);
    }
    if($persisted)	{
      $record->save();
    }
    return $record;
  }

  public function testFindRecord()	{
    $this->markTestSkipped('cause of a strange behaviour loaded Records in this context have a NULL value instead of Doctrine_Null');
    parent::testFindRecord();
  }
  
  public function testSaveWithReferences() {
//    $this->markTestSkipped();
    $record = $this->create(true);
    $record->save();
    $simpleRecord = $record->getSimpleRecord();
    $this->assertFalse($record->isModified(true));
    $this->assertFalse($simpleRecord->isModified());
    $this->assertEquals($record->getSimpleRecordId(), $simpleRecord->getId());
  }

  public function testQueryLimitOnJoin() {
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
    ->leftJoin('c.SimpleRecord as s')
    ->addWhere('s.id > ?', 0)
    ->limit($limit);
    $result = $q->execute();
    $this->assertEquals($limit, $result->count(), 'limit did not work');
  }



}
