ALTER TABLE `inv_pembelian`
ADD `bea_masuk` int(11) NULL,
ADD `ppn` int(11) NULL AFTER `bea_masuk`,
ADD `pph22` int(11) NULL AFTER `ppn`,
ADD `denda_pabean` int(11) NULL AFTER `pph22`,
ADD `pelabuhan_ppn` int(11) NULL AFTER `denda_pabean`,
ADD `pelabuhan_non_ppn` int(11) NULL AFTER `pelabuhan_ppn`;