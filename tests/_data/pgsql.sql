CREATE TABLE foo (
  id         SERIAL PRIMARY KEY,
  name       VARCHAR(40),
  "order"    INT,
  bar_id     INTEGER,
  is_enabled BOOLEAN,
  type       VARCHAR(10),
  "float"    FLOAT(5),
  "double"   DOUBLE PRECISION,
  "decimal"  DECIMAL(5, 5),
  datetime   TIMESTAMP WITHOUT TIME ZONE
);
INSERT INTO
  foo
  (name, bar_id, "order", is_enabled, type, "float", "double", "decimal", datetime)
VALUES
  ('foo_1', 2, 3, TRUE, '4', 0.1, 0.2, 0.3, '2011-01-01 22:17:17'),
  ('foo_2', 1, 1, TRUE, '5', 0.4, 0.5, 0.6, '2011-01-01 22:17:16'),
  ('foo_3', 2, 2, FALSE, '6', 0.7, 0.8, 0.9, '2011-01-01 22:17:17'),
  ('foo_4', 1, 2, FALSE, '7', 0.10, 0.11, 0.12, '2011-01-01 22:17:17'),
  ('foo_5', 4, 3, TRUE, '7', 0.12, 0.14, 0.15, '2011-01-01 22:17:17');


CREATE TABLE bar (
  id       SERIAL PRIMARY KEY,
  name     VARCHAR(30) DEFAULT 'bar_0',
  baz_id   INTEGER,
  baz_name VARCHAR(10),
  baz_type VARCHAR(10)
);
INSERT INTO
  bar
  (name, baz_id, baz_name, baz_type)
VALUES
  ('bar_1', 3,'baz_1', 'g_2'),
  ('bar_2', 2,'baz_2', 'g_1'),
  ('bar_3', 3,'baz_1', 'g_2'),
  ('bar_4', 4,'baz_4', 'g_2');


CREATE TABLE baz (
  id   SERIAL PRIMARY KEY,
  name VARCHAR(30),
  type VARCHAR(30)
);

INSERT INTO
  baz
  (name, type)
VALUES
  ('baz_1', 'g_2'),
  ('baz_2', 'g_1'),
  ('baz_2', 'g_1'),
  ('baz_3', 'g_2'),
  ('baz_4', 'g_1');

CREATE TYPE test_enum as ENUM ('one', 'two');
CREATE TABLE type_table (
  smallint          SMALLINT,
  integer           INTEGER,
  bigint            BIGINT,
  decimal           DECIMAL,
  numeric           NUMERIC,
  real              REAL,
  double_precission DOUBLE PRECISION,
  smallserial       SMALLSERIAL,
  serial            SERIAL,
  bigserial         BIGSERIAL,
  money             MONEY,
  char_var          character varying,
  char              CHARACTER(10),
  text              TEXT,
  bytea             BYTEA,
  timestamp         timestamp,
  timestamptz       TIMESTAMP WITH TIME ZONE,
  date              DATE,
  time              TIME,
  timetz            TIME WITH TIME ZONE,
  interval          INTERVAL,
  boolean           BOOLEAN,
  enum              test_enum
);

INSERT INTO type_table (
  smallint,
  integer,
  bigint,
  decimal,
  numeric,
  real,
  double_precission,
  money,
  char_var,
  char,
  text,
  bytea,
  timestamp,
  timestamptz,
  date,
  time,
  timetz,
  interval,
  boolean,
  enum)
VALUES (
  1, 2, 3, 4.1, 4.2, 4.3, 4.4, 10.3, 'char_var', 'char', 'text',
  '123', '2017-01-01 22:01:02', '2017-01-01 22:01:03',
  '2017-01-01', '22:01:02', '22:01:03', '35', FALSE, 'two'
);


CREATE TABLE multi_pk (
  id   INTEGER,
  type INTEGER,
  tag  VARCHAR(10),
  PRIMARY KEY (id, type)
);

INSERT INTO multi_pk (id, type, tag)
VALUES
  (1, 2, '1-2'),
  (2, 2, '2-2'),
  (1, 3, '1-3'),
  (2, 1, '2-1'),
  (3, 3, '1-3'),
  (2, 3, '2-2')