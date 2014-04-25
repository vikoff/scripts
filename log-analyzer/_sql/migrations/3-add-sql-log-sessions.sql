
ALTER TABLE sql_log
	ADD COLUMN `sess_id` INT UNSIGNED AFTER id,
	ADD INDEX `fk_sess_id` (`sess_id`),
	ADD CONSTRAINT `fk_sql_log_sess_id` FOREIGN KEY (`sess_id`) REFERENCES `sql_log_sessions` (`id`)
	ON DELETE CASCADE ON UPDATE NO ACTION;