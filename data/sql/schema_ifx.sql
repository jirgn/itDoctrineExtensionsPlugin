CREATE TABLE test_table_02 (id BIGINT, simple_record_id BIGINT, varchar_field1 VARCHAR(50), column_03 VARCHAR(50), PRIMARY KEY(id));
CREATE TABLE self_reference_record (id BIGINT, varchar_field VARCHAR(100), self_reference_record_id BIGINT, PRIMARY KEY(id));
CREATE TABLE simple_record (id BIGSERIAL, varchar_field VARCHAR(50) DEFAULT 'default', text_field TEXT, integer_field BIGINT DEFAULT 0, boolean_field BOOLEAN DEFAULT 'F', date_field DATE, time_field DATETIME HOUR TO SECOND, timestamp_field DATETIME YEAR TO SECOND, PRIMARY KEY(id));
ALTER TABLE test_table_02 ADD CONSTRAINT FOREIGN KEY (simple_record_id) REFERENCES simple_record(id);
ALTER TABLE self_reference_record ADD CONSTRAINT FOREIGN KEY (self_reference_record_id) REFERENCES self_reference_record(id);
