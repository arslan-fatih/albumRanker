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
        
        // Insert genres and tracks using helper functions
        insertGenres($conn, $albumId, $data['genres'] ?? []);
        insertTracks($conn, $albumId, $data['tracks'] ?? []);
        
        $conn->commit();
        return ['success' => true, 'message' => SUCCESS_MESSAGES['ALBUM_CREATED'], 'album_id' => $albumId];
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log($e->getMessage());
        return ['success' => false, 'message' => ERROR_MESSAGES['ALBUM_CREATE_FAILED']];
    }
}

// Update album
function updateAlbum($albumId, $userId, $data) {
    global $conn;
    
    try {
        if (!verifyAlbumOwnership($conn, $albumId, $userId)) {
            return ['success' => false, 'message' => ERROR_MESSAGES['UNAUTHORIZED']];
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
        insertGenres($conn, $albumId, $data['genres'] ?? []);
        
        // Update tracks
        $stmt = $conn->prepare("DELETE FROM tracks WHERE album_id = ?");
        $stmt->execute([$albumId]);
        insertTracks($conn, $albumId, $data['tracks'] ?? []);
        
        $conn->commit();
        return ['success' => true, 'message' => SUCCESS_MESSAGES['ALBUM_UPDATED']];
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log($e->getMessage());
        return ['success' => false, 'message' => ERROR_MESSAGES['ALBUM_UPDATE_FAILED']];
    }
}

// Delete album
function deleteAlbum($albumId, $userId) {
    global $conn;
    
    return handleDatabaseOperation(function() use ($conn, $albumId, $userId) {
        if (!verifyAlbumOwnership($conn, $albumId, $userId)) {
            return ['success' => false, 'message' => ERROR_MESSAGES['UNAUTHORIZED']];
        }
        
        $stmt = $conn->prepare("DELETE FROM albums WHERE id = ?");
        $stmt->execute([$albumId]);
        
        return ['success' => true, 'message' => SUCCESS_MESSAGES['ALBUM_DELETED']];
    });
}

/**
 * Handle database operations with error handling
 * @param callable $operation The database operation to perform
 * @return array Success status and message
 */
function handleDatabaseOperation($operation) {
    try {
        return $operation();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return ['success' => false, 'message' => 'Operation failed'];
    }
}

// Helper function for processing genres
function processGenres($genresString) {
    $genres = [];
    if ($genresString) {
        foreach (explode(',', $genresString) as $genre) {
            list($id, $name) = explode(':', $genre);
            $genres[] = ['id' => $id, 'name' => $name];
        }
    }
    return $genres;
}

// Helper function for processing tracks
function processTracks($tracksString) {
    $tracks = [];
    if ($tracksString) {
        foreach (explode(',', $tracksString) as $track) {
            list($id, $title, $duration, $track_number) = explode(':', $track);
            $tracks[] = [
                'id' => $id,
                'title' => $title,
                'duration' => $duration,
                'track_number' => $track_number
            ];
        }
    }
    return $tracks;
}

// Helper function for inserting genres
function insertGenres($conn, $albumId, $genres) {
    if (!empty($genres)) {
        $stmt = $conn->prepare("INSERT INTO album_genres (album_id, genre_id) VALUES (?, ?)");
        foreach ($genres as $genreId) {
            $stmt->execute([$albumId, $genreId]);
        }
    }
}

// Helper function for inserting tracks
function insertTracks($conn, $albumId, $tracks) {
    if (!empty($tracks)) {
        $stmt = $conn->prepare("
            INSERT INTO tracks (album_id, title, duration, track_number)
            VALUES (?, ?, ?, ?)
        ");
        foreach ($tracks as $index => $track) {
            $stmt->execute([
                $albumId,
                $track['title'],
                $track['duration'],
                $index + 1
            ]);
        }
    }
}

// Error messages as constants
const ERROR_MESSAGES = [
    'UNAUTHORIZED' => 'Unauthorized',
    'ALBUM_CREATE_FAILED' => 'Album creation failed',
    'ALBUM_UPDATE_FAILED' => 'Album update failed',
    'ALBUM_DELETE_FAILED' => 'Album deletion failed',
    'OPERATION_FAILED' => 'Operation failed'
];

// Success messages as constants
const SUCCESS_MESSAGES = [
    'ALBUM_CREATED' => 'Album created successfully',
    'ALBUM_UPDATED' => 'Album updated successfully',
    'ALBUM_DELETED' => 'Album deleted successfully',
    'RATING_SAVED' => 'Rating saved successfully',
    'REVIEW_ADDED' => 'Review added successfully'
];

/**
 * Get album details with optimized queries
 * @param int $albumId Album ID
 * @return array|null Album details or null if not found
 */
function getAlbumDetails($albumId) {
    global $conn;
    
    return handleDatabaseOperation(function() use ($conn, $albumId) {
        $stmt = $conn->prepare("
            SELECT 
                a.*,
                u.username as uploader,
                (SELECT AVG(rating) FROM ratings WHERE album_id = a.id) as avg_rating,
                (SELECT COUNT(*) FROM ratings WHERE album_id = a.id) as rating_count,
                (SELECT COUNT(*) FROM reviews WHERE album_id = a.id) as review_count,
                GROUP_CONCAT(DISTINCT g.id, ':', g.name) as genres,
                GROUP_CONCAT(
                    t.id, ':', t.title, ':', t.duration, ':', t.track_number
                    ORDER BY t.track_number
                ) as tracks
            FROM albums a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN album_genres ag ON a.id = ag.album_id
            LEFT JOIN genres g ON ag.genre_id = g.id
            LEFT JOIN tracks t ON a.id = t.album_id
            WHERE a.id = ?
            GROUP BY a.id
        ");
        
        $stmt->execute([$albumId]);
        $album = $stmt->fetch();
        
        if (!$album) {
            return null;
        }
        
        $album['genres'] = processGenres($album['genres']);
        $album['tracks'] = processTracks($album['tracks']);
        
        return $album;
    });
}

/**
 * Rate an album
 * @param int $userId User ID
 * @param int $albumId Album ID
 * @param float $rating Rating value
 * @return array Success status and message
 */
function rateAlbum($userId, $albumId, $rating) {
    global $conn;
    
    return handleDatabaseOperation(function() use ($conn, $userId, $albumId, $rating) {
        $stmt = $conn->prepare("
            INSERT INTO ratings (user_id, album_id, rating)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = ?
        ");
        $stmt->execute([$userId, $albumId, $rating, $rating]);
        
        return ['success' => true, 'message' => SUCCESS_MESSAGES['RATING_SAVED']];
    });
}

/**
 * Add a review for an album
 * @param int $userId User ID
 * @param int $albumId Album ID
 * @param string $content Review content
 * @return array Success status and message
 */
function addReview($userId, $albumId, $content) {
    global $conn;
    
    return handleDatabaseOperation(function() use ($conn, $userId, $albumId, $content) {
        $stmt = $conn->prepare("
            INSERT INTO reviews (user_id, album_id, content)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $albumId, $content]);
        
        return ['success' => true, 'message' => SUCCESS_MESSAGES['REVIEW_ADDED']];
    });
}

/**
 * Get album reviews with pagination
 * @param int $albumId Album ID
 * @param int $page Page number
 * @param int $limit Reviews per page
 * @return array Reviews, total count and page count
 */
function getAlbumReviews($albumId, $page = 1, $limit = 10) {
    global $conn;
    
    return handleDatabaseOperation(function() use ($conn, $albumId, $page, $limit) {
        $offset = ($page - 1) * $limit;
        
        // Get reviews with user information
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
        
        // Get total review count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM reviews WHERE album_id = ?");
        $stmt->execute([$albumId]);
        $total = $stmt->fetchColumn();
        
        return [
            'reviews' => $reviews,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    });
}

// Search albums
function searchAlbums($query, $filters = [], $page = 1, $limit = 12) {
    global $conn;
    
    return handleDatabaseOperation(function() use ($conn, $query, $filters, $page, $limit) {
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
    });
}

// Helper function to verify album ownership
function verifyAlbumOwnership($conn, $albumId, $userId) {
    $stmt = $conn->prepare("SELECT id FROM albums WHERE id = ? AND user_id = ?");
    $stmt->execute([$albumId, $userId]);
    return $stmt->rowCount() > 0;
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