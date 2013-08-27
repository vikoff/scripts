

/*
 * access_log
 * таблица зависит от: 
 * от таблицы зависят: 
 */
DROP TABLE IF EXISTS `access_log`;
CREATE TABLE `access_log` (
	`id`              INT UNSIGNED AUTO_INCREMENT,
	`remote_addr`     VARCHAR(32),
	`remote_user`     VARCHAR(32),
	`time_local`      TIMESTAMP NULL DEFAULT NULL,
	`request`         TEXT,
	`reduced_request` TEXT,
	`http_status`     INT,
	`bytes_sent`      INT,
	`referer`         VARCHAR(512),
	`user_agent`      VARCHAR(512),
	`created_at`      TIMESTAMP DEFAULT NOW(),
	PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

/*
 * sql_log
 * таблица зависит от:
 * от таблицы зависят:
 */
DROP TABLE IF EXISTS `sql_log`;
CREATE TABLE `sql_log` (
	`id`            INT UNSIGNED AUTO_INCREMENT,
	`conn_id`       TINYINT,
	`command`       VARCHAR(10),
	`date`          TIMESTAMP NULL COMMENT 'sql exec datetime',
	`sql`           TEXT,
	`sql_reduced`   TEXT,
	`sql_type`      VARCHAR(10) COMMENT 'insert, delete, update, etc',
	`created_at`    TIMESTAMP DEFAULT NOW(),
	PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

/*
 * ____
 * таблица зависит от:
 * от таблицы зависят:
 */
DROP TABLE IF EXISTS `___`;
CREATE TABLE `___` (
	`id`            INT UNSIGNED AUTO_INCREMENT,
	`created_at`    TIMESTAMP DEFAULT NOW(),
	PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
