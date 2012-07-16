<?php

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'AbstractRecordTestCase.php');

/**
 *
 * enter description here ...
 *
 * @package			itDoctrineExtensionPlugin
 * @subpackage	test
 * @author			jirgn_itools
 * @version			SVN: $Id: file_name $/
 *
 */
class ItDoctrineExtensions_SelfReferenceRecordTestCase extends ItDoctrineExtensions_AbstractRecordTestCase	{

  const REFERENCE_COUNT = 15;

  /**
   * (non-PHPdoc)
   * @see ItDoctrineExtensions_AbstractRecordTestCase::create()
   * @return Test_SelfReferenceRecord
   */
  protected function create($withReferences=false, $persisted=false)	{
    $record = self::createSelfReferenceRecord();
    if($withReferences)	{
      /* @var $refernceRecordCollection Doctrine_Collection */
      $refernceRecordCollection = $record->getReferencedToRecords();
      for($i=0; $i<self::REFERENCE_COUNT; $i++)	{
        $refernceRecordCollection->add(self::createSelfReferenceRecord());
      }
    }
    if($persisted)	{
      $record->save();
    }
    return $record;
  }

  public function testLoadWithReferences()	{
//    $this->markTestSkipped();
    $record = $this->create(true, true);
    /* @var $loaded Test_SelfReferenceRecord */
    $loaded = Test_SelfReferenceRecordTable::getInstance()->find($record->getId());
    $this->assertEquals($record->getData(), $loaded->getData(), 'loaded does not match saved record');
    foreach($loaded->getReferencedToRecords() as $ref)	{
      /* @var $ref Test_SelfReferenceRecord */
      $this->assertEquals($loaded->getId(), $ref->getReferencedByRecord()->getId(), 'refernce is not correct');
    }
  }

  public function testSaveWithManualIdUsesSequence()	{
//$this->markTestSkipped();
    $record = new Test_SelfReferenceRecord();
    $record->setId(99999999);
    $record->setVarcharField('testRecord');

    $refRec = new Test_SelfReferenceRecord();
    $refRec->setVarcharField('referenced');
    $refRec->setReferencedByRecord($record);

    $record->save();
    $this->assertNotEquals(999999999, $record->getId());
    $this->assertEquals($record->getId(), $refRec->getReferencedByRecord()->getId());

  }

  public function testUpdateId()	{
//    $this->markTestSkipped();
    $record = $this->create(false, true);
    $idBefore = $record->getId();

    $record->setId(100);
    $record->save();
    $this->assertEquals(100, $record->getId());
  }
}
