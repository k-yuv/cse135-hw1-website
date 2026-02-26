<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$dsn = "pgsql:host=localhost;port=5432;dbname=postgres;";
$user = "postgres";
$password = "Sanrio135Cse";

try {
    $db = new PDO($dsn, $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Database connection failed",
        "details" => $e->getMessage()
    ]);
    exit();
}

// Parse URL
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$resource = $request[0] ?? null;
$id = $request[1] ?? null;

$method = $_SERVER['REQUEST_METHOD'];

// Only supporting pageviews here (you can add others later)
if ($resource !== "pageviews") {
    http_response_code(404);
    echo json_encode(["error" => "Resource not found"]);
    exit();
}

switch ($method) {

    // =========================
    // GET
    // =========================
    case "GET":
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM pageviews WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                http_response_code(404);
                echo json_encode(["error" => "Not found"]);
            } else {
                echo json_encode($result);
            }
        } else {
            $stmt = $db->query("SELECT * FROM pageviews ORDER BY id DESC");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($results);
        }
        break;

    // =========================
    // POST (NO ID)
    // =========================
    case "POST":
        if ($id) {
            http_response_code(400);
            echo json_encode(["error" => "Do not include ID in POST"]);
            break;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['url']) || !isset($data['session_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing fields"]);
            break;
        }

        $stmt = $db->prepare(
            "INSERT INTO pageviews (url, session_id)
             VALUES (?, ?)
             RETURNING *"
        );
        $stmt->execute([$data['url'], $data['session_id']]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(201);
        echo json_encode($result);
        break;

    // =========================
    // PUT (ID REQUIRED)
    // =========================
    case "PUT":
        if (!$id) {
            http_response_code(400);
            echo json_encode(["error" => "ID required for PUT"]);
            break;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $db->prepare(
            "UPDATE pageviews
             SET url = ?
             WHERE id = ?
             RETURNING *"
        );
        $stmt->execute([$data['url'], $id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            http_response_code(404);
            echo json_encode(["error" => "Not found"]);
        } else {
            echo json_encode($result);
        }
        break;

    // =========================
    // DELETE (ID REQUIRED)
    // =========================
    case "DELETE":
        if (!$id) {
            http_response_code(400);
            echo json_encode(["error" => "ID required for DELETE"]);
            break;
        }

        $stmt = $db->prepare(
            "DELETE FROM pageviews WHERE id = ? RETURNING *"
        );
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