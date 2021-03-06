<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.org>.
 */

/**
 * @package     Doctrine
 * @subpackage  DataDict
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision$
 * @link        www.phpdoctrine.org
 * @since       1.0
 */
class Doctrine_DataDict_Informix extends Doctrine_DataDict
{
    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param array $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      default
     *          Text value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     *
     * @throws RuntimeException
     * @throws Doctrine_DataDict_Exception
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     */
  public function getNativeDeclaration($field)
  {
    if ( ! isset($field['type'])) {
      throw new Doctrine_DataDict_Exception('Missing column type.');
    }
    
    switch ($field['type']) {
      case 'char':
      case 'enum':
        $field['length'] = isset($field['length']) && $field['length'] ? $field['length']:255;
      case 'varchar':
      case 'array':
      case 'object':
      case 'string':
        if (empty($field['length']) && array_key_exists('default', $field)) {
          $field['length'] = $this->conn->varchar_max_length;
        }

        $length = ( ! empty($field['length'])) ? $field['length'] : false;
        $fixed  = ((isset($field['fixed']) && $field['fixed']) || $field['type'] == 'char') ? true : false;

        $result = false;
        if($fixed)  {
          $result =  $length ? 'CHAR('.$length.')' : 'CHAR(255)';
        }
        else{
          if($length){
            $result =  ($length <= 255) ? 'VARCHAR('.$length.')' : 'LVARCHAR('.$length.')';
          }else{
            $result = 'TEXT';
          }
        }
        if($result)  {
          return $result;
        }
        throw new RuntimeException('check typedefintion in your model');
      case 'clob':
        return 'TEXT';
      case 'blob':
        return 'BLOB';
      case 'integer':
      case 'int':
        if ( ! empty($field['autoincrement'])) {
          if ( ! empty($field['length'])) {
            $length = $field['length'];
            if ($length > 4) {
              return 'BIGSERIAL';
            }
          }
          return 'SERIAL';
        }
        if ( ! empty($field['length'])) {
          $length = $field['length'];
          if ($length <= 1) {
            return 'SMALLINT';
          } elseif ($length == 2) {
            return 'SMALLINT';
          } elseif ($length == 3 || $length == 4) {
            return 'INTEGER';
          } elseif ($length > 4) {
            return 'BIGINT';
          }
        }
        return 'INT';
      case 'boolean':
        return 'BOOLEAN';
      case 'date':
        return 'DATE';
      case 'time':
        return  'DATETIME HOUR TO SECOND';
      case 'timestamp':
        return 'DATETIME YEAR TO SECOND';
      case 'float':
        return 'FLOAT';
      case 'decimal':
        return 'DECIMAL';
    }
    throw new Doctrine_DataDict_Exception('Unknown field type \'' . $field['type'] .  '\'.');
  }

  /**
   * TODO check if this can replace the connection implementation of defaultvalue
   * parseBoolean
   * parses a literal boolean value and returns
   * proper sql equivalent
   *
   * @param string $value     boolean value to be parsed
   * @return string           parsed boolean value
   */
  //    public function parseBoolean($value)
  //    {
  //        return $value;
  //    }
  }