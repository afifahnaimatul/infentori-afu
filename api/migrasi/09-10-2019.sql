ALTER TABLE `acc_m_akun` ADD `is_induk` BOOLEAN NOT NULL DEFAULT FALSE AFTER `level`;
update acc_m_akun set is_induk = 1 where parent_id = 0 or parent_id is null;