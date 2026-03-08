<?php
// backend/api/teacher/announcements.php
// Teacher-only announcements access

require_once '../../config/database.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDBConnection();

// Get teacher info from session
session_start();
$teacherUserId = $_SESSION['user_id'] ?? null;
$teacherRole = $_SESSION['role'] ?? null;

// Verify teacher access
if (!$teacherUserId || $teacherRole !== 'teacher') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Teacher access required']);
    exit();
}

switch ($method) {
    case 'GET':
        handleGet($conn, $teacherUserId);
        break;
    case 'POST':
        handlePost($conn, $teacherUserId);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function handleGet($conn, $teacherUserId) {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'list') {
        // Get all announcements targeted to teachers
        $query = "SELECT a.*, 
                  u.email as created_by_email,
                  ar.read_at,
                  CASE WHEN ar.id IS NOT NULL THEN true ELSE false END as is_read
                  FROM announcements a
                  LEFT JOIN users u ON a.created_by = u.id
                  LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = :user_id
                  WHERE a.status = 'active' 
                  AND (a.target_audience = 'teachers' OR a.target_audience = 'both')
                  ORDER BY 
                    CASE WHEN ar.id IS NULL THEN 0 ELSE 1 END, -- Unread first
                    a.priority = 'urgent' DESC,
                    a.priority = 'high' DESC,
                    a.created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $teacherUserId);
        $stmt->execute();
        
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dates and add helper fields
        foreach ($announcements as &$announcement) {
            $announcement['is_new'] = !$announcement['is_read'];
            $announcement['formatted_date'] = date('M d, Y h:i A', strtotime($announcement['created_at']));
        }
        
        echo json_encode(['success' => true, 'data' => $announcements]);
        
    } elseif ($action === 'unread_count') {
        // Get count of unread announcements
        $query = "SELECT COUNT(*) as unread_count
                  FROM announcements a
                  LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = :user_id
                  WHERE a.status = 'active' 
                  AND (a.target_audience = 'teachers' OR a.target_audience = 'both')
                  AND ar.id IS NULL";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $teacherUserId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'unread_count' => (int)$result['unread_count']]);
        
    } elseif ($action === 'single') {
        // Get single announcement
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID required']);
            return;
        }
        
        $query = "SELECT a.*, 
                  u.email as created_by_email,
                  ar.read_at,
                  CASE WHEN ar.id IS NOT NULL THEN true ELSE false END as is_read
                  FROM announcements a
                  LEFT JOIN users u ON a.created_by = u.id
                  LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = :user_id
                  WHERE a.id = :id 
                  AND a.status = 'active'
                  AND (a.target_audience = 'teachers' OR a.target_audience = 'both')";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $teacherUserId);
        $stmt->execute();
        
        $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($announcement) {
            $announcement['formatted_date'] = date('M d, Y h:i A', strtotime($announcement['created_at']));
            echo json_encode(['success' => true, 'data' => $announcement]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Announcement not found']);
        }
        
    } elseif ($action === 'recent') {
        // Get recent announcements (last 5)
        $limit = $_GET['limit'] ?? 5;
        
        $query = "SELECT a.id, a.title, a.priority, a.created_at,
                  CASE WHEN ar.id IS NOT NULL THEN true ELSE false END as is_read
                  FROM announcements a
                  LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = :user_id
                  WHERE a.status = 'active' 
                  AND (a.target_audience = 'teachers' OR a.target_audience = 'both')
                  ORDER BY a.created_at DESC
                  LIMIT :limit";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $teacherUserId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $announcements]);
    }
}

function handlePost($conn, $teacherUserId) {
    $action = $_POST['action'] ?? $_GET['action'] ?? 'mark_read';
    
    if ($action === 'mark_read') {
        // Mark announcement as read
        $data = json_decode(file_get_contents('php://input'), true);
        $announcementId = $data['announcement_id'] ?? null;
        
        if (!$announcementId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Announcement ID required']);
            return;
        }
        
        // Verify announcement exists and is accessible to teacher
        $verifyQuery = "SELECT id FROM announcements 
                       WHERE id = :id 
                       AND status = 'active'
                       AND (target_audience = 'teachers' OR target_audience = 'both')";
        $verifyStmt = $conn->prepare($verifyQuery);
        $verifyStmt->bindParam(':id', $announcementId);
        $verifyStmt->execute();
        
        if (!$verifyStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Announcement not found']);
            return;
        }
        
        try {
            $query = "INSERT INTO announcement_reads (announcement_id, user_id)
                      VALUES (:announcement_id, :user_id)
                      ON CONFLICT (announcement_id, user_id) DO NOTHING
                      RETURNING read_at";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':announcement_id', $announcementId);
            $stmt->bindParam(':user_id', $teacherUserId);
            
            if ($stmt->execute()) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Marked as read',
                    'read_at' => $result['read_at'] ?? null
                ]);
            } else {
                throw new Exception('Failed to mark as read');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
    } elseif ($action === 'mark_all_read') {
        // Mark all announcements as read
        try {
            $query = "INSERT INTO announcement_reads (announcement_id, user_id)
                      SELECT a.id, :user_id
                      FROM announcements a
                      LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = :user_id
                      WHERE a.status = 'active'
                      AND (a.target_audience = 'teachers' OR a.target_audience = 'both')
                      AND ar.id IS NULL
                      ON CONFLICT (announcement_id, user_id) DO NOTHING";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $teacherUserId);
            
            if ($stmt->execute()) {
                $count = $stmt->rowCount();
                echo json_encode([
                    'success' => true, 
                    'message' => "Marked {$count} announcements as read"
                ]);
            } else {
                throw new Exception('Failed to mark all as read');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?>