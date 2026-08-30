<?php
header('Content-Type: text/plain; charset=utf-8');
$root = __DIR__;
require_once $root . '/db.php';

echo "=== LARAS SEED 177 PEGAWAI EXCEL USER (v2 FIX unit_kerja + role) ===\n\n";

$default_password = password_hash('password', PASSWORD_BCRYPT);

$existingNipMap = [
    'Luthfi Jauhari' => '19850210 200901 1 002',
    'Mulyadi' => '19861118 200901 1 001',
    'Rumbati Argo' => '19870125 200911 2 001',
    'Arsa Nur Azhari Winarso' => '19870305 200911 1 001',
    'Dyah Qonitasari Estyamilla' => '19870412 200911 2 001',
    'Hani Zulniati' => '19870521 200911 2 001',
    'Danie Yanuar' => '19860128 201012 2 001',
    'Puspita Dewi Putri' => '19880820 201012 2 001',
    'Syah Mahardika' => '19890426 201012 1 001',
    'Agus Budi Laksono' => '19870816 201012 1 001',
    'Riski Lukfiarini' => '19881017 201210 2 001',
    'Mandala Ulul Amri' => '19881125 201210 1 001',
    'Cahyo Dwi Sabdono' => '19890514 201210 1 001',
    'Asri Primandari' => '19900102 201210 2 001',
    'Fandy Prakasa Wardhana' => '19900204 201210 1 001',
    'Abu Achmad' => '19900321 201210 1 001',
    'Aditya Tri Rahmadi Putra' => '19900422 201210 1 001',
    'Sani Nurbani' => '19900906 201210 1 002',
    'Enggar Nastanto' => '19900621 201210 1 001',
    'Juli Sarwanto' => '19900706 201210 1 001',
    'Anita Setianingtyas' => '19841121 201212 2 001',
    'Anita Setyaningtyas' => '19841121 201212 2 001',
    'Mareisca Yulistina Pratama' => '19890728 201212 2 002',
    'Asri Suwarsih' => '19880102 201212 2 001',
    'Usman Maulana' => '19890607 201212 1 002',
    'Fadlian Lazuardi Mulyono' => '19870821 201212 1 001',
    'Hanifiar Bima Retnanti' => '19880313 201212 2 002',
    'Lestariningsih' => '19871106 201212 2 002',
    'Oki Paramita' => '19901025 201212 2 002',
    'Wakhid Sulistio Adi' => '19860104 201402 1 002',
    'Indria Putriasari' => '19860302 201402 2 001',
    'Ngatini' => '19860404 201402 2 004',
    'Devita Febriani' => '19870216 201402 2 003',
    'Lenni Agustina' => '19870815 201402 2 003',
    'Rini Risnawati' => '19870920 201402 2 002',
    'Anggra Dewi Sekarningrum' => '19870922 201402 2 004',
    'Monika Jayatri' => '19871201 201402 2 004',
    'Ananta Singgih Cahya Prasetya' => '19871227 201402 1 001',
    'Nur Hanifah Hayyuningtyas' => '19880205 201402 2 003',
    'Tri Ana Fauziah' => '19880323 201402 2 003',
    'Cholifatul Husna' => '19880610 201402 2 004',
    'Yunita Evi Kurniasari' => '19880620 201402 2 001',
    'Nur Fita Sari' => '19881115 201402 2 005',
    'Desi Susanti' => '19881214 201402 2 003',
    'Widyawan Nugroho' => '19890219 201402 1 002',
    'Zulita Dyah Shintaningrum' => '19890424 201402 2 009',
    'Mega Yoga Prastika' => '19890609 201402 1 004',
    'Siti Muslikhah Kusuma Nurakhmadyati' => '19890714 201402 2 004',
    'Arum Ditha Safitri' => '19891121 201402 2 005',
    'Rizka Choirunnisa' => '19900518 201402 2 008',
    'Doni Kurniawan Subardo' => '19900531 201402 1 002',
    'Dewi Asih Kurnia' => '19900914 201402 2 003',
    'Winda Dyah Kinasih' => '19900918 201402 2 009',
    'Asri Oktaviani Puitri' => '19901028 201402 2 004',
    'Irene Linda Widiastuti' => '19901107 201402 2 008',
    'Sari Wahyuni' => '19910528 201402 2 002',
    'Puji Purnaweni' => '19910817 201402 2 002',
    'Raisha Pratidina' => '19910827 201402 2 002',
    'Fajar Cahyaning Sadarum' => '19911029 201402 2 004',
    'Noor Hanifah' => '19911223 201402 2 003',
    'Amaliyyah Raadhiyyata Mardhiyyah' => '19930617 201402 2 001',
    'Giri Firmansyah' => '19871128 201402 1 003',
    'Paramithasari R' => '19870607 201402 2 004',
    'Kurnia Yuspita' => '19910827 201402 2 003',
    'Galih Hapsari Kirana' => '19880229 201502 2 002',
    'Rizki Rusdhiani' => '19880828 201502 2 001',
    'Rikha Aditya Wardhani' => '19900325 201502 2 002',
    'Azizah Endrastaty' => '19900630 201502 2 002',
    'Dewi Kurniasari' => '19871118 201801 2 001',
    'Riana Widiastuti' => '19911110 201801 2 002',
    'Dini Susanti' => '19931205 201801 2 002',
    'Vuji Suprihatin' => '19930910 201801 2 001',
    'Halim Prawiranata' => '19940914 201801 1 002',
    'Ismu Adi Pranawa' => '19951127 201801 1 001',
    'Siti Roh Chayatun' => '19960305 201801 2 001',
    'Akhmad Pandu Kurnia' => '19970322 201812 1 001',
    'Erik Darmawan' => '19951106 201812 1 001',
    'Arin Ambar Setiarani' => '19950613 201902 2 008',
    'Chintria Tira Nadia' => '19960520 201902 2 002',
    'Dicky Ervyanto' => '19870717 201902 1 002',
    'Dinar Safir Fatikha' => '19940925 201902 2 003',
    'Naura Nadhifa' => '19961108 201902 2 003',
    'Nolita Ayu Puspitasari' => '19931017 201902 2 006',
    'Ratni Dewi' => '19950525 201902 2 010',
    'Rosa Rizki Agustina' => '19940803 201902 2 003',
    'Unik Novia Dara' => '19931116 201902 2 001',
    'Wahyu Nurwanto' => '19960126 201902 1 002',
    'Yenni K Nainggolan' => '19941027 201902 2 006',
    'Agus Cahyadi' => '19821224 202521 1 027',
    'Amin Idrus' => '19760928 202521 1 012',
    'Heri Susanto' => '19800819 202521 1 021',
    'Rochmad Susanto' => '19820318 202521 1 027',
    'Pargiyono' => '19851109 202521 1 013',
    'Sarwo Edi' => '19850118 202521 1 024',
    'Andrianto' => '19830904 202521 1 022',
    'Margiyanto' => '19850516 202521 1 037',
    'Marwadi' => '19840124 202521 1 022',
    'Agus Suranto' => '19740825 202521 1 014',
    'Kriswanto' => '19780603 202521 1 024',
    'Maryanto' => '19840331 202521 1 024',
    'Anang Hartaya' => '19781020 202521 1 022',
    'Parman' => '19720410 202521 1 023',
    'Fajar Pramono' => '19920707 202521 1 057',
    'Sudaryanta' => '19720215 202521 1 022',
    'Paryanto' => '19730102 202521 1 013',
    'Yundaris Filiyanto' => '19920605 202521 1 021',
    'Faisal Ansari' => '19950912 202012 1 008',
    'Risdiyanto' => '19790723 202521 1 020',
    'Endi Jayus' => '19841106 202521 1 019',
    'Upik Krismareta Nuratifa' => '19990326 202202 2 001',
    'Riska Heru Wibowo' => '19870310 202521 1 048',
    'Hudalil Mustofa' => '20011202 202521 1 004',
    'Wisnu Saputra' => '20010504 202521 1 005',
    'Yunika Permata Sari' => '19890612 202521 2 067',
    'Aninda Purba Cahyani' => '19971216 202421 2 024',
    'Noor Latifah Dachlan' => '19741110 202421 2 005',
    'RR Kun Alfiah Nur Aerodynamicawati' => '19900305 202421 2 034',
    'Raden Rara Kun Alfiah Nur Aerodynamicawati' => '19900305 202421 2 034',
    'Siti Solekah' => '19670225 198703 2 001',
    'Susetyo Gigih Trilaksono' => '19661126 198703 1 001',
    'Edi Prasetyo' => '19670705 198803 1 001',
    'Komaruz Zaman' => '19681107 198903 1 001',
    'Hary Eka Surjanta' => '19681024 198903 1 001',
    'Cukamnoto Hariyadi' => '19681224 198903 1 001',
    'Achmad Fachri' => '19680208 198903 1 001',
    'Bambang Yuliyanto' => '19680719 198903 1 001',
    'Azis Hanafi' => '19690510 199003 1 001',
    'Dessy Adin' => '19680107 199103 2 001',
    'Purnomo Aji' => '19701227 199103 1 001',
    'Eko Herman Budi Rahardjo' => '19710313 199103 1 001',
    'Bagus Widodo' => '19690301 199103 1 001',
    'Caecilia Hermawati' => '19720310 199202 2 001',
    'Suyatno' => '19710511 199202 1 001',
    'Franciscus Xaverius Sarwoko' => '19710203 199202 1 001',
    'Puji Estriningsih' => '19721104 199203 2 001',
    'Jun Suwarno' => '19720615 199303 1 001',
    'Ali Ihsan' => '19690820 199303 1 001',
    'Syahrizal Ali' => '19670725 199303 1 001',
    'Ni Made Duisthiti' => '19690207 199303 2 001',
    'Rudy Tri Yulianto Widodo' => '19710703 199303 1 001',
    'Purwaningsih Handayani' => '19740607 199402 2 001',
    'Wiji Astuti' => '19730424 199402 2 001',
    'Fahmi Atvidyan' => '19740906 199502 1 001',
    'Fahmy Atvidyan' => '19740906 199502 1 001',
    'Puji Yuwono' => '19750621 199502 1 002',
    'Sulistyo Himawan' => '19760724 199601 1 001',
    'Agung Ragil Pujono' => '19760727 199601 1 002',
    'Asri Damayanti' => '19740930 199603 2 001',
    'Much. Bouxit Wibowo' => '19710425 199603 1 001',
    'Niken Kusuma Wardhani' => '19740907 199703 2 001',
    'Rosita Ariani' => '19740907 199703 2 001',
    'Hartati' => '19701224 199703 2 001',
    'Maria Sinaga' => '19700817 199703 2 001',
    'Junaidi' => '19690609 199803 1 001',
    'Nuraini Saptanti Dewi' => '19720903 199803 2 001',
    'Siti Akrojah' => '19730916 199803 2 001',
    'Rahayu Muji Lestari' => '19740509 199803 2 007',
    'Asih Winarti' => '19750608 199803 2 001',
    'Deddy Yuliawan Suwondo' => '19750726 199803 1 001',
    'Paryono' => '19740505 199803 1 001',
    'Evi Anggraini Soeryanti' => '19750929 199803 2 001',
    'Muhammad Yasril Friandi' => '19760625 199803 1 001',
    'Bima Gautama' => '19700611 199803 1 007',
    'Afriani Nurfajriyah' => '19730405 199803 2 001',
    'Nurhadi' => '19770209 199803 1 001',
    'Novianto' => '19681117 199803 1 001',
    'Iwanto' => '19760515 199811 1 001',
    'Ana Suprihatiningsih' => '19761203 199811 2 001',
    'Akhda Himmawan' => '19761026 199811 1 001',
    'Ninik Triani' => '19761124 199811 2 001',
    'Dyah Retno Palupi' => '19770908 199811 2 001',
    'Tri Anawati' => '19770219 199811 2 001',
    'Lea Triana' => '19760623 199811 2 001',
    'Titi Sari' => '19771020 199903 2 001',
    'Rosalia Kustyaningsih' => '19720919 199903 2 011',
    'Maftukhah Nur Wijayanti' => '19801129 200312 2 001',
    'Sulistyo Tri Cahyono' => '19851104 200701 1 003',
    'Dedi Fafanto' => '19860706 200801 1 001',
    'Rizky Shampitha Surya Wibowo' => '19861118 200901 1 001',
];

