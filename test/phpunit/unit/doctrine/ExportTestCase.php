<?php
class ItDoctrineExtensions_ExportTestCase extends sfBasePhpunitTestCase	{

  public function testAlterTableAddColumn()	{
    $changes =array(
                    'add' => array(
                        'add_this' => array(
                            'type' => 'integer',
                            'length' => '10',
                            'unique' => '1',
                        ),
                        'add_this2' => array(
                            'type' => 'string',
                            'length' => '10',
                        )
                    ),
                    'remove' => array(
                        'drop_this' => array(),
                        'drop_this2' => array()
                    ),
                    'name' => 'new_table_name',
                    'rename' => array(
                        'test_column' => array(
                              'name' => 'new_column_name',
                        ),
                        'other_column' => array(
                              'name' => 'new_other_column_name',
                        )
                    ),
                    'change' => array(
                        'test_column' => array(
                            'definition' => array(
                                'type' => 'boolean',
                                'default' => true,
                            ),
                        ),
                        'othertest_column' => array(
                            'definition' => array(
                                'type' => 'integer',
                                'length' => 8,
                                'default' => 3,
                                'unique' => 1
                            ),
                        )
                    ),
                );
    $export = new Doctrine_Export_Informix();
    $sql = $export->alterTableSql('test_table', $changes, false);
    $expectedSql[] = 'ALTER TABLE test_table ADD add_this BIGINT UNIQUE';
    $expectedSql[] = 'ALTER TABLE test_table ADD add_this2 VARCHAR(20)';
    $expectedSql[] = 'ALTER TABLE test_table DROP drop_this';
    $expectedSql[] = 'ALTER TABLE test_table DROP drop_this2';
    $expectedSql[] = 'RENAME TABLE test_table TO new_table_name';
    $expectedSql[] = 'RENAME COLUMN test_table.test_column TO new_column_name';
    $expectedSql[] = 'RENAME COLUMN test_table.other_column TO new_other_column_name';
    $expectedSql[] = "ALTER TABLE test_table MODIFY (test_column  BOOLEAN DEFAULT 'T', othertest_column  BIGINT DEFAULT 3 UNIQUE)";
    $this->assertEquals($sql, $expectedSql);
  }

}