<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

session_start();

$mongoUri = 'mongodb://10.10.13.2:27017';
$database = 'AssaggiaMente';

try {
    $manager = new MongoDB\Driver\Manager($mongoUri);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Connessione DB fallita: ' . $e->getMessage()]);
    exit();
}

// Helper per ottenere il prossimo ID automatico
function getNextId($manager, $database, $collection) {
    $query = new MongoDB\Driver\Query([], ['sort' => ['_id' => -1], 'limit' => 1]);
    $cursor = $manager->executeQuery("$database.$collection", $query);
    $last = current($cursor->toArray());
    return $last ? $last->_id + 1 : 1;
}

// Gestione preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    switch ($method) {
        case 'GET':
            // === GET Ristoranti ===
            if ($endpoint === 'restaurants') {
                if ($id) {
                    // Dettaglio singolo ristorante
                    $query = new MongoDB\Driver\Query(['_id' => $id]);
                    $cursor = $manager->executeQuery("$database.restaurants", $query);
                    $restaurant = current($cursor->toArray());
                    
                    if (!$restaurant) {
                        http_response_code(404);
                        echo json_encode(['error' => 'Ristorante non trovato']);
                        break;
                    }
                    
                    // Recupera recensioni
                    $reviewQuery = new MongoDB\Driver\Query(['restaurant_id' => $id]);
                    $reviewCursor = $manager->executeQuery("$database.reviews", $reviewQuery);
                    $reviews = [];
                    foreach ($reviewCursor as $review) {
                        $reviews[] = json_decode(json_encode($review), true);
                    }
                    
                    $result = json_decode(json_encode($restaurant), true);
                    $result['reviews'] = $reviews;
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                } else {
                    // Lista tutti i ristoranti
                    $query = new MongoDB\Driver\Query([]);
                    $cursor = $manager->executeQuery("$database.restaurants", $query);
                    $restaurants = [];
                    foreach ($cursor as $doc) {
                        $restaurants[] = json_decode(json_encode($doc), true);
                    }
                    echo json_encode($restaurants, JSON_UNESCAPED_UNICODE);
                }
            }
            // === GET I miei ristoranti (admin) ===
            elseif ($endpoint === 'my-restaurants' && isset($_SESSION['user_id'])) {
                $query = new MongoDB\Driver\Query(['created_by' => $_SESSION['user_id']]);
                $cursor = $manager->executeQuery("$database.restaurants", $query);
                $restaurants = [];
                foreach ($cursor as $doc) {
                    $restaurants[] = json_decode(json_encode($doc), true);
                }
                echo json_encode($restaurants, JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // === POST Registrazione ===
            if ($endpoint === 'register') {
                // Verifica email esistente
                $query = new MongoDB\Driver\Query(['email' => $data['email']]);
                $cursor = $manager->executeQuery("$database.users", $query);
                if (current($cursor->toArray())) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Email già registrata']);
                    break;
                }
                
                $newId = getNextId($manager, $database, 'users');
                $user = [
                    '_id' => $newId,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                    'role' => 'user',
                    'created_at' => new MongoDB\BSON\UTCDateTime()
                ];
                
                $bulk = new MongoDB\Driver\BulkWrite();
                $bulk->insert($user);
                $manager->executeBulkWrite("$database.users", $bulk);
                
                echo json_encode(['success' => true, 'user_id' => $newId]);
            }
            // === POST Login ===
            elseif ($endpoint === 'login') {
                $query = new MongoDB\Driver\Query(['email' => $data['email']]);
                $cursor = $manager->executeQuery("$database.users", $query);
                $user = current($cursor->toArray());
                
                if ($user && password_verify($data['password'], $user->password)) {
                    $_SESSION['user_id'] = $user->_id;
                    $_SESSION['user_name'] = $user->name;
                    $_SESSION['role'] = $user->role;
                    echo json_encode(['success' => true, 'user' => $user->name, 'role' => $user->role]);
                } else {
                    http_response_code(401);
                    echo json_encode(['error' => 'Credenziali non valide']);
                }
            }
            // === POST Aggiungi ristorante (SOLO ADMIN) ===
            elseif ($endpoint === 'restaurants') {
                if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Solo gli admin possono aggiungere ristoranti']);
                    break;
                }
                
                $newId = getNextId($manager, $database, 'restaurants');
                $restaurant = [
                    '_id' => $newId,
                    'name' => $data['name'],
                    'address' => $data['address'],
                    'price_range' => (int)$data['price_range'],
                    'image_url' => $data['image_url'] ?? '',
                    'description' => $data['description'],
                    'created_by' => $_SESSION['user_id'],
                    'created_at' => new MongoDB\BSON\UTCDateTime()
                ];
                
                $bulk = new MongoDB\Driver\BulkWrite();
                $bulk->insert($restaurant);
                $manager->executeBulkWrite("$database.restaurants", $bulk);
                
                echo json_encode(['success' => true, 'id' => $newId]);
            }
            // === POST Aggiungi recensione ===
            elseif ($endpoint === 'reviews') {
                if (!isset($_SESSION['user_id'])) {
                    http_response_code(401);
                    echo json_encode(['error' => 'Devi essere loggato per recensire']);
                    break;
                }
                
                $newId = getNextId($manager, $database, 'reviews');
                $review = [
                    '_id' => $newId,
                    'restaurant_id' => (int)$data['restaurant_id'],
                    'user_id' => $_SESSION['user_id'],
                    'user_name' => $_SESSION['user_name'],
                    'text' => $data['text'],
                    'created_at' => new MongoDB\BSON\UTCDateTime()
                ];
                
                $bulk = new MongoDB\Driver\BulkWrite();
                $bulk->insert($review);
                $manager->executeBulkWrite("$database.reviews", $bulk);
                
                echo json_encode(['success' => true, 'id' => $newId]);
            }
            break;
            
        case 'PUT':
            // === PUT Modifica ristorante (SOLO ADMIN) ===
            if ($endpoint === 'restaurants' && $id) {
                if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Permessi insufficienti']);
                    break;
                }
                
                $data = json_decode(file_get_contents('php://input'), true);
                $bulk = new MongoDB\Driver\BulkWrite();
                $bulk->update(['_id' => $id], ['$set' => $data]);
                $manager->executeBulkWrite("$database.restaurants", $bulk);
                
                echo json_encode(['success' => true]);
            }
            break;
            
        case 'DELETE':
            // === DELETE Elimina ristorante (SOLO ADMIN) ===
            if ($endpoint === 'restaurants' && $id) {
                if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Permessi insufficienti']);
                    break;
                }
                
                $bulk = new MongoDB\Driver\BulkWrite();
                $bulk->delete(['_id' => $id]);
                $manager->executeBulkWrite("$database.restaurants", $bulk);
                
                // Elimina anche le recensioni associate
                $bulkReviews = new MongoDB\Driver\BulkWrite();
                $bulkReviews->delete(['restaurant_id' => $id]);
                $manager->executeBulkWrite("$database.reviews", $bulkReviews);
                
                echo json_encode(['success' => true]);
            }
            // === DELETE Elimina recensione ===
            elseif ($endpoint === 'reviews' && $id) {
                if (!isset($_SESSION['user_id'])) {
                    http_response_code(401);
                    echo json_encode(['error' => 'Non autenticato']);
                    break;
                }
                
                // Solo l'autore o admin possono eliminare
                $query = new MongoDB\Driver\Query(['_id' => $id]);
                $cursor = $manager->executeQuery("$database.reviews", $query);
                $review = current($cursor->toArray());
                
                if (!$review) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Recensione non trovata']);
                    break;
                }
                
                if ($review->user_id != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Non autorizzato']);
                    break;
                }
                
                $bulk = new MongoDB\Driver\BulkWrite();
                $bulk->delete(['_id' => $id]);
                $manager->executeBulkWrite("$database.reviews", $bulk);
                
                echo json_encode(['success' => true]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Metodo non supportato']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore server: ' . $e->getMessage()]);
}
?>