$pegawai = [
    ['map', 'Hary Eka Surjanta', 'IPP'],
    ['map', 'Edi Prasetyo', 'IPP'],
    ['map', 'Eko Herman Budi Rahardjo', 'IPP'],
    ['map', 'Jun Suwarno', 'IPP'],
    ['map', 'Syahrizal Ali', 'IPP'],
    ['map', 'Susetyo Gigih Trilaksono', 'IPP'],
    ['map', 'Maria Sinaga', 'IPP'],
    ['map', 'Rahayu Muji Lestari', 'IPP'],
    ['map', 'Ninik Triani', 'IPP'],
    ['map', 'Dedi Fafanto', 'IPP'],
    ['map', 'Indria Putriasari', 'IPP'],
    ['map', 'Rini Risnawati', 'IPP'],
    ['map', 'Monika Jayatri', 'IPP'],
    ['map', 'Ananta Singgih Cahya Prasetya', 'IPP'],
    ['map', 'Cholifatul Husna', 'IPP'],
    ['map', 'Rizki Rusdhiani', 'IPP'],
    ['map', 'Mareisca Yulistina Pratama', 'IPP'],
    ['map', 'Raisha Pratidina', 'IPP'],
    ['map', 'Aditya Tri Rahmadi Putra', 'IPP'],
    ['map', 'Bagus Widodo', 'IPP'],
    ['map', 'Chintria Tira Nadia', 'IPP'],
    ['map', 'Azis Hanafi', 'IPP'],
    ['map', 'Evi Anggraini Soeryanti', 'IPP'],
    ['map', 'Maftukhah Nur Wijayanti', 'IPP'],
    ['map', 'Fandy Prakasa Wardhana', 'IPP'],
    ['map', 'Rumbati Argo', 'IPP'],
    ['map', 'Syah Mahardika', 'IPP'],
    ['map', 'Ismu Adi Pranawa', 'IPP'],
    ['map', 'Ali Ihsan', 'APD'],
    ['map', 'Suyatno', 'APD'],
    ['map', 'Rosita Ariani', 'APD'],
    ['map', 'Afriani Nurfajriyah', 'APD'],
    ['map', 'Akhda Himmawan', 'APD'],
    ['map', 'Hartati', 'APD'],
    ['map', 'Asri Damayanti', 'APD'],
    ['map', 'Wakhid Sulistio Adi', 'APD'],
    ['map', 'Arsa Nur Azhari Winarso', 'APD'],
    ['map', 'Agus Budi Laksono', 'APD'],
    ['map', 'Galih Hapsari Kirana', 'APD'],
    ['map', 'Cahyo Dwi Sabdono', 'APD'],
    ['map', 'Sani Nurbani', 'APD'],
    ['map', 'Winda Dyah Kinasih', 'APD'],
    ['map', 'Sari Wahyuni', 'APD'],
    ['map', 'Widyawan Nugroho', 'APD'],
    ['map', 'Muhammad Yasril Friandi', 'APD'],
    ['map', 'Mega Yoga Prastika', 'APD'],
    ['map', 'Lenni Agustina', 'APD'],
    ['map', 'Rizka Choirunnisa', 'APD'],
    ['map', 'Noor Hanifah', 'APD'],
    ['map', 'Ratni Dewi', 'APD'],
    ['map', 'Siti Roh Chayatun', 'APD'],
    ['map', 'Naura Nadhifa', 'APD'],
    ['map', 'Fajar Cahyaning Sadarum', 'APD'],
    ['map', 'Puji Yuwono', 'AN'],
    ['map', 'Siti Solekah', 'AN'],
    ['map', 'Purnomo Aji', 'AN'],
    ['map', 'Dyah Retno Palupi', 'AN'],
    ['map', 'Komaruz Zaman', 'AN'],
    ['map', 'Rizky Shampitha Surya Wibowo', 'AN'],
    ['map', 'Tri Ana Fauziah', 'AN'],
    ['map', 'Nur Fita Sari', 'AN'],
    ['map', 'Siti Muslikhah Kusuma Nurakhmadyati', 'AN'],
    ['map', 'Abu Achmad', 'AN'],
    ['map', 'Irene Linda Widiastuti', 'AN'],
    ['map', 'Dyah Qonitasari Estyamilla', 'AN'],
    ['map', 'Riski Lukfiarini', 'AN'],
    ['map', 'Devita Febriani', 'AN'],
    ['map', 'Kurnia Yuspita', 'AN'],
    ['map', 'Lea Triana', 'AN'],
    ['map', 'Sulistyo Tri Cahyono', 'AN'],
    ['map', 'Enggar Nastanto', 'AN'],
    ['map', 'Agung Ragil Pujono', 'Investigasi'],
    ['map', 'Bambang Yuliyanto', 'Investigasi'],
    ['map', 'Caecilia Hermawati', 'Investigasi'],
    ['map', 'Sulistyo Himawan', 'Investigasi'],
    ['map', 'Wiji Astuti', 'Investigasi'],
    ['map', 'Hani Zulniati', 'Investigasi'],
    ['map', 'Paramithasari R', 'Investigasi'],
    ['map', 'Giri Firmansyah', 'Investigasi'],
    ['map', 'Desi Susanti', 'Investigasi'],
    ['map', 'Rikha Aditya Wardhani', 'Investigasi'],
    ['map', 'Doni Kurniawan Subardo', 'Investigasi'],
    ['map', 'Azizah Endrastaty', 'Investigasi'],
    ['map', 'Juli Sarwanto', 'Investigasi'],
    ['map', 'Fadlian Lazuardi Mulyono', 'Investigasi'],
    ['map', 'Dewi Kurniasari', 'Investigasi'],
    ['map', 'Amaliyyah Raadhiyyata Mardhiyyah', 'Investigasi'],
    ['map', 'Erik Darmawan', 'Investigasi'],
    ['map', 'Iwanto', 'Investigasi'],
    ['map', 'Asri Primandari', 'Investigasi'],
    ['map', 'Puspita Dewi Putri', 'Investigasi'],
    ['map', 'Fahmy Atvidyan', 'P3A'],
    ['map', 'Achmad Fachri', 'P3A'],
    ['map', 'Franciscus Xaverius Sarwoko', 'P3A'],
    ['map', 'Ana Suprihatiningsih', 'P3A'],
    ['map', 'Tri Anawati', 'P3A'],
    ['map', 'Ngatini', 'P3A'],
    ['map', 'Anggra Dewi Sekarningrum', 'P3A'],
    ['map', 'Asri Suwarsih', 'P3A'],
    ['map', 'Mandala Ulul Amri', 'P3A'],
    ['map', 'Zulita Dyah Shintaningrum', 'P3A'],
    ['map', 'Dewi Asih Kurnia', 'P3A'],
    ['map', 'Asri Oktaviani Puitri', 'P3A'],
    ['map', 'Puji Purnaweni', 'P3A'],
    ['map', 'Anita Setyaningtyas', 'P3A'],
    ['map', 'Nur Hanifah Hayyuningtyas', 'P3A'],
    ['map', 'Yunita Evi Kurniasari', 'P3A'],
    ['map', 'Riana Widiastuti', 'P3A'],
    ['map', 'Yenni K Nainggolan', 'P3A'],
    ['map', 'Niken Kusuma Wardhani', 'P3A'],
    ['map', 'Akhmad Pandu Kurnia', 'P3A'],
    ['map', 'Dessy Adin', 'TU'],
    ['map', 'Siti Akrojah', 'TU', 'admin'],
    ['map', 'Usman Maulana', 'TU'],
    ['map', 'Lutfhi Jauhari', 'TU'],
    ['map', 'Mulyadi', 'TU', 'admin'],
    ['map', 'Purwaningsih Handayani', 'TU'],
    ['map', 'Arum Ditha Safitri', 'TU'],
    ['map', 'Ni Made Duisthiti', 'TU'],
    ['map', 'Upik Krismareta Nuratifa', 'TU'],
    ['map', 'Asih Winarti', 'TU'],
    ['map', 'Nolita Ayu Puspitasari', 'TU'],
    ['map', 'Noor Latifah Dachlan', 'TU'],
    ['map', 'Rosalia Kustyaningsih', 'TU'],
    ['map', 'Cukamnoto Hariyadi', 'TU'],
    ['map', 'Danie Yanuar', 'TU'],
    ['map', 'Dini Susanti', 'TU'],
    ['map', 'Vuji Suprihatin', 'TU'],
    ['map', 'Hanifiar Bima Retnanti', 'TU'],
    ['map', 'Lestariningsih', 'TU'],
    ['map', 'Halim Prawiranata', 'TU'],
    ['map', 'Rudy Tri Yulianto Widodo', 'TU'],
    ['map', 'Aninda Purba Cahyani', 'TU'],
    ['map', 'RR Kun Alfiah Nur Aerodynamicawati', 'TU'],
    ['map', 'Arin Ambar Setiarani', 'TU'],
    ['map', 'Nuraini Saptanti Dewi', 'TU'],
    ['map', 'Titi Sari', 'TU'],
    ['map', 'Oki Paramita', 'TU'],
    ['map', 'Deddy Yuliawan Suwondo', 'TU'],
    ['map', 'Puji Estriningsih', 'TU'],
    ['map', 'Wahyu Nurwanto', 'TU'],
    ['map', 'Faisal Ansari', 'TU'],
    ['map', 'Yunika Permata Sari', 'TU'],
    ['map', 'Paryono', 'TU'],
    ['map', 'Much. Bouxit Wibowo', 'TU'],
    ['map', 'Dicky Ervyanto', 'TU'],
    ['map', 'Unik Novia Dara', 'TU'],
    ['map', 'Rosa Rizki Agustina', 'TU'],
    ['map', 'Junaidi', 'TU'],
    ['map', 'Sudaryanta', 'TU'],
    ['map', 'Parman', 'TU'],
    ['map', 'Paryanto', 'TU'],
    ['map', 'Agus Suranto', 'TU'],
    ['map', 'Amin Idrus', 'TU'],
    ['map', 'Anang Hartaya', 'TU'],
    ['map', 'Risdiyanto', 'TU'],
    ['map', 'Heri Susanto', 'TU'],
    ['map', 'Rochmad Susanto', 'TU'],
    ['map', 'Agus Cahyadi', 'TU'],
    ['map', 'Marwadi', 'TU'],
    ['map', 'Maryanto', 'TU'],
    ['map', 'Endi Jayus', 'TU'],
    ['map', 'Sarwo Edi', 'TU'],
    ['map', 'Pargiyono', 'TU'],
    ['map', 'Riska Heru Wibowo', 'TU'],
    ['map', 'Yundaris Filiyanto', 'TU'],
    ['map', 'Fajar Pramono', 'TU'],
    ['map', 'Wisnu Saputra', 'TU'],
    ['map', 'Hudalil Mustofa', 'TU'],
    ['map', 'Nurhadi', 'TU'],
    ['map', 'Novianto', 'TU'],
    ['map', 'Bima Gautama', 'TU'],
    ['map', 'Dinar Safir Fatikha', 'TU'],
    ['map', 'Ngatini', 'TU'],
    ['map', 'Kriswanto', 'TU'],
    ['map', 'Andrianto', 'TU'],
    ['map', 'Margiyanto', 'TU'],
];

