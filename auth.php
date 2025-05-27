<?php
/**
 * AlbumRanker - Kimlik Doğrulama API
 * 
 * Bu dosya, kullanıcı kimlik doğrulama işlemlerini yönetir:
 * - Giriş yapma
 * - Kayıt olma
 * - Oturum kontrolü
 * - Çıkış yapma
 * 
 * Tüm işlemler JSON formatında yanıt döndürür.
 * 
 * @author AlbumRanker Team
 * @version 1.0
 */

session_start();
require_once 'config.php';

// JSON yanıt formatını ayarla
header('Content-Type: application/json');

/**
 * İstek parametrelerinden action değerini al
 * Eğer action belirtilmemişse boş string döndür
 */
$action = $_GET['action'] ?? '';

/**
 * İstek türüne göre işlem yap
 * 
 * Mevcut action'lar:
 * - check: Kullanıcı oturum durumunu kontrol et
 * - logout: Kullanıcı oturumunu sonlandır
 * - default: Giriş veya kayıt işlemi
 */
switch ($action) {
    case 'check':
        /**
         * Kullanıcı oturum durumunu kontrol et
         * Eğer kullanıcı giriş yapmışsa kullanıcı bilgilerini döndür
         */
        if (isLoggedIn()) {
            // Kullanıcı bilgilerini veritabanından çek
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
        /**
         * Kullanıcı oturumunu sonlandır
         * Tüm oturum verilerini temizle
         */
        session_destroy();
        echo json_encode([
            'success' => true
        ]);
        break;
        
    default:
        /**
         * Giriş veya kayıt işlemi
         * Sadece POST isteklerini kabul et
         */
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // İşlem türünü belirle (giriş veya kayıt)
            $action = $_POST['is_login'] ?? 'register';
            
            if ($action === '1') { // Giriş işlemi
                /**
                 * Kullanıcı girişi
                 * 1. Email ve şifre kontrolü
                 * 2. Kullanıcı doğrulama
                 * 3. Oturum başlatma
                 */
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                
                // Zorunlu alanları kontrol et
                if (empty($email) || empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
                    exit;
                }
                
                // Kullanıcıyı veritabanında ara
                $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                // Şifre doğrulama ve oturum başlatma
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    echo json_encode(['success' => true, 'message' => 'Login successful']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
                }
            } else { // Kayıt işlemi
                /**
                 * Yeni kullanıcı kaydı
                 * 1. Form verilerini al
                 * 2. Veri doğrulama
                 * 3. Email kontrolü
                 * 4. Şifre hashleme
                 * 5. Kullanıcı kaydı
                 */
                $username = $_POST['username'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                
                // Zorunlu alanları kontrol et
                if (empty($username) || empty($email) || empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'All fields are required']);
                    exit;
                }
                
                // Email adresinin benzersiz olduğunu kontrol et
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Email already exists']);
                    exit;
                }
                
                // Şifreyi güvenli bir şekilde hashle ve kullanıcıyı kaydet
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $result = $stmt->execute([$username, $email, $hashedPassword]);
                
                if ($result) {
                    // Başarılı kayıt sonrası oturum başlat
                    $_SESSION['user_id'] = $conn->lastInsertId();
                    echo json_encode(['success' => true, 'message' => 'Registration successful']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Registration failed']);
                }
            }
        } else {
            // POST dışındaki istekleri reddet
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        }
        break;
} 