SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `acc_t_pengajuan_det2`;
CREATE TABLE `acc_t_pengajuan_det2` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `acc_t_pengajuan_det_id` int(11) NOT NULL,
  `keterangan` text,
  `budget` int(11) DEFAULT NULL,
  `realisasi` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `acc_t_pengajuan_det2`
ADD `acc_t_pengajuan_id` int NOT NULL AFTER `id`;

ALTER TABLE `acc_t_pengajuan_det2`
CHANGE `acc_t_pengajuan_id` `t_pengajuan_id` int(11) NOT NULL AFTER `id`,
CHANGE `acc_t_pengajuan_det_id` `t_pengajuan_det_id` int(11) NOT NULL AFTER `t_pengajuan_id`;

ALTER TABLE `acc_saldo_piutang`
ADD `kode` varchar(50) NOT NULL AFTER `id`,
ADD `no_invoice` varchar(50) NOT NULL AFTER `kode`,
ADD `m_akun_piutang_id` int(11) NOT NULL AFTER `m_akun_id`,
CHANGE `tanggal` `tanggal` date NOT NULL AFTER `m_akun_piutang_id`,
ADD `jatuh_tempo` date NOT NULL AFTER `tanggal`,
ADD `keterangan` text NOT NULL AFTER `total`,
ADD `status_hutang` varchar(20) NOT NULL AFTER `keterangan`,
ADD `status` varchar(20) NOT NULL AFTER `status_hutang`;

ALTER TABLE `acc_saldo_piutang`
CHANGE `no_invoice` `no_invoice` varchar(50) COLLATE 'latin1_swedish_ci' NULL AFTER `kode`,
CHANGE `keterangan` `keterangan` text COLLATE 'latin1_swedish_ci' NULL AFTER `total`;