<?php
// Set response header to JSON
header('Content-Type: application/json');

// Retrieve Supabase database credentials from environment variables set in Vercel
$host     = getenv('DB_HOST');
$port     = getenv('DB_PORT') ?: '5432';
$database = getenv('DB_DATABASE') ?: 'postgres';
$username = getenv('DB_USERNAME') ?: 'postgres';
$password = getenv('DB_PASSWORD');

// Check if POST request contains required field
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['txtName'])) {

    if (!$host || !$password) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database connection details not configured."]);
        exit;
    }

    try {
        // Build PostgreSQL DSN connection string
        $dsn = "pgsql:host=$host;port=$port;dbname=$database;sslmode=require";
        
        // Connect via PDO
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO_ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO_FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 10,
        ]);

        // Get and sanitize POST input records
        $txtName    = trim($_POST['txtName'] ?? '');
        $txtEmail   = trim($_POST['txtEmail'] ?? '');
        $txtPhone   = trim($_POST['txtPhone'] ?? '');
        $txtMessage = trim($_POST['txtMessage'] ?? '');

        // PostgreSQL INSERT statement
        $sql = "INSERT INTO tbl_contact (fldName, fldEmail, fldPhone, fldMessage) VALUES (:name, :email, :phone, :message)";
        
        $stmt = $pdo->prepare($sql);
        $executed = $stmt->execute([
            ':name'    => $txtName,
            ':email'   => $txtEmail,
            ':phone'   => $txtPhone,
            ':message' => $txtMessage,
        ]);

        if ($executed) {
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Contact Records Inserted Successfully!"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to insert record."]);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database Connection Failed: " . $e->getMessage()]);
    }

} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Are you a genuine visitor?"]);
}
?>