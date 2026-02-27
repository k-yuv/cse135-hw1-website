<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// ----------------------------
// Database connection
// ----------------------------
$dsn = "pgsql:host=127.0.0.1;port=5432;dbname=postgres;";
$user = "postgres";
$password = "Sanrio135Cse";

try {
    $db = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Database connection failed",
        "details" => $e->getMessage()
    ]);
    exit();
}

// ----------------------------
// Parse the URL
// ----------------------------
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$resource = $request[0] ?? null;
$id = $request[1] ?? null;

$method = $_SERVER['REQUEST_METHOD'];

// ----------------------------
// Allowed tables
// ----------------------------
$allowedTables = [
    "errors",
    "events",
    "pageviews",
    "performance",
    "sessions",
    "users"
];

if (!$resource || !in_array($resource, $allowedTables)) {
    http_response_code(404);
    echo json_encode(["error" => "Resource not found"]);
    exit();
}

// ----------------------------
// Handle CRUD operations dynamically
// ----------------------------
switch ($method) {

    // =========================
    // GET (all or by ID)
    // =========================
    case "GET":
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM $resource WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                http_response_code(404);
                echo json_encode(["error" => "Not found"]);
            } else {
                echo json_encode($result);
            }
        } else {
            $stmt = $db->query("SELECT * FROM $resource ORDER BY id DESC");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($results);
        }
        break;

    // =========================
    // POST (create new entry)
    // =========================
    case "POST":
        if ($id) {
            http_response_code(400);
            echo json_encode(["error" => "Do not include ID in POST"]);
            break;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data || !is_array($data)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid JSON"]);
            break;
        }

        $columns = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $columnList = implode(',', $columns);

        $stmt = $db->prepare(
            "INSERT INTO $resource ($columnList)
             VALUES ($placeholders)
             RETURNING *"
        );
        $stmt->execute(array_values($data));

        http_response_code(201);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        break;

    // =========================
    // PUT (update by ID)
    // =========================
    case "PUT":
        if (!$id) {
            http_response_code(400);
            echo json_encode(["error" => "ID required for PUT"]);
            break;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data || !is_array($data)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid JSON"]);
            break;
        }

        $setParts = [];
        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
        }
        $setClause = implode(',', $setParts);

        $stmt = $db->prepare(
            "UPDATE $resource
             SET $setClause
             WHERE id = ?
             RETURNING *"
        );

        $values = array_values($data);
        $values[] = $id;

        $stmt->execute($values);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            http_response_code(404);
            echo json_encode(["error" => "Not found"]);
        } else {
            echo json_encode($result);
        }
        break;

    // =========================
    // DELETE (by ID)
    // =========================
    case "DELETE":
        if (!$id) {
            http_response_code(400);
            echo json_encode(["error" => "ID required for DELETE"]);
            break;
        }

        $stmt = $db->prepare("DELETE FROM $resource WHERE id = ? RETURNING *");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["error" => "Not found"]);
        } else {
            http_response_code(204);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
}