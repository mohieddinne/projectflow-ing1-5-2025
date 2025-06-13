#!/bin/bash

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration
BASE_PORT=8000
MAX_PORT=8020

# Fonction pour vérifier si un port est disponible
is_port_available() {
    ! netstat -tna | grep -q ":$1.*LISTEN"
}

# Trouver un port disponible
PORT=$BASE_PORT
while ! is_port_available $PORT && [ $PORT -le $MAX_PORT ]; do
    ((PORT++))
done

if [ $PORT -gt $MAX_PORT ]; then
    echo -e "${RED}Aucun port disponible entre $BASE_PORT et $MAX_PORT${NC}"
    exit 1
fi

# Tuer tout processus existant sur le port choisi
fuser -k $PORT/tcp 2>/dev/null

# Configuration de la base de données
echo "Configuration de la base de données..."
./setup_db.sh

if [ $? -ne 0 ]; then
    echo -e "${RED}Erreur lors de la configuration de la base de données${NC}"
    exit 1
fi

echo -e "${GREEN}Démarrage du serveur sur http://localhost:$PORT${NC}"
php -S localhost:$PORT index.php
