<?php
class ItDoctrineExtensions_SequenceTestCase extends sfBasePhpunitTestCase	{

  const SEQ_NAME = 'test';

  /**
   * @var Doctrine_Connection_Informix
   */
  private $connection = null;

  /**
   *
   * @var Doctrine_Sequence_Informix
   */
  private $sequence = null;

  protected function _start()	{
    $this->connection = Doctrine_Manager::getInstance()->getConnection('ifx_dummy');
    $this->sequence = $this->connection->sequence;
  }

  protected function _end()	{
    $this->dropTestSequence();
  }

  /**
   * @expectedException Doctrine_Sequence_Exception
   */
  public function testNextIdException()	{
    //try get next on not existent
    $next = $this->sequence->nextId(self::SEQ_NAME, false);
  }

  public function testNextIdSuccess()	{
    $next = $this->sequence->nextId(self::SEQ_NAME, true);
    $this->assertEquals(1, $next);
    $next = $this->sequence->nextId(self::SEQ_NAME);
    $this->assertEquals(2, $next);
  }

  public function testCurrId()	{
    $next = $this->sequence->nextId(self::SEQ_NAME, true);
    $this->assertEquals($next, $this->sequence->currId(self::SEQ_NAME));
  }

  private function dropTestSequence()	{
    try {
      $this->connection->export->dropSequence(self::SEQ_NAME);
    } catch (Exception $e) {    }
  }

}