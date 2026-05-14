<?php
header('Content-Type: application/json; charset=UTF-8');

$mongoUri = 'mongodb://10.10.13.2:27017';
$database = 'AssaggiaMente';

try {
    $manager = new MongoDB\Driver\Manager($mongoUri);
    
    // Test connessione
    $command = new MongoDB\Driver\Command(['ping' => 1]);
    $manager->executeCommand('admin', $command);
    
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Errore connessione MongoDB: ' . $e->getMessage()]));
}

// Le collection verranno usate direttamente nelle API
// Non serve definirle qui perché le useremo con $manager->executeQuery()
?>