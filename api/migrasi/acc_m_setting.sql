-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 01 Okt 2019 pada 18.05
-- Versi server: 5.7.21
-- Versi PHP: 7.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `landa_ukdc`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `acc_m_setting`
--

DROP TABLE IF EXISTS `acc_m_setting`;
CREATE TABLE `acc_m_setting` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `pengecualian_labarugi` text NOT NULL,
  `pengecualian_neraca` text NOT NULL,
  `print_pengajuan` text NOT NULL,
  `print_penerimaan` text NOT NULL,
  `print_pengeluaran` text NOT NULL,
  `print_jurnal` text NOT NULL,
  `format_pemasukan` text NOT NULL,
  `format_pengeluaran` text NOT NULL,
  `format_transfer` text NOT NULL,
  `format_jurnal` text NOT NULL,
  `format_pengajuan` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `acc_m_setting`
--

INSERT INTO `acc_m_setting` (`id`, `tanggal`, `pengecualian_labarugi`, `pengecualian_neraca`, `print_pengajuan`, `print_penerimaan`, `print_pengeluaran`, `print_jurnal`, `format_pemasukan`, `format_pengeluaran`, `format_transfer`, `format_jurnal`, `format_pengajuan`) VALUES
(1, '2020-01-01', '', '', '<div style=\"text-align:center\"><span style=\"font-size:30px\"><strong>RAIN PROPTECH</strong></span></div>\n\n<hr />\n<p>Nomor : {{data.no_proposal}}</p>\n\n<p>Lampiran :</p>\n\n<p>Hal : {{data.perihal}}</p>\n\n<p>A. DASAR PENGAJUAN</p>\n\n<table border=\"1\" bordercolor=\"#ccc\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse:collapse; width:700px\">\n	<tbody>\n		<tr>\n			<td>{{data.dasar_pengajuan}}</td>\n		</tr>\n	</tbody>\n</table>\n\n<p>B. ANGGARAN</p>\n\n<p>&nbsp; &nbsp; PERKIRAAN ANGGARAN : {{data.jumlah_perkiraan}}</p>\n\n<p>&nbsp; &nbsp; DETAIL&nbsp;</p>\n\n<table border=\"1\" bordercolor=\"#ccc\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse:collapse; width:700px\">\n	<tbody>\n		<tr>\n			<td>No.</td>\n			<td>Uraian</td>\n			<td>Jenis Satuan</td>\n			<td>Jumlah</td>\n			<td>Harga Satuan</td>\n			<td>Subtotal</td>\n		</tr>\n		<tr>\n			<td>{start_detail}</td>\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td>{{val.no}}</td>\n			<td>{{val.keterangan}}</td>\n			<td>@{{val.jenis_satuan}}</td>\n			<td>{{val.jumlah}}</td>\n			<td>{{val.harga_satuan}}</td>\n			<td>{{val.jumlah * val.harga_satuan}}</td>\n		</tr>\n		<tr>\n			<td>{end}</td>\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n	</tbody>\n</table>\n\n<p>C. CATATAN</p>\n\n<table border=\"1\" bordercolor=\"#ccc\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse:collapse; width:700px\">\n	<tbody>\n		<tr>\n			<td>{{data.catatan}}</td>\n		</tr>\n	</tbody>\n</table>\n\n<p>D. LEMBAR PERSETUJUAN</p>\n\n<p>Malang, {{data.tanggal_formated}}</p>\n\n<table border=\"1\" bordercolor=\"#ccc\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse:collapse; width:700px\">\n	<tbody>\n		<tr>\n			<td style=\"text-align:center\">Pemohon</td>\n			<td>{start_acc}</td>\n			<td>\n			<p style=\"text-align:center\">Disetujui oleh :</p>\n\n			<p style=\"text-align:center\">{{val.sebagai}}</p>\n			</td>\n			<td>{end}</td>\n		</tr>\n		<tr>\n			<td style=\"text-align:center\">\n			<p>&nbsp;</p>\n\n			<p>&nbsp;</p>\n\n			<p>&nbsp;</p>\n\n			<p>&nbsp;</p>\n			</td>\n			<td>{start_acc}</td>\n			<td style=\"text-align:center\">\n			<p>&nbsp;</p>\n\n			<p>&nbsp;</p>\n\n			<p>&nbsp;</p>\n\n			<p>&nbsp;</p>\n			</td>\n			<td>{end}</td>\n		</tr>\n	</tbody>\n</table>\n\n<p>&nbsp;</p>\n', '<table border=\"1\" bordercolor=\"#ccc\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse:collapse\"> 	<tbody> 		<tr> 			<td colspan=\"3\" style=\"width:25%\">{{data.m_lokasi_id.nama}}<br /> 			{{data.no_transaksi}}<br /> 			{{data.tanggal}}</td> 			<td colspan=\"6\" style=\"width:50%\">PENERIMAAN<br /> 			{{data.m_akun_id.nama}}</td> 			<td colspan=\"3\" style=\"width:25%\">Dicetak Pada :<br /> 			{{data.tanggalsekarang}}</td> 		</tr> 		<tr> 			<td colspan=\"12\">Diterima Dari : {{data.namaCus}}</td> 		</tr> 		<tr> 			<td colspan=\"3\" style=\"width:18%\">Perkiraan</td> 			<td colspan=\"6\">Keterangan</td> 			<td colspan=\"3\">Jumlah</td> 		</tr> 		<tr> 			<td>{start_detail}</td> 		</tr> 		<tr> 			<td colspan=\"3\" style=\"width:18%\">{{val.m_akun_id.kode}} - {{val.m_akun_id.nama}}</td> 			<td colspan=\"6\" style=\"width:50%\">{{val.keterangan}}</td> 			<td colspan=\"3\">{{val.kredit}}</td> 		</tr> 		<tr> 			<td>{end}</td> 		</tr> 		<tr> 			<td colspan=\"9\" style=\"width:75%\">Total</td> 			<td colspan=\"3\" style=\"width:25%\">{{data.total}}</td> 		</tr> 		<tr> 			<td colspan=\"4\" style=\"border-right:hidden\">Menyetujui</td> 			<td colspan=\"4\" style=\"border-right:hidden\">Menerima</td> 			<td colspan=\"4\">Petugas</td> 		</tr> 		<tr> 			<td colspan=\"12\"><br /> 			<br /> 			<br /> 			&nbsp;</td> 		</tr> 	</tbody> </table>', '<table border=\"1\" bordercolor=\"#ccc\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse:collapse; width:800px\">\n	<tbody>\n		<tr>\n			<td style=\"text-align:center; width:200px\"><span style=\"font-family:Courier New,Courier,monospace\">{{data.m_lokasi_id.nama}}<br />\n			{{data.no_transaksi}}<br />\n			{{data.tanggal}}</span></td>\n			<td style=\"text-align:center\"><span style=\"font-family:Courier New,Courier,monospace\">PENGELUARAN<br />\n			{{data.m_akun_id.nama}}</span></td>\n			<td style=\"text-align:center; width:200px\"><span style=\"font-family:Courier New,Courier,monospace\">Dicetak Pada :<br />\n			{{data.tanggalsekarang}}</span></td>\n		</tr>\n	</tbody>\n</table>\n\n<p><span style=\"font-family:Courier New,Courier,monospace\">Dibayarkan Kepada : {{data.penerima}}</span></p>\n\n<table border=\"1\" bordercolor=\"#ccc\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse:collapse; width:800px\">\n	<tbody>\n		<tr>\n			<td style=\"width:200px\"><span style=\"font-family:Courier New,Courier,monospace\">Perkiraan</span></td>\n			<td><span style=\"font-family:Courier New,Courier,monospace\">Keterangan</span></td>\n			<td style=\"width:200px\"><span style=\"font-family:Courier New,Courier,monospace\">Jumlah</span></td>\n		</tr>\n	</tbody>\n</table>\n\n<pre>\n<span style=\"font-family:Courier New,Courier,monospace\">{start_detail}</span></pre>\n\n<table border=\"1\" bordercolor=\"#ccc\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse:collapse; width:800px\">\n	<tbody>\n		<tr>\n			<td style=\"width:200px\"><span style=\"font-family:Courier New,Courier,monospace\">{{val.m_akun_id.kode}}</span></td>\n			<td><span style=\"font-family:Courier New,Courier,monospace\">{{val.keterangan}}</span></td>\n			<td style=\"text-align:right; width:200px\"><span style=\"font-family:Courier New,Courier,monospace\">{{val.debit}}</span></td>\n		</tr>\n	</tbody>\n</table>\n\n<pre>\n<span style=\"font-family:Courier New,Courier,monospace\">{end}</span></pre>\n\n<table border=\"1\" bordercolor=\"#ccc\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse:collapse; width:800px\">\n	<tbody>\n		<tr>\n			<td><span style=\"font-family:Courier New,Courier,monospace\">{{data.terbilang}}</span></td>\n			<td style=\"text-align:right; width:200px\"><span style=\"font-family:Courier New,Courier,monospace\">{{data.total}}</span></td>\n		</tr>\n	</tbody>\n</table>\n\n<table border=\"1\" bordercolor=\"#ccc\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse:collapse; width:800px\">\n	<tbody>\n		<tr>\n			<td style=\"width:200px\">\n			<p style=\"text-align:center\"><span style=\"font-family:Courier New,Courier,monospace\">Pembukuan</span></p>\n\n			<p style=\"text-align:center\">&nbsp;</p>\n\n			<p style=\"text-align:center\">&nbsp;</p>\n\n			<p style=\"text-align:center\">&nbsp;</p>\n			</td>\n			<td style=\"width:200px\">\n			<p style=\"text-align:center\"><span style=\"font-family:Courier New,Courier,monospace\">Menyetujui</span></p>\n\n			<p style=\"text-align:center\">&nbsp;</p>\n\n			<p style=\"text-align:center\">&nbsp;</p>\n\n			<p style=\"text-align:center\">&nbsp;</p>\n			</td>\n			<td style=\"width:200px\">\n			<p style=\"text-align:center\"><span style=\"font-family:Courier New,Courier,monospace\">Menerima</span></p>\n\n			<p style=\"text-align:center\">&nbsp;</p>\n\n			<p style=\"text-align:center\">&nbsp;</p>\n\n			<p style=\"text-align:center\">&nbsp;</p>\n			</td>\n			<td style=\"width:200px\">\n			<p style=\"text-align:center\"><span style=\"font-family:Courier New,Courier,monospace\">Kasir</span></p>\n\n			<p style=\"text-align:center\">&nbsp;</p>\n\n			<p style=\"text-align:center\">&nbsp;</p>\n\n			<p style=\"text-align:center\">&nbsp;</p>\n			</td>\n		</tr>\n	</tbody>\n</table>\n\n<p>&nbsp;</p>\n\n<p>&nbsp;</p>\n\n<p>&nbsp;</p>\n', '<table border=\"1\">     <tr>         <td colspan=\"3\" style=\"width: 25%\" class=\"tengah\">             {{data.m_lokasi_id.nama}}             <br>             {{data.no_transaksi}}             <br>             {{data.tanggal | date(\'d M Y\')}}         </td>         <td colspan=\"6\" style=\"width: 50%\" class=\"tengah\">JURNAL UMUM</td>         <td colspan=\"3\" style=\"width: 25%\" class=\"tengah\">             Dicetak Pada :             <br>             {{data.tanggalsekarang}}         </td>     </tr>     <tr>         <td colspan=\"12\">Keterangan :  {{data.keterangan}}</td><br><br><br>     </tr>     <tr>         <!--<td style=\"width: 7%\">No. </td>-->         <td style=\"width: 18%\"class=\"tengah\" colspan=\"3\">Perkiraan</td>         <td class=\"tengah\" colspan=\"5\">Keterangan</td>         <td class=\"tengah\" colspan=\"2\">Debit</td>         <td class=\"tengah\" colspan=\"2\">Kredit</td>     </tr>     <tr><td>{start_detail}</td></tr>     <tr >         <!--<td class=\"tengah\" style=\"width: 7%\">{{val.no}}</td>-->         <td class=\"tengah\" style=\"width: 18%\" colspan=\"3\">{{val.m_akun_id.kode}} - {{val.m_akun_id.nama}}</td>         <td colspan=\"5\" style=\"width: 40%\">{{val.keterangan}}</td>         <td class=\"angka\" colspan=\"2\">{{val.debit}}</td>         <td class=\"angka\" colspan=\"2\">{{val.kredit}}</td>     </tr>     <tr><td>{end}</td></tr>     <tr>         <td colspan=\"8\" style=\"width: 50%\">Total         </td>         <td class=\"angka\" colspan=\"2\" >{{data.total_debit}}</td>         <td class=\"angka\" colspan=\"2\" >{{data.total_kredit}}</td>     </tr>     <tr style=\"border-bottom:hidden\">         <td class=\"tengah\" colspan=\"4\" style=\"border-right:hidden\">Menyetujui </td>         <td class=\"tengah\" colspan=\"4\" style=\"border-right:hidden\">Menerima </td>         <td class=\"tengah\" colspan=\"4\">Petugas </td>     </tr>     <tr>         <td colspan=\"12\">             <br>               <br>               <br>               <br>           </td>     </tr> </table>', 'TAHUN/PMSK/KODEPRODI/NOURUT', 'TAHUN/BULAN/KODEPRODI/KLR/NOURUT', 'TAHUN/TRNS/KODEPRODI/NOURUT', 'TAHUN/JRNL/KODEPRODI/NOURUT', 'TAHUN/PNGJ/KODEPRODI/NOURUT');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `acc_m_setting`
--
ALTER TABLE `acc_m_setting`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `acc_m_setting`
--
ALTER TABLE `acc_m_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
