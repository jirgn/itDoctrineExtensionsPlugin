<?php
class Doctrine_Export_Informix extends Doctrine_Export
{

    public function createSequenceSql($seqName, $start = 1, array $options = array())
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($seqName), true);
        $sql = 'CREATE SEQUENCE ' . $sequenceName . ' INCREMENT BY 1 START WITH ' . $start . ' NOCACHE';
        if ($start < 0) {
            $sql .= 'MINVALUE ' . $start;
        }
        $sql .= ';';
        return $sql;
    }

    public function dropSequenceSql($seqName)
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($seqName), true);
        return 'DROP SEQUENCE ' . $sequenceName;
    }

    public function getDefaultFieldDeclaration($field)
    {
        $default = '';

        if (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull'])
                    ? null : $this->valid_default_values[$field['type']];

                if ($field['default'] === '' &&
                    ($this->conn->getAttribute(Doctrine_Core::ATTR_PORTABILITY) & Doctrine_Core::PORTABILITY_EMPTY_TO_NULL)
                ) {
                    $field['default'] = null;
                }
            }

            if ($field['type'] === 'boolean') {
                $field['default'] = $field['default'] ? 'T' : 'F';
            }
            $default = ' DEFAULT ' . (is_null($field['default'])
                ? 'NULL' : $this->conn->quote($field['default'], $field['type']));
        }

        return $default;
    }

    public function createTableSql($name, array $fields, array $options = array())
    {
        if (!$name) {
            throw new Doctrine_Export_Exception('no valid table name specified');
        }
        if (empty($fields)) {
            throw new Doctrine_Export_Exception('no fields specified for table ' . $name);
        }
        $queryFields = $this->getFieldDeclarationList($fields);
        if (isset($options['primary']) && !empty($options['primary'])) {
            $primaryKeys = array_map(array($this->conn, 'quoteIdentifier'), array_values($options['primary']));
            $queryFields .= ', PRIMARY KEY(' . implode(', ', $primaryKeys) . ')';
        }
        $query = 'CREATE TABLE ' . $this->conn->quoteIdentifier($name, true) . ' (' . $queryFields;
        $check = $this->getCheckDeclaration($fields);
        if (!empty($check)) {
            $query .= ', ' . $check;
        }
        $query .= ')';
        $sql[] = $query;
        if (isset($options['indexes']) && !empty($options['indexes'])) {
            foreach ($options['indexes'] as $index => $definition) {
                $sql[] = $this->createIndexSql($name, $index, $definition);
            }
        }
        if (isset($options['foreignKeys'])) {
            foreach ((array)$options['foreignKeys'] as $k => $definition) {
                if (is_array($definition)) {
                    $sql[] = $this->createForeignKeySql($name, $definition);
                }
            }
        }
        return $sql;
    }

    public function getForeignKeyBaseDeclaration(array $definition)
    {
        $sql = 'CONSTRAINT FOREIGN KEY (';

        if (!isset($definition['local'])) {
            throw new Doctrine_Export_Exception('Local reference field missing from definition.');
        }
        if (!isset($definition['foreign'])) {
            throw new Doctrine_Export_Exception('Foreign reference field missing from definition.');
        }
        if (!isset($definition['foreignTable'])) {
            throw new Doctrine_Export_Exception('Foreign reference table missing from definition.');
        }
        if (!is_array($definition['local'])) {
            $definition['local'] = array($definition['local']);
        }
        if (!is_array($definition['foreign'])) {
            $definition['foreign'] = array($definition['foreign']);
        }
        $sql .= implode(', ', array_map(array($this->conn, 'quoteIdentifier'), $definition['local']))
            . ') REFERENCES '
            . $this->conn->quoteIdentifier($definition['foreignTable']) . '('
            . implode(', ', array_map(array($this->conn, 'quoteIdentifier'), $definition['foreign'])) . ')';

        return $sql;
    }

    public function getAdvancedForeignKeyOptions(array $definition)
    {
        $query = '';
        if (!empty($definition['onDelete'])) {
            $query .= ' ON DELETE ' . $this->getForeignKeyReferentialAction($definition['onDelete']);
        }
        return $query;
    }

    /**
     * (non-PHPdoc)
     * @see Doctrine_Export::alterTable()
     */
    public function alterTable($name, array $changes, $check = false)
    {
        $sql = $this->alterTableSql($name, $changes, $check);
        foreach ($sql as $query) {
            $this->conn->exec($query);
        }
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see Doctrine_Export::alterTableSql()
     * @return array of string with sql statement
     */
    public function alterTableSql($name, array $changes, $check = false)
    {
        foreach ($changes as $changeName => $change) {
            switch ($changeName) {
                case 'add':
                case 'remove':
                case 'name':
                case 'rename':
                case 'change':
                    break;
                default:
                    throw new Doctrine_Export_Exception('change type "' . $changeName . '\" not yet supported');
            }
        }

        if ($check) {
            return true;
        }

        $sql = array();

        if (isset($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $fieldName => $field) {
                $query = 'ADD ' . $this->getDeclaration($fieldName, $field);
                $sql[] = 'ALTER TABLE ' . $this->conn->quoteIdentifier($name, true) . ' ' . $query;
            }
        }

        if (isset($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $fieldName => $field) {
                $fieldName = $this->conn->quoteIdentifier($fieldName, true);
                $query = 'DROP ' . $fieldName;
                $sql[] = 'ALTER TABLE ' . $this->conn->quoteIdentifier($name, true) . ' ' . $query;
            }
        }

        if (isset($changes['name'])) {
            $changeName = $this->conn->quoteIdentifier($changes['name'], true);
            $sql[] = 'RENAME TABLE ' . $this->conn->quoteIdentifier($name, true) . ' TO ' . $changeName;
        }

        if (isset($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $fieldName => $field) {
                $fieldName = $this->conn->quoteIdentifier($fieldName, true);
                $sql[] = "RENAME COLUMN {$this->conn->quoteIdentifier($name, true)}.$fieldName TO {$this->conn->quoteIdentifier($field['name'], true)}";
            }
        }

        if (isset($changes['change']) && is_array($changes['change'])) {
            $fields = array();
            foreach ($changes['change'] as $fieldName => $field) {
                if (isset($field['definition'])) {
                    $fields[] = $fieldName . ' ' . $this->getDeclaration('', $field['definition']);
                }
            }
            if (!empty($fields)) {
                $sql[] = 'ALTER TABLE ' . $this->conn->quoteIdentifier($name, true) . ' MODIFY (' . implode(', ', $fields) . ')';
            }
        }
        return $sql;
    }

    /**
     * @param $name <strong>will be ignored - Informix does not support constraint names at the moment</strong>
     * @see Doctrine_Export::createConstraintSql()
     */
    public function createConstraintSql($table, $name, $definition)
    {
        $table = $this->conn->quoteIdentifier($table);
        $query = 'ALTER TABLE ' . $table . ' ADD CONSTRAINT ';

        if (isset($definition['primary']) && $definition['primary']) {
            $query .= ' PRIMARY KEY';
        } elseif (isset($definition['unique']) && $definition['unique']) {
            $query .= ' UNIQUE';
        }

        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $this->conn->quoteIdentifier($field, true);
        }
        $query .= ' (' . implode(', ', $fields) . ')';

        return $query;
    }
}