$resolvedPegawai = [];
$nipAdminList = [
    'Mulyadi' => '19861118 200901 1 001',
    'Siti Akrojah' => '19730916 199803 2 001',
];
foreach ($pegawai as $idx => $p) {
    list($tag, $nama, $bidang) = $p;
    $role = $p[3] ?? 'pegawai';
    $noUrut = $idx + 1;
    if (isset($nipAdminList[$nama])) {
        $nip = $nipAdminList[$nama];
    } else {
        $nip = "TEMP-PEGAWAI-" . str_pad($noUrut, 3, '0', STR_PAD_LEFT);
    }
    $resolvedPegawai[] = [$nip, $nama, $bidang, $role];
}

$success = 0;
$skipped = 0;
$updated = 0;
$errors = [];
$admin_count = 0;

try {
    echo "=== RESET DATA USERS & DRIVER (TRUNCATE agar 100% sesuai 177 list) ===\n";
    try {
        db()->exec("TRUNCATE TABLE users");
        echo "  ✅ Table users di-TRUNCATE (data lama dihapus semua, AUTO_INCREMENT reset)\n";
    } catch (Exception $e) {
        echo "  ⚠️ TRUNCATE users gagal, fallback DELETE: " . $e->getMessage() . "\n";
        db()->exec("DELETE FROM users");
        echo "  ✅ Fallback DELETE FROM users berhasil\n";
    }
    try {
        db()->exec("TRUNCATE TABLE driver");
        echo "  ✅ Table driver di-TRUNCATE (data driver lama dihapus)\n\n";
    } catch (Exception $e) {
        echo "  ⚠️ TRUNCATE driver gagal, fallback DELETE: " . $e->getMessage() . "\n";
        db()->exec("DELETE FROM driver");
        echo "  ✅ Fallback DELETE FROM driver berhasil\n\n";
    }

    foreach ($resolvedPegawai as $p) {
        list($nip, $nama, $bidang, $role) = $p;
        try {
            $exists = db()->fetchOne("SELECT id, nama_lengkap, unit_kerja, role FROM users WHERE nip = ?", [$nip]);
            if ($exists) {
                if ($exists['nama_lengkap'] !== $nama || $exists['unit_kerja'] !== $bidang || $exists['role'] !== $role) {
                    db()->exec("UPDATE users SET nama_lengkap = ?, unit_kerja = ?, role = ? WHERE nip = ?", [$nama, $bidang, $role, $nip]);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                db()->exec("INSERT INTO users (nip, nama_lengkap, password, unit_kerja, no_hp, role, created_at) VALUES (?, ?, ?, ?, NULL, ?, NOW())",
                    [$nip, $nama, $default_password, $bidang, $role]);
                $success++;
            }
            if ($role === 'admin') $admin_count++;
        } catch (Exception $e) {
            $errors[] = "NIP $nip $nama: " . $e->getMessage();
        }
    }

    echo "=== SEED 5 DRIVER EXCEL PERSONIL ===\n";
    try {
        $idx_d = @db()->fetchAll("SHOW INDEX FROM driver WHERE Key_name LIKE 'uniq_driver_%'");
        if (empty($idx_d)) { @db()->exec("ALTER IGNORE TABLE driver ADD UNIQUE KEY uniq_driver_nama (nama_driver)"); }
    } catch (Exception $e) {}
    $driverList = [
        ['nama_driver' => 'Idrus', 'no_wa' => '08157948036', 'status' => 'tersedia'],
        ['nama_driver' => 'Heri Susanto', 'no_wa' => '08564345558', 'status' => 'tersedia'],
        ['nama_driver' => 'Novianto', 'no_wa' => '085327387772', 'status' => 'tersedia'],
        ['nama_driver' => 'Deddy Yuliawan Suwondo', 'no_wa' => '081225550551', 'status' => 'tersedia'],
        ['nama_driver' => 'Maryanto', 'no_wa' => '085878778827', 'status' => 'tersedia'],
    ];
    $dIns = 0; $dUpd = 0;
    foreach ($driverList as $d) {
        $ex = db()->fetchOne("SELECT id FROM driver WHERE nama_driver=?", [$d['nama_driver']]);
        if ($ex) {
            db()->exec("UPDATE driver SET no_wa=?, status=? WHERE id=?", [$d['no_wa'], $d['status'], $ex['id']]);
            $dUpd++;
        } else {
            db()->exec("INSERT INTO driver (nama_driver, no_wa, status, created_at) VALUES (?, ?, ?, NOW())", [$d['nama_driver'], $d['no_wa'], $d['status']]);
            $dIns++;
        }
    }
    echo "  Driver Insert : $dIns orang\n";
    echo "  Driver Update : $dUpd orang\n\n";

    echo "=== HASIL EKSEKUSI PEGAWAI ===\n";
    echo "  Insert Baru : $success orang\n";
    echo "  Update Data : $updated orang\n";
    echo "  Skip (sama) : $skipped orang\n";
    echo "  Jadi Admin  : $admin_count orang\n";
    echo "  Error       : " . count($errors) . " orang\n\n";

    if (!empty($errors)) {
        echo "=== LIST ERROR ===\n";
        foreach ($errors as $e) echo "  ! $e\n";
        echo "\n";
    }

    echo "--- VERIFIKASI COUNT TOTAL ---\n";
    $cu = (int)db()->fetchOne("SELECT COUNT(*) c FROM users")['c'];
    $ca = (int)db()->fetchOne("SELECT COUNT(*) c FROM users WHERE role='admin'")['c'];
    $cp = (int)db()->fetchOne("SELECT COUNT(*) c FROM users WHERE role='pegawai'")['c'];
    echo "  Total Users  : $cu\n";
    echo "  Total Admin  : $ca\n";
    echo "  Total Pegawai: $cp\n\n";

    echo "--- VERIFIKASI COUNT PER BIDANG --- (TARGET: IPP=28, APD=25, AN=18, Investigasi=20, P3A=20, TU=66)\n";
    $bidangList = ['IPP','APD','AN','Investigasi','Keuangan','P3A','PBRA','TU'];
    foreach ($bidangList as $b) {
        $c = (int)db()->fetchOne("SELECT COUNT(*) c FROM users WHERE unit_kerja=?", [$b])['c'];
        echo "  $b : $c orang\n";
    }
    $cNull = (int)db()->fetchOne("SELECT COUNT(*) c FROM users WHERE unit_kerja IS NULL OR unit_kerja=''")['c'];
    echo "  (NULL/empty unit_kerja) : $cNull orang\n\n";

    echo "=== CEK 2 ADMIN SPESIFIK ===\n";
    foreach ([
        '19861118 200901 1 001' => 'Mulyadi (TU - Admin Utama)',
        '19730916 199803 2 001' => 'Siti Akrojah (TU - Admin 2)'
    ] as $nip => $label) {
        $u = db()->fetchOne("SELECT id, nip, nama_lengkap, unit_kerja, role FROM users WHERE nip=?", [$nip]);
        if ($u) {
            echo "  ✅ $label\n";
            echo "       NIP: {$u['nip']} | Nama: {$u['nama_lengkap']} | Bidang: {$u['unit_kerja']} | Role: {$u['role']}\n";
        } else {
            echo "  ❌ $label → TIDAK DITEMUKAN dengan NIP $nip!\n";
        }
    }

    $pass = ($ca >= 2 && $cu >= 177);
    echo "\n=== " . ($pass?"🎉 SELESAI. DATA PEGAWAI 177 BERHASIL DIMASUKKAN":"⚠️ MASIH ADA KURANG - cek count di atas") . " ===\n";
    echo "\nDefault password SEMUA user: 'password'\n";
    echo "User login pakai kolom NIP sebagai username.\n";
    echo "Untuk user TEMP-PEGAWAI-xxx: NIP placeholder, silakan edit manual di Master Pengguna jika ada NIP asli.\n";

} catch (Exception $e) {
    echo "\n[FATAL] " . $e->getMessage() . "\n" . $e->getTraceAsString();
    exit(1);
}
