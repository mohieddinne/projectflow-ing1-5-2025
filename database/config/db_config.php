<?php
/**
 * Configuration de la base de données
 * Ce fichier est chargé par les scripts nécessitant un accès à la base de données
 */

// Charger la configuration principale si elle existe
$mainConfig = file_exists(__DIR__ . '/../../config.php') 
    ? require __DIR__ . '/../../config.php' 
    : null;

// Configuration de la base de données
return [
    // Utiliser les valeurs du fichier principal ou les valeurs par défaut
    'driver'    => $mainConfig['db']['driver'] ?? 'mysql',
    'host'      => $mainConfig['db']['host'] ?? 'localhost',
    'port'      => $mainConfig['db']['port'] ?? 3306,
    'database'  => $mainConfig['db']['name'] ?? 'projectflow',
    'name'  => $mainConfig['db']['user'] ?? 'projectflow',
    'password'  => $mainConfig['db']['pass'] ?? '',
    
    // Options PDO
    'options'   => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ],
    
    // Configuration du pool de connexions
    'pool' => [
        'min_connections' => 1,
        'max_connections' => 10,
        'idle_timeout'    => 60, // secondes
    ],
    
    // Configuration de la réplication (si nécessaire)
    'replication' => [
        'read' => [
            'host' => [
                'localhost',
                // Autres serveurs de lecture
            ],
        ],
        'write' => [
            'host' => [
                'localhost',
                // Autres serveurs d'écriture
            ],
        ],
    ],
    
    // Configuration du cache
    'cache' => [
        'enabled'    => true,
        'driver'     => 'redis',
        'host'       => 'localhost',
        'port'       => 6379,
        'ttl'        => 3600, // secondes
    ],
    
    // Configuration des migrations
    'migrations' => [
        'table'     => 'migrations',
        'path'      => __DIR__ . '/../migrations',
    ],
    
    // Configuration des sauvegardes
    'backup' => [
        'path'      => __DIR__ . '/../backups',
        'filename'  => 'backup_' . date('Y-m-d_H-i-s') . '.sql',
        'compress'  => true,
    ],
];

/**
 * Fonction utilitaire pour obtenir une connexion PDO
 * @return PDO
 */
function getDatabaseConnection(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        $config = require __FILE__;
        
        try {
            $dsn = sprintf(
                "%s:host=%s;port=%d;dbname=%s;charset=utf8mb4",
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database']
            );
            
            $pdo = new PDO(
                $dsn,
                $config['name'],
                $config['password'],
                $config['options']
            );
            
        } catch (PDOException $e) {
            // Log l'erreur
            error_log("Erreur de connexion à la base de données : " . $e->getMessage());
            
            // Retourner une erreur utilisateur sécurisée
            throw new Exception("Impossible de se connecter à la base de données. Contactez l'administrateur.");
        }
    }
    
    return $pdo;
}

/**
 * Fonction pour tester la connexion
 * @return bool
 */
function testDatabaseConnection(): bool {
    try {
        $pdo = getDatabaseConnection();
        $pdo->query('SELECT 1');
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Fonction pour obtenir la version de la base de données
 * @return string
 */
function getDatabaseVersion(): string {
    try {
        $pdo = getDatabaseConnection();
        return $pdo->query('SELECT VERSION()')->fetchColumn();
    } catch (Exception $e) {
        return 'Unknown';
    }
}
