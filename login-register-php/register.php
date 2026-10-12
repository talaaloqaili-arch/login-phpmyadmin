<?php
include "connect.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method not allowed.");
}

$username = trim($_POST["name"] ?? $_POST["username"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($username === "" || $email === "" || $password === "") {
    http_response_code(400);
    exit("Please complete all registration fields.");
}

$checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ? LIMIT 1");
$checkEmail->bind_param("s", $email);
$checkEmail->execute();
$checkEmail->store_result();

if ($checkEmail->num_rows > 0) {
    exit("Email already exists.");
}

$hashPassword = password_hash($password, PASSWORD_DEFAULT);
$insert = $conn->prepare(
    "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
);
$insert->bind_param("sss", $username, $email, $hashPassword);

if ($insert->execute()) {
    echo "Registration successful.";
} else {
    http_response_code(500);
    echo "Registration failed: " . htmlspecialchars($insert->error, ENT_QUOTES, "UTF-8");
}
?>
