-- Data Verification Dashboard (Demo) - seed data
-- EVERYTHING HERE IS FICTITIOUS. Demo password for all accounts: Password123!

SET NAMES utf8mb4;

DELETE FROM audit_log;
DELETE FROM import_batches;
DELETE FROM records;
DELETE FROM users;
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE records AUTO_INCREMENT = 1;

INSERT INTO users (id, name, username, password_hash, role) VALUES
(1, 'Admin Contoh',       'admin',       '$2y$12$znNzE/EcOz1RALBfmevD7.Yv4eTbB8ynFNyZ6Px33HdBdd.oASetW', 'admin'),
(2, 'Verifikator Contoh', 'verifikator', '$2y$12$hjoXt1X9hR6bckHDRWYgWu0s8r5CHO3IHRCUuYxpmJACWsQR75RaS', 'verifikator'),
(3, 'Staff Contoh',       'staff',       '$2y$12$PBBzUfl4xIdK.x2wcBNIBe/QffXq0nVZmzS0JNS1gjLzlUWL3i/ru', 'staff');

-- A few fictitious records already in the system (fake NIK pattern 9999999999999xxx)
INSERT INTO records (nik, full_name, address, category, status, source, created_by, verified_by, verified_at) VALUES
('9999999999999001', 'Warga Contoh 001', 'Jl. Contoh No. 1, Kota Contoh', 'Category A', 'verified', 'seed', 3, 2, NOW()),
('9999999999999002', 'Warga Contoh 002', 'Jl. Contoh No. 2, Kota Contoh', 'Category B', 'pending',  'seed', 3, NULL, NULL),
('9999999999999003', 'Warga Contoh 003', 'Jl. Contoh No. 3, Kota Contoh', 'Category A', 'rejected', 'seed', 3, 2, NOW());

INSERT INTO audit_log (user_id, action, entity, entity_id, detail) VALUES
(3, 'create', 'record', 1, 'Seed record created'),
(2, 'verify', 'record', 1, 'Verified during seed'),
(2, 'reject', 'record', 3, 'Rejected during seed: incomplete data');
