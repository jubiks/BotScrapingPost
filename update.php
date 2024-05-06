<?php

require __DIR__ . '/core/settings.php';
require __DIR__ . '/core/database.php';

$DB = new DataBase();

$sql = "
CREATE TABLE tgstat_editors (
  id bigint NOT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
CHARACTER SET utf8mb4,
COLLATE utf8mb4_unicode_520_ci;
";

$DB->query($sql, $error);
if (!empty($error)) {
    die($error);
}

die('Update success!');
