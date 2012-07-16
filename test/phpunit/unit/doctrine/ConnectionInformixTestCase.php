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
class ItDoctrineExtension_ConnectionInformixTestCase extends sfBasePhpunitTestCase {

  protected static $insert_sql = "INSERT INTO simple_record (id, varchar_field, integer_field, boolean_field) VALUES (1, 'stringValue', 1000, 'T');";
  protected static $select_sql = 'SELECT COUNT(id) as item_count FROM simple_record WHERE id=1';
  protected static $delete_sql = 'DELETE FROM simple_record WHERE id=1';

  /**
   *
   * @var Doctrine_Connection_Informix
   */
  protected $connection = null;

  protected function _start() {
    $this->connection = Doctrine_Manager::getInstance()->getConnection('ifx_dummy');
  }



  /**
   * test connection to informix
   */
  public function testDoctrineConnectToInformix() {
    if($this->connection->isConnected()) {
      $this->connection->close();
    }
    $this->assertTrue($this->connection->connect(), 'can not connect to ifx_dummy database with doctrine connection');
  }

}