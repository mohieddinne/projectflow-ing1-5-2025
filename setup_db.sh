#!/bin/bash

# Configuration de la base de données
DB_NAME="projectflow"
DB_USER="projectflow"
DB_PASS="123@mohA"

# Se connecter en tant que root et configurer la base de données
sudo mysql << EOF
DROP DATABASE IF EXISTS ${DB_NAME};
CREATE DATABASE ${DB_NAME};
DROP USER IF EXISTS '${DB_USER}'@'localhost';
CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "✅ Base de données et utilisateur créés"

# Créer les tables
mysql -u ${DB_USER} -p${DB_PASS} ${DB_NAME} < database/schema.sql
echo "✅ Tables créées"

# Insérer les données de test
mysql -u ${DB_USER} -p${DB_PASS} ${DB_NAME} < database/seed.sql
echo "✅ Données de test insérées"

echo "✅ Configuration de la base de données terminée"
