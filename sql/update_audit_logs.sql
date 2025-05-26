-- Modify the audit_logs table to allow NULL values for user_id
ALTER TABLE audit_logs DROP FOREIGN KEY audit_logs_ibfk_1;
ALTER TABLE audit_logs MODIFY user_id INT NULL;
ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_ibfk_1 
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL; 