<?php
/**
 * Fonction pour appeler l'API
 * 
 * @param string $endpoint Point d'accès de l'API (ex: 'projects', 'users')
 * @param string $method Méthode HTTP (GET, POST, PUT, DELETE)
 * @param array $data Données à envoyer (pour POST et PUT)
 * @param array $params Paramètres de requête (pour GET)
 * @return array Réponse de l'API
 * @throws Exception En cas d'erreur
 */
function callAPI($endpoint, $method = 'GET', $data = null, $params = []) {
    // Construire l'URL de base
    $baseUrl = "http://localhost/backend/api/";
    $url = $baseUrl . $endpoint . ".php";
    
    // Ajouter les paramètres de requête
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    // Initialiser cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Configurer la méthode HTTP
    switch ($method) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, 1);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
    }
    
    // Configurer les en-têtes
    $headers = ['Content-Type: application/json'];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Exécuter la requête
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Vérifier les erreurs cURL
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Erreur cURL: $error");
    }
    
    curl_close($ch);
    
    // Décoder la réponse JSON
    $result = json_decode($response, true);
    
    // Vérifier le code HTTP
    if ($httpCode < 200 || $httpCode >= 300) {
        $message = isset($result['message']) ? $result['message'] : "Erreur HTTP: $httpCode";
        throw new Exception($message);
    }
    
    return $result;
}
