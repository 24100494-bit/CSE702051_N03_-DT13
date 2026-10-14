-- Buoi 09 - chi muc ho tro lay ban nop moi nhat theo tung moc cua mot de tai.
-- Chay trong dung database cua project. Script an toan khi chay lai tren MySQL 8 / MariaDB.
SET @idx_exists = (
    SELECT COUNT(*)
      FROM information_schema.statistics
     WHERE table_schema = DATABASE()
       AND table_name = 'bao_cao_moc'
       AND index_name = 'idx_baocao_detai_moc_created_id'
);
SET @idx_sql = IF(
    @idx_exists = 0,
    'CREATE INDEX idx_baocao_detai_moc_created_id ON bao_cao_moc (de_tai_id, moc_thoi_gian_id, created_at, id)',
    'SELECT ''idx_baocao_detai_moc_created_id da ton tai'' AS ket_qua'
);
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;
