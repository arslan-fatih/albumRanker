<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Handle different actions
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check':
        // Check if user is logged in
        if (isLoggedIn()) {
            $stmt = $conn->prepare("SELECT id, username, email, profile_pic FROM users WHERE id = ?");
            $stmt->execute([getCurrentUserId()]);
            $user = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
        } else {
            echo json_encode([
                'success' => false
            ]);
        }
        break;
        
    case 'logout':
        // Handle logout
        session_destroy();
        echo json_encode([
            'success' => true
        ]);
        break;
        
    default:
        // Handle login/register
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['is_login'] ?? 'register';
            
            if ($action === '1') { // Login
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                
                if (empty($email) || empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
                    exit;
                }
                
                $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    echo json_encode(['success' => true, 'message' => 'Login successful']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
                }
            } else { // Register
                $username = $_POST['username'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                
                if (empty($username) || empty($email) || empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'All fields are required']);
                    exit;
                }
                
                // Check if email already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Email already exists']);
                    exit;
                }
                
                // Hash password and insert user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $result = $stmt->execute([$username, $email, $hashedPassword]);
                
                if ($result) {
                    $_SESSION['user_id'] = $conn->lastInsertId();
                    echo json_encode(['success' => true, 'message' => 'Registration successful']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Registration failed']);
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        }
        break;
} 