<?php
/**
 * interactive-tools 2010 (c)
 */

/**
 *
 *
 * @category plugin
 * @package itDoctrineExtensionsPlugin
 * @subpackage test.unit
 * @version SVN: $Id$
 * @author jirgn <juergen.messner@interactive-tools.de>
 *
 */
class ItDoctrineExtension_ConnectionPdoInformixTestCase extends sfBasePhpunitTestCase {

  protected static $insert_sql = "INSERT INTO simple_record (varchar_field, integer_field, boolean_field) VALUES ('stringValue', 1000, 'T');";
  protected static $select_sql = 'SELECT COUNT(id) as item_count FROM simple_record WHERE id=1';
  protected static $delete_sql = 'DELETE FROM simple_record WHERE id=1';

  /**
   *
   * @var PDO
   */
  protected $pdo = null;


  protected function _start() {
  $this->pdo = new PDO("informix:host=itools-informix32; service=9088;database=ifx_test;server=itools;protocol=onsoctcp;", "root", "fred8");
  //$this->pdo = new PDO("informix:host=10.10.10.16; service=3307;database=itools_ifx_test;server=filmfest;protocol=onsoctcp;", "itools", "Boa2011");
  }
  
  public function testLastInsertId()	{
    $this->markTestSkipped('lastInsertId on Informix seems not to work correctly');
    foreach(range(0, 9) as $index)	{
      var_dump($index);
      $stmd = $this->pdo->query(self::$insert_sql);
    }
    $lastInsertId = $this->pdo->lastInsertId();
    var_dump($lastInsertId);
    $this->assertGreaterThan(0, $lastInsertId);
    
//    $stmd = $this->pdo->query("SELECT DBINFO ('sqlca.sqlerrd1') FROM systables WHERE tabid = 1;");
    $stmd = $this->pdo->query("SELECT DBINFO ('sqlca.sqlerrd1') FROM systables WHERE abname = 'systables'");
    $result = $stmd->fetch();
    var_dump($result);
  }

  public function testTransactionCommit()  {
    //transaction insert and commit
    $this->pdo->beginTransaction();
    $rowCount = $this->pdo->exec(self::$insert_sql);
    $this->pdo->commit();
    $this->assertGreaterThan(0, $rowCount, "pdo transaction statement did not affect any rows");

//    /* @var $stmd PDOStatement */
//    $stmd = $this->pdo->query(self::$select_sql);
//    $result = $stmd->fetch(PDO::FETCH_ASSOC);
//    $this->assertEquals('1', $result['ITEM_COUNT'], 'could not fetch currently inserted entity');
//
//    $rowCount = $this->pdo->exec(self::$delete_sql);
//    $this->assertGreaterThan(0, $rowCount, "could not delete currently inserted entity with simple delete (no transaction)");
  }

  /**
   * tests informix connection directly on pdo layer
   * transaction, simple statement and rollback
   */
  public function testTransactionRollback() {

    $this->pdo->beginTransaction();
    $this->pdo->exec(str_replace('id=1', 'id=2', self::$insert_sql));
    $this->pdo->rollBack();

    $res = $this->pdo->query(str_replace('id=1', 'id=2', self::$select_sql));
    $this->assertLessThan(2, $res, 'rollback did not work.');
  }

}