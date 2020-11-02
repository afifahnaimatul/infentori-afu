-- Adminer 4.7.1 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `acc_bayar_hutang`;
CREATE TABLE `acc_bayar_hutang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(45) DEFAULT NULL,
  `m_kontak_id` int(11) DEFAULT NULL,
  `m_lokasi_id` int(11) DEFAULT NULL,
  `m_akun_id` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `keterangan` text,
  `total` int(11) DEFAULT NULL,
  `denda` int(11) DEFAULT NULL,
  `m_akun_denda_id` int(11) DEFAULT NULL,
  `tgl_mulai` date DEFAULT NULL,
  `tgl_selesai` date DEFAULT NULL,
  `status_hutang` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `modified_at` int(11) DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `acc_bayar_hutang_det`;
CREATE TABLE `acc_bayar_hutang_det` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(45) DEFAULT NULL,
  `acc_bayar_hutang_id` int(11) NOT NULL,
  `acc_saldo_hutang_id` int(11) DEFAULT NULL,
  `m_akun_id` int(11) DEFAULT NULL,
  `sisa` int(11) DEFAULT NULL,
  `bayar` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `tanggal` int(11) DEFAULT NULL,
  `potongan` int(11) DEFAULT NULL,
  `catatan` text,
  `created_at` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- 2019-09-20 12:24:46
