-- SQLITE3 data types: http://www.sqlite.org/datatype3.html

CREATE TABLE `blog_post` (
  `id` integer PRIMARY KEY AUTOINCREMENT,
  `time` text NOT NULL,
  `subject` text NOT NULL,
  `body` text NOT NULL,
  `security` text NOT NULL
  );

