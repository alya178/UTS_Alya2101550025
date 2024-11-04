<?php
header("Content-Type: application/json");
include 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$object = $request[0];

switch ($method) {
    case 'GET':
        if (isset($request[1])) {
            $id = filter_var($request[1], FILTER_VALIDATE_INT);
            if ($id === false) {
                response(400, "Request Tidak Valid.");
                break;
            }
            $stmt = $pdo->prepare("SELECT * FROM instruments WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                response(200, "Berhasil Mendapatkan Data.", $data);
            } else {
                response(404, "Data Tidak Ditemukan.");
            }
        } else {
            $name = isset($_GET['name']) ? "%" . htmlspecialchars($_GET['name']) . "%" : "%";
            $stmt = $pdo->prepare("SELECT * FROM instruments WHERE name LIKE ?");
            $stmt->execute([$name]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            response(200, "Berhasil Mendapatkan Data.", $data);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['name'], $input['type'], $input['price'], $input['stock'])) {
            $name = htmlspecialchars(trim($input['name']));
            $type = htmlspecialchars(trim($input['type']));
            $price = filter_var($input['price'], FILTER_VALIDATE_INT);
            $stock = filter_var($input['stock'], FILTER_VALIDATE_INT);

            if ($name && $type && $price !== false && $stock !== false) {
                $stmt = $pdo->prepare("INSERT INTO instruments (name, type, price, stock) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$name, $type, $price, $stock])) {
                    response(201, "Data Berhasil Ditambahkan.", ['id' => $pdo->lastInsertId()]);
                }
            } else {
                response(400, "Request Tidak Valid.");
            }
        } else {
            response(400, "Request Tidak Valid.");
        }
        break;

    case 'PUT':
        if (isset($request[1])) {
            $id = filter_var($request[1], FILTER_VALIDATE_INT);
            if ($id === false) {
                response(400, "Request Tidak Valid.");
                break;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("SELECT * FROM instruments WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                if (isset($input['name'], $input['type'], $input['price'], $input['stock'])) {
                    $name = htmlspecialchars(trim($input['name']));
                    $type = htmlspecialchars(trim($input['type']));
                    $price = filter_var($input['price'], FILTER_VALIDATE_INT);
                    $stock = filter_var($input['stock'], FILTER_VALIDATE_INT);

                    if ($name && $type && $price !== false && $stock !== false) {
                        $stmt = $pdo->prepare("UPDATE instruments SET name = ?, type = ?, price = ?, stock = ? WHERE id = ?");
                        if ($stmt->execute([$name, $type, $price, $stock, $id])) {
                            response(200, "Data Berhasil Diperbarui.");
                        }
                    } else {
                        response(400, "Request Tidak Valid.");
                    }
                } else {
                    response(400, "Request Tidak Valid.");
                }
            } else {
                response(404, "Data Tidak Ditemukan.");
            }
        }
        break;

    case 'DELETE':
        if (isset($request[1])) {
            $id = filter_var($request[1], FILTER_VALIDATE_INT);
            if ($id === false) {
                response(400, "Request Tidak Valid.");
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM instruments WHERE id = ?");
            if ($stmt->execute([$id])) {
                if ($stmt->rowCount() > 0) {
                    response(200, "Data Berhasil Dihapus.");
                } else {
                    response(404, "Data Tidak Ditemukan.");
                }
            }
        }
        break;

    default:
        response(405, "Method not allowed.");
}

function response($status, $message, $data = null) {
    http_response_code($status);
    echo json_encode([
        "status" => $status === 200 || $status === 201 ? "success" : "error",
        "message" => $message,
        "data" => $data,
    ]);
}
?>