CREATE TABLE foo (
  id         BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name       VARCHAR(40),
  `order`    INT,
  bar_id     INTEGER UNSIGNED,
  is_enabled BOOL,
  type       VARCHAR(10),
  `float`    FLOAT(5, 5),
  `double`   DOUBlE(5, 5),
  `decimal`  DECIMAL(5, 5),
  `datetime` DATETIME
);
INSERT INTO
  foo
  (name, bar_id, `order`, is_enabled, type, `float`, `double`, `decimal`, `datetime`)
VALUES
  ('foo_1', 2, 3, TRUE, '4', 0.1, 0.2, 0.3, '2011-01-01 22:17:17'),
  ('foo_2', 1, 1, TRUE, '5', 0.4, 0.5, 0.6, '2011-01-01 22:17:16'),
  ('foo_3', 2, 2, FALSE, '6', 0.7, 0.8, 0.9, '2011-01-01 22:17:17'),
  ('foo_4', 1, 2, FALSE, '7', 0.10, 0.11, 0.12, '2011-01-01 22:17:17'),
  ('foo_5', 4, 3, TRUE, '7', 0.12, 0.14, 0.15, '2011-01-01 22:17:17');


CREATE TABLE bar (
  id     BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name   VARCHAR(30),
  baz_id INTEGER UNSIGNED
);
INSERT INTO
  bar
  (name, baz_id)
VALUES
  ('bar_1', 3),
  ('bar_2', 2),
  ('bar_3', 3),
  ('bar_4', 4);


CREATE TABLE baz (
  id   BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(30)
);

INSERT INTO
  baz
  (name)
VALUES
  ('baz_1'),
  ('baz_2'),
  ('baz_3'),
  ('baz_4');

CREATE TABLE type_table (
  bit          BIT,
  `tinyint`    TINYINT,
  `bool`       BOOL,
  `smallint`   SMALLINT,
  `mediumint`  MEDIUMINT,
  `integer`    INTEGER,
  `bigint`     BIGINT,
  `decimal`    DECIMAL(5,4),
  `float`      FLOAT,
  `double`     DOUBLE,
  `date`       DATE,
  `datetime`   DATETIME,
  `timestamp`  TIMESTAMP,
  `time`       TIME,
  `year`       YEAR,
  `varchar`    VARCHAR(200),
  `char`       CHAR(20),
  `text`       TEXT,
  `enum`       ENUM ('a', 'b'),
  `set`        SET ('a', 'b'),
  `tinyblob`   TINYBLOB,
  `blob`       BLOB,
  `mediumblob` MEDIUMBLOB,
  `longblob`   LONGBLOB,
  `binary`     BINARY(100),
  `varbinary`  VARBINARY(20)
);

INSERT INTO type_table (
  bit,
  `tinyint`,
  bool,
  `smallint`,
  `mediumint`,
  `integer`,
  `bigint`,
  `decimal`,
  `float`,
  `double`,
  date,
  datetime,
  timestamp,
  time,
  year,
  `varchar`,
  `char`,
  text,
  enum,
  `set`,
  `tinyblob`,
  `blob`,
  `mediumblob`,
  `longblob`,
  `binary`,
  `varbinary`
) VALUES (
  1, 2, 1, 4, 5, 7, 8, 9.3, 9.4, 9.5,
  '2017-01-02', '2017-01-02 00:00:00',
  '2017-01-02 00:00:01', '00:00:01', 2017, 'varchar', 'char', 'text',
  'a', 'a,b', 'tinyblob',
  'blob', 'mediumblob', 'longblob', 'binary', 'varbinary'
)
