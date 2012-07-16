CREATE TABLE test_table_02 (id BIGINT, simple_record_id BIGINT, varchar_field1 VARCHAR(25), column_03 VARCHAR(25), PRIMARY KEY(id));
CREATE TABLE self_reference_record (id BIGINT, varchar_field VARCHAR(50), self_reference_record_id BIGINT, PRIMARY KEY(id));
CREATE TABLE simple_record (id BIGSERIAL, varchar_field VARCHAR(25) DEFAULT 'default', text_field TEXT, integer_field BIGINT DEFAULT 0, boolean_field BOOLEAN DEFAULT 'false', date_field DATE, time_field TIME, timestamp_field TIMESTAMP, PRIMARY KEY(id));
CREATE SEQUENCE test_table_03_seq_seq INCREMENT 1 START 1;
ALTER TABLE test_table_02 ADD CONSTRAINT test_table_02_simple_record_id_simple_record_id FOREIGN KEY (simple_record_id) REFERENCES simple_record(id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE self_reference_record ADD CONSTRAINT sssi FOREIGN KEY (self_reference_record_id) REFERENCES self_reference_record(id) NOT DEFERRABLE INITIALLY IMMEDIATE;
