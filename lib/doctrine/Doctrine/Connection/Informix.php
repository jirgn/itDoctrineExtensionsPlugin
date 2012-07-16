<?php
/**
 * interactive-tools 2010 (c)
 */

/**
 *
 *
 * @category plugin
 * @package kbbOfflinePlugin
 * @subpackage doctrine
 * @version SVN: $Id$
 * @author jirgn <juergen.messner@interactive-tools.de>
 *
 */
class Doctrine_Connection_Informix extends Doctrine_Connection_Common {
  protected $driverName = 'Informix';
  public function __construct(Doctrine_Manager $manager, $adapter)
  {
    // initialize all driver options
    $this->supported = array(
      'sequences'               => true,
      'indexes'                 => true,
      'transactions'            => true,
      'savepoints'              => false,
      'current_id'              => true,
      'limit_queries'           => true,
      'auto_increment'          => true,
      'primary_key'             => true,
      'prepared_statements'     => true,
    );
    parent::__construct($manager, $adapter);
  }

  public function modifyLimitQuery($query, $limit = false,$offset = false,$isManip=false) {
    return $this->_modifyLimitQuery($query, $limit, $offset, $isManip, false);
  }

  public function modifyLimitSubquery(Doctrine_Table $rootTable, $query, $limit = false,
                                        $offset = false, $isManip = false)	{
    return $this->_modifyLimitQuery($query, $limit, $offset, $isManip, true);
  }

  private function _modifyLimitQuery($query, $limit = false,$offset = false,$isManip=false, $enshureOrderByFieldsInSelect=false) {
    $limit = (integer) $limit;
    $offset = (integer) $offset;
    $pojectionStatement = '';

    if ($limit && $offset) {
      $pojectionStatement = ' SKIP ' . $offset . ' FIRST ' . $limit;
    } elseif ($limit && ! $offset) {
      $pojectionStatement = ' FIRST ' . $limit;
    } elseif ( ! $limit && $offset) {
      $pojectionStatement = ' SKIP ' . $offset;
    }

    $select = 'SELECT';
    $query = str_ireplace($select, $select . $pojectionStatement, $query);
    
    $orderbyFields = '';
    if($enshureOrderByFieldsInSelect && ($orderByPos = stripos($query, 'ORDER BY')))	{
      $orderbyFields = substr($query, $orderByPos + strlen('ORDER BY '));
      $from = ' FROM';
      $query = str_ireplace($from, ', '.$orderbyFields . $from, $query);
    }
    return $query;
  }
}