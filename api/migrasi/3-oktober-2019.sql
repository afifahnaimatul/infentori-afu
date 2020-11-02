ALTER TABLE `acc_t_pengajuan` CHANGE `tanggal` `tanggal` DATE NULL DEFAULT NULL;
ALTER TABLE `acc_t_pengajuan` ADD `approval` INT(2) NOT NULL AFTER `levelapproval`;
ALTER TABLE `acc_pengeluaran_det` ADD `m_lokasi_id` INT NOT NULL AFTER `m_akun_id`;
ALTER TABLE `acc_pemasukan_det` ADD `m_lokasi_id` INT NOT NULL AFTER `keterangan`;
ALTER TABLE `acc_pemasukan`
  DROP `m_jenis_pembayaran_id`,
  DROP `m_unker_id`;
ALTER TABLE `acc_pengeluaran`
  DROP `m_jenis_pembayaran_id`;
ALTER TABLE `acc_pemasukan` ADD `keterangan` VARCHAR(255) NOT NULL AFTER `status`;
ALTER TABLE `acc_pemasukan` CHANGE `keterangan` `keterangan` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `acc_trans_detail` DROP `m_unker_id`;
ALTER TABLE `acc_trans_detail` ADD `m_lokasi_jurnal_id` INT NOT NULL AFTER `m_lokasi_id`;
ALTER TABLE `acc_t_pengajuan` ADD `tanggal_approve` DATE NULL DEFAULT NULL AFTER `approval`;
