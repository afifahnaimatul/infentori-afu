/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  miku
 * Created: Sep 19, 2019
 */

ALTER TABLE `acc_saldo_hutang`
CHANGE `tanggal` `tanggal` date NOT NULL AFTER `m_akun_id`,
ADD `jatuh_tempo` date NOT NULL AFTER `tanggal`;

ALTER TABLE `acc_saldo_hutang`
ADD `status_hutang` varchar(20) NOT NULL AFTER `total`,
ADD `status` varchar(20) NOT NULL AFTER `status_hutang`;

ALTER TABLE `acc_saldo_hutang`
ADD `m_akun_hutang_id` int(11) NOT NULL AFTER `m_akun_id`;

ALTER TABLE `acc_saldo_hutang`
ADD `no_invoice` int(11) NOT NULL AFTER `id`;

ALTER TABLE `acc_saldo_hutang`
ADD `keterangan` text NOT NULL AFTER `total`;

ALTER TABLE `acc_saldo_hutang`
CHANGE `no_invoice` `no_invoice` int(11) NULL AFTER `kode`,
CHANGE `m_akun_hutang_id` `m_akun_hutang_id` int(11) NULL AFTER `m_akun_id`,
CHANGE `jatuh_tempo` `jatuh_tempo` date NULL AFTER `tanggal`,
CHANGE `keterangan` `keterangan` text COLLATE 'latin1_swedish_ci' NULL AFTER `total`;

ALTER TABLE `acc_saldo_hutang`
ADD `kode` varchar(50) NOT NULL AFTER `id`,