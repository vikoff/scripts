
ALTER TABLE xdebug_trace_sessions ADD COLUMN `process_percent` TINYINT AFTER `comments`;
ALTER TABLE xdebug_trace_sessions ADD COLUMN `processed_at` TIMESTAMP NULL AFTER `created_at`;
ALTER TABLE xdebug_trace ADD INDEX (`sess_id`, `call_index`);

ALTER TABLE xdebug_trace_sessions ADD COLUMN project_id INT UNSIGNED AFTER `application`;
ALTER TABLE xdebug_trace_sessions ADD KEY `fk_xdebug_trace_sessions_1` (`project_id`);
ALTER TABLE xdebug_trace_sessions ADD CONSTRAINT `fk_xdebug_trace_sessions_1`
	FOREIGN KEY (`project_id`)
	REFERENCES `xdebug_projects` (`id`)
		ON DELETE CASCADE
		ON UPDATE NO ACTION;

ALTER TABLE xdebug_trace_sessions
	DROP COLUMN `db_table`,
	DROP COLUMN `application`,
	DROP COLUMN `app_base_path`;

