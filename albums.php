<?php
require_once 'config.php';

// Create new album
function createAlbum($userId, $data) {
    global $conn;
    
    try {
        $conn->beginTransaction();
        
        // Insert album
        $stmt = $conn->prepare("
            INSERT INTO albums (title, artist, cover_image, release_date, description, user_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['artist'],
            $data['cover_image'],
            $data['release_date'],
            $data['description'],
            $userId
        ]);
        
        $albumId = $conn->lastInsertId();
        
        // Insert genres
        if (!empty($data['genres'])) {
            $stmt = $conn->prepare("INSERT INTO album_genres (album_id, genre_id) VALUES (?, ?)");
            foreach ($data['genres'] as $genreId) {
                $stmt->execute([$albumId, $genreId]);
            }
        }
        
        // Insert tracks
        if (!empty($data['tracks'])) {
            $stmt = $conn->prepare("
                INSERT INTO tracks (album_id, title, duration, track_number)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($data['tracks'] as $index => $track) {
                $stmt->execute([
                    $albumId,
                    $track['title'],
                    $track['duration'],
                    $index + 1
                ]);
            }
        }
        
        $conn->commit();
        return ['success' => true, 'message' => 'Album created successfully', 'album_id' => $albumId];
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log($e->getMessage());
        return ['success' => false, 'message' => 'Album creation failed'];
    }
}

// Update album
function updateAlbum($albumId, $userId, $data) {
    global $conn;
    
    try {
        // Verify ownership
        $stmt = $conn->prepare("SELECT id FROM albums WHERE id = ? AND user_id = ?");
        $stmt->execute([$albumId, $userId]);
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        $conn->beginTransaction();
        
        // Update album
        $stmt = $conn->prepare("
            UPDATE albums
            SET title = ?, artist = ?, cover_image = ?, release_date = ?, description = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['title'],
            $data['artist'],
            $data['cover_image'],
            $data['release_date'],
            $data['description'],
            $albumId
        ]);
        
        // Update genres
        $stmt = $conn->prepare("DELETE FROM album_genres WHERE album_id = ?");
        $stmt->execute([$albumId]);
        
        if (!empty($data['genres'])) {
            $stmt = $conn->prepare("INSERT INTO album_genres (album_id, genre_id) VALUES (?, ?)");
            foreach ($data['genres'] as $genreId) {
                $stmt->execute([$albumId, $genreId]);
            }
        }
        
        // Update tracks
        $stmt = $conn->prepare("DELETE FROM tracks WHERE album_id = ?");
        $stmt->execute([$albumId]);
        
        if (!empty($data['tracks'])) {
            $stmt = $conn->prepare("
                INSERT INTO tracks (album_id, title, duration, track_number)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($data['tracks'] as $index => $track) {
                $stmt->execute([
                    $albumId,
                    $track['title'],
                    $track['duration'],
                    $index + 1
                ]);
            }
        }
        
        $conn->commit();
        return ['success' => true, 'message' => 'Album updated successfully'];
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log($e->getMessage());
        return ['success' => false, 'message' => 'Album update failed'];
    }
}

// Delete album
function deleteAlbum($albumId, $userId) {
    global $conn;
    
    try {
        // Verify ownership
        $stmt = $conn->prepare("SELECT id FROM albums WHERE id = ? AND user_id = ?");
        $stmt->execute([$albumId, $userId]);
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        $stmt = $conn->prepare("DELETE FROM albums WHERE id = ?");
        $stmt->execute([$albumId]);
        
        return ['success' => true, 'message' => 'Album deleted successfully'];
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return ['success' => false, 'message' => 'Album deletion failed'];
    }
}

// Get album details
function getAlbumDetails($albumId) {
    global $conn;
    
    try {
        // Get album info
        $stmt = $conn->prepare("
            SELECT a.*, u.username as uploader,
                   (SELECT AVG(rating) FROM ratings WHERE album_id = a.id) as avg_rating,
                   (SELECT COUNT(*) FROM ratings WHERE album_id = a.id) as rating_count,
                   (SELECT COUNT(*) FROM reviews WHERE album_id = a.id) as review_count
            FROM albums a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$albumId]);
        $album = $stmt->fetch();
        
        if (!$album) {
            return null;
        }
        
        // Get genres
        $stmt = $conn->prepare("
            SELECT g.*
            FROM genres g
            JOIN album_genres ag ON g.id = ag.genre_id
            WHERE ag.album_id = ?
        ");
        $stmt->execute([$albumId]);
        $album['genres'] = $stmt->fetchAll();
        
        // Get tracks
        $stmt = $conn->prepare("
            SELECT *
            FROM tracks
            WHERE album_id = ?
            ORDER BY track_number
        ");
        $stmt->execute([$albumId]);
        $album['tracks'] = $stmt->fetchAll();
        
        return $album;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return null;
    }
}

// Rate album
function rateAlbum($userId, $albumId, $rating) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO ratings (user_id, album_id, rating)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = ?
        ");
        $stmt->execute([$userId, $albumId, $rating, $rating]);
        
        return ['success' => true, 'message' => 'Rating saved successfully'];
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return ['success' => false, 'message' => 'Rating failed'];
    }
}

