<?php
// Set response headers for JSON output and CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 1. Read environment variables from Vercel / server environment
$host     = getenv('DB_HOST');
$port     = getenv('DB_PORT') ?: '5432';
$dbname   = getenv('DB_NAME');
$user     = getenv('DB_USER');
$password = getenv('DB_PASS');

// 2. Validate environment variable existence
if (!$host || !$dbname || !$user || !$password) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Database connection details not configured."
    ]);
    exit();
}

// 3. Establish PDO PostgreSQL connection
try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Database connection failed: " . $e->getMessage()
    ]);
    exit();
}

// 4. Process incoming request payload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON payload or form-encoded POST data
    $rawInput = file_get_contents('php://input');
    $data     = json_decode($rawInput, true);

    $name    = trim($data['name'] ?? $_POST['name'] ?? '');
    $email   = trim($data['email'] ?? $_POST['email'] ?? '');
    $subject = trim($data['subject'] ?? $_POST['subject'] ?? '');
    $message = trim($data['message'] ?? $_POST['message'] ?? '');

    // Validate required fields
    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        echo json_encode([
            "status"  => "error",
            "message" => "Please fill in all required fields (name, email, message)."
        ]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            "status"  => "error",
            "message" => "Invalid email address format."
        ]);
        exit();
    }

    // 5. Insert submission into PostgreSQL table
    try {
        $sql  = "INSERT INTO contact_form (name, email, subject, message) VALUES (:name, :email, :subject, :message)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'    => $name,
            ':email'   => $email,
            ':subject' => $subject,
            ':message' => $message
        ]);

        http_response_code(200);
        echo json_encode([
            "status"  => "success",
            "message" => "Thank you! Your message has been sent."
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status"  => "error",
            "message" => "Failed to save submission: " . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "status"  => "error",
        "message" => "Method not allowed. Use POST."
    ]);
}
?>
