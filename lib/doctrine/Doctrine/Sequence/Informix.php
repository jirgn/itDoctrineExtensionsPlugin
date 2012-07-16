<?php
/**
 *
 * Handles Sequeces in Doctrine context
 * @author jirgn
 *
 */
class Doctrine_Sequence_Informix extends Doctrine_Sequence	{

  /**
   * (non-PHPdoc)
   * @see Doctrine_Sequence::nextId()
   */
  public function nextId($seqName, $ondemand = true)    {
    $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($seqName), true);
    $query = 'SELECT FIRST 1 '.$sequenceName.'.NEXTVAL FROM systables;';
    try {
      $result = (int) $this->conn->fetchOne($query);
    } catch(Doctrine_Connection_Exception $e) {
      if ($ondemand) {
        try {
          $result = $this->conn->export->createSequence($seqName);
        } catch(Doctrine_Exception $e) {
          throw new Doctrine_Sequence_Exception('on demand sequence ' . $seqName . ' could not be created');
        }

        return $this->nextId($seqName, false);
      } else {
        throw new Doctrine_Sequence_Exception('sequence ' .$seqName . ' does not exist');
      }
    }

    return $result;
  }

  /**
   * (non-PHPdoc)
   * @see Doctrine_Sequence::lastInsertId()
   */
  public function lastInsertId($table = null, $field = null) {
    //HACK this could get false values on high traffic sites
    //This function correspondes with the autoincremet setting.
    //To avoid Problems better use a real sequence instead of autoincremnt (SERIAL)
    if($table === null || $field === null)	{
      //var_dump($table, $field);
      throw new Doctrine_Connection_Informix_Exception('tablename or fieldname not defined when fetching last insert id');
    }
    return (int) $this->conn->fetchOne('SELECT MAX('.$field.') FROM '.$table);
    
    //lastInsertId on informix pdo seems not to work @see test ConnectionPdoInformix::testLastInsertId
    //return $this->conn->getDbh()->lastInsertId();
    
    //no sequence repreentation in informix found
//    $seqName = $table . (empty($field) ? '' : '_'.$field);
//    $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($seqName), true);
//    return (int) $this->conn->fetchOne('SELECT FIRST 1 '.$sequenceName.'.CURRVAL FROM systables;');
  }

  /**
   * (non-PHPdoc)
   * @see Doctrine_Sequence::currId()
   */
  public function currId($seqName)  {
    $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($seqName), true);
    return (int) $this->conn->fetchOne('SELECT FIRST 1 '.$sequenceName.'.CURRVAL FROM systables;');
  }

}