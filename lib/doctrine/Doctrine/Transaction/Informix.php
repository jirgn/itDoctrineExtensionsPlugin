<?php
/**
 * interactive-tools 2010 (c)
 */

class Doctrine_Transaction_Informix extends Doctrine_Transaction  {

  /**
   * Set the transacton isolation level.
   *
   * @param   string  standard isolation level
   *                  READ UNCOMMITTED (allows dirty reads)
   *                  READ COMMITTED (prevents dirty reads)
   *                  REPEATABLE READ (prevents nonrepeatable reads)
   *                  SERIALIZABLE (prevents phantom reads)
   *
   * @throws Doctrine_Transaction_Exception           if using unknown isolation level
   * @throws PDOException                             if something fails at the PDO level
   * @return void
   */
  public function setIsolation($isolation)  {
    switch ($isolation) {
      case 'READ UNCOMMITTED':
      case 'READ COMMITTED':
      case 'REPEATABLE READ':
      case 'SERIALIZABLE':
        break;
      default:
        throw new Doctrine_Transaction_Exception('Isolation level ' . $isolation . ' is not supported.');
    }

    $query = 'SET TRANSACTION ISOLATION LEVEL ' . $isolation;

    return $this->conn->execute($query);
  }
}