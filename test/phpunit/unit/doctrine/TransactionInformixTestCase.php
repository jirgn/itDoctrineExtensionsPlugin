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
class ItDoctrineExtension_TransactionInformixTestCase extends sfBasePhpunitTestCase {

  protected static $insert_sql = "INSERT INTO simple_record (id, varchar_field, integer_field, boolean_field) VALUES (1, 'stringValue', 1000, 'T');";
  protected static $select_sql = 'SELECT COUNT(id) as item_count FROM simple_record WHERE id=1';
  protected static $delete_sql = 'DELETE FROM simple_record WHERE id=1';

  /**
   /* @var Doctrine_Connection
   */
  protected $connection = null;

  protected function _start() {
    parent::_start();
    $this->connection = Doctrine_Manager::getInstance()->getConnection('ifx_dummy');
  }

  public function testTransactionCommitSuccess()  {
    $this->connection->beginTransaction();
    $this->connection->execute(self::$insert_sql);
    $this->connection->commit();

    $pdoStatement = $this->executeDoctrineStandaloneQuery(self::$select_sql);
    $result = $pdoStatement->fetch(PDO::FETCH_ASSOC);
    $this->assertEquals(1, $result['item_count'],  'could not fetch currently inserted record');

    $this->connection->execute(self::$delete_sql);

    $pdoStatement = $this->executeDoctrineStandaloneQuery(self::$select_sql);
    $result = $pdoStatement->fetch(PDO::FETCH_ASSOC);
    $this->assertEquals(0, $result['item_count'],  'direct deletion without transaction did not work');

  }

  public function testTransactionRollbackSuccess() {
    $this->connection->beginTransaction();
    $this->connection->execute(str_replace('id=1', 'id=2', self::$insert_sql));
    $this->connection->rollback();

    $pdoStatement = $this->executeDoctrineStandaloneQuery(str_replace('id=1', 'id=2', self::$select_sql));
    $result = $pdoStatement->fetch(PDO::FETCH_ASSOC);
    $this->assertEquals(0, $result['item_count'],  'rollback did not work');

  }

  public function testIsolationLevel() {
    $this->markTestSkipped('not implemented yet');
  }

 /**
   *
   * @param string $sql
   * @return PDOStatement
   */
  private function executeDoctrineStandaloneQuery($sql)  {
    /* @var $pdoStatement PDOStatement */
    $pdoStatement = $this->connection->standaloneQuery($sql);
    $pdoStatement->execute();
    return $pdoStatement;
  }
}