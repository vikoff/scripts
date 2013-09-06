

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
 * xdebug_trace_sessions
 * таблица зависит от:
 * от таблицы зависят: xdebug_trace
 */
DROP TABLE IF EXISTS `xdebug_trace_sessions`;
CREATE TABLE `xdebug_trace_sessions` (
	`id`            INT UNSIGNED AUTO_INCREMENT,
	`db_table`      TEXT,
	`application`   TEXT,
	`request_url`   TEXT,
	`app_base_path` TEXT,
	`total_memory`  INT,
	`total_time`    FLOAT,
	`total_calls`   INT,
	`comments`      TEXT,
	`created_at`    TIMESTAMP DEFAULT NOW(),
	PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

/*
 * xdebug_trace
 * таблица зависит от: xdebug_trace_sessions
 * от таблицы зависят:
 */
DROP TABLE IF EXISTS `xdebug_trace`;
CREATE TABLE `xdebug_trace` (
	`id`               INT UNSIGNED AUTO_INCREMENT,
	`sess_id`          INT UNSIGNED NOT NULL,
	`level`            SMALLINT NOT NULL,
	`call_index`       INT NOT NULL,
	`time_start`       FLOAT,
	`time_end`         FLOAT,
	`memory_start`     INT COMMENT 'memory on entering function',
	`memory_end`       INT COMMENT 'memory on leaving function',
	`func_name`        TEXT,
	`user_defined`     TINYINT(1) NOT NULL,
	`included_file`    TEXT,
	`call_file`        TEXT,
	`call_line`        INT,
	`num_args`         SMALLINT,
	`args`             TEXT,
	`parent_func_id`   INT,
	`num_nested_calls` INT,
	`comments`         TEXT,
	`created_at`       TIMESTAMP DEFAULT NOW(),
	PRIMARY KEY (`id`),
	KEY `fk_xdebug_trace_1` (`sess_id`),
	CONSTRAINT `fk_xdebug_trace_1`
		FOREIGN KEY (`sess_id`)
		REFERENCES `xdebug_trace_sessions` (`id`)
		ON DELETE CASCADE
		ON UPDATE NO ACTION
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
