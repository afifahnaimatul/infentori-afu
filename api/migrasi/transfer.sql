ALTER TABLE `acc_transfer` ADD `m_lokasi_asal_id` INT NOT NULL AFTER `m_lokasi_id`;
ALTER TABLE `acc_transfer` CHANGE `m_lokasi_id` `m_lokasi_tujuan_id` INT(11) NULL DEFAULT NULL;
