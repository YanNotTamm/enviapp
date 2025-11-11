USE envirome_devdb;

INSERT INTO users (
  username, 
  email, 
  password, 
  nama_lengkap, 
  nama_perusahaan, 
  alamat_perusahaan, 
  telepon, 
  role, 
  email_verified,
  envipoin,
  masa_berlaku,
  layanan_aktif,
  created_at,
  updated_at
) VALUES (
  'admin',
  'admin@envirometrolestari.com',
  '$2y$10$dNE.FTDn5xHpjCFX6MTpCuVJAm/2iWEIPQP1FiY3xf4BsPa4DN1BO',
  'Administrator',
  'PT Envirometro Lestari',
  'Jakarta',
  '08123456789',
  'superadmin',
  1,
  1000,
  DATE_ADD(NOW(), INTERVAL 1 YEAR),
  1,
  NOW(),
  NOW()
);
 