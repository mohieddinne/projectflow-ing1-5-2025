-- database/seed.sql

-- Utilisateur de test (mot de passe: Pa$$w0rd!)
INSERT INTO users (name, email, password, role) 
VALUES ('Test User', 'monezefigy@mailinator.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Utilisateur admin (mot de passe: Admin123!)
INSERT INTO users (name, email, password, role) 
VALUES ('Admin', 'admin@projectflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
