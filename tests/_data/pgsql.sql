CREATE TABLE table1 (
  id             BIGSERIAL PRIMARY KEY,
  "varchar"      VARCHAR(100),
  "int_nullable" INT DEFAULT NULL
);

INSERT INTO table1 ("varchar", "int_nullable") VALUES
  ('null', NULL),
  ('ten', 10);