// Add review
function addReview($userId, $albumId, $content) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO reviews (user_id, album_id, content)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $albumId, $content]);
        
        return ['success' => true, 'message' => 'Review added successfully'];
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return ['success' => false, 'message' => 'Review failed'];
    }
}

// Get album reviews
function getAlbumReviews($albumId, $page = 1, $limit = 10) {
    global $conn;
    
    try {
        $offset = ($page - 1) * $limit;
        
        $stmt = $conn->prepare("
            SELECT r.*, u.username, u.profile_pic
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.album_id = ?
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$albumId, $limit, $offset]);
        $reviews = $stmt->fetchAll();
        
        // Get total count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM reviews WHERE album_id = ?");
        $stmt->execute([$albumId]);
        $total = $stmt->fetchColumn();
        
        return [
            'reviews' => $reviews,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return ['reviews' => [], 'total' => 0, 'pages' => 0];
    }
}

// Search albums
function searchAlbums($query, $filters = [], $page = 1, $limit = 12) {
    global $conn;
    
    try {
        $offset = ($page - 1) * $limit;
        $params = [];
        $conditions = [];
        
        // Base query
        $sql = "
            SELECT a.*, u.username as uploader,
                   (SELECT AVG(rating) FROM ratings WHERE album_id = a.id) as avg_rating,
                   (SELECT COUNT(*) FROM ratings WHERE album_id = a.id) as rating_count
            FROM albums a
            LEFT JOIN users u ON a.user_id = u.id
        ";
        
        // Add search condition
        if (!empty($query)) {
            $conditions[] = "(a.title LIKE ? OR a.artist LIKE ?)";
            $params[] = "%$query%";
            $params[] = "%$query%";
        }
        
        // Add genre filter
        if (!empty($filters['genre'])) {
            $conditions[] = "EXISTS (
                SELECT 1 FROM album_genres ag
                JOIN genres g ON ag.genre_id = g.id
                WHERE ag.album_id = a.id AND g.id = ?
            )";
            $params[] = $filters['genre'];
        }
        
        // Add sorting
        $orderBy = "ORDER BY a.created_at DESC";
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'rating':
                    $orderBy = "ORDER BY avg_rating DESC";
                    break;
                case 'reviews':
                    $orderBy = "ORDER BY (SELECT COUNT(*) FROM reviews WHERE album_id = a.id) DESC";
                    break;
                case 'newest':
                    $orderBy = "ORDER BY a.created_at DESC";
                    break;
                case 'oldest':
                    $orderBy = "ORDER BY a.created_at ASC";
                    break;
            }
        }
        
        // Combine conditions
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        // Add sorting and pagination
        $sql .= " $orderBy LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $albums = $stmt->fetchAll();
        
        // Get total count
        $countSql = str_replace("SELECT a.*, u.username as uploader,", "SELECT COUNT(*) as total", $sql);
        $countSql = preg_replace("/ORDER BY.*$/", "", $countSql);
        $stmt = $conn->prepare($countSql);
        $stmt->execute(array_slice($params, 0, -2));
        $total = $stmt->fetchColumn();
        
        return [
            'albums' => $albums,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return ['albums' => [], 'total' => 0, 'pages' => 0];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($action) {
        case 'create_album':
            if (isLoggedIn() && isset($_POST['data'])) {
                $response = createAlbum(getCurrentUserId(), $_POST['data']);
            }
            break;
            
        case 'update_album':
            if (isLoggedIn() && isset($_POST['album_id'], $_POST['data'])) {
                $response = updateAlbum($_POST['album_id'], getCurrentUserId(), $_POST['data']);
            }
            break;
            
        case 'delete_album':
            if (isLoggedIn() && isset($_POST['album_id'])) {
                $response = deleteAlbum($_POST['album_id'], getCurrentUserId());
            }
            break;
            
        case 'rate_album':
            if (isLoggedIn() && isset($_POST['album_id'], $_POST['rating'])) {
                $response = rateAlbum(getCurrentUserId(), $_POST['album_id'], $_POST['rating']);
            }
            break;
            
        case 'add_review':
            if (isLoggedIn() && isset($_POST['album_id'], $_POST['content'])) {
                $response = addReview(getCurrentUserId(), $_POST['album_id'], $_POST['content']);
            }
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($action) {
        case 'get_album':
            if (isset($_GET['id'])) {
                $album = getAlbumDetails($_GET['id']);
                if ($album) {
                    $response = ['success' => true, 'album' => $album];
                } else {
                    $response = ['success' => false, 'message' => 'Album not found'];
                }
            }
            break;
            
        case 'get_reviews':
            if (isset($_GET['album_id'])) {
                $page = $_GET['page'] ?? 1;
                $response = getAlbumReviews($_GET['album_id'], $page);
            }
            break;
            
        case 'search':
            $query = $_GET['q'] ?? '';
            $filters = [
                'genre' => $_GET['genre'] ?? null,
                'sort' => $_GET['sort'] ?? null
            ];
            $page = $_GET['page'] ?? 1;
            $response = searchAlbums($query, $filters, $page);
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?> 