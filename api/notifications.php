<?php
/**
 * Notifications API
 * AJAX endpoint for notification management
 */

require_once '../config/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check authentication
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'];

switch ($method) {
    case 'GET':
        // Get notifications for current user
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        
        $query = "SELECT id, subject, message, case_id, type, is_read, created_at 
                  FROM notifications 
                  WHERE recipient_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get unread count
        $count_query = "SELECT COUNT(*) as unread_count 
                        FROM notifications 
                        WHERE recipient_id = :user_id AND is_read = 0";
        
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bindParam(':user_id', $user_id);
        $count_stmt->execute();
        
        $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Format dates for display
        foreach ($notifications as &$notification) {
            $notification['created_at'] = date('M j, g:i A', strtotime($notification['created_at']));
        }
        
        echo json_encode([
            'notifications' => $notifications,
            'unread_count' => $count_result['unread_count']
        ]);
        break;
        
    case 'POST':
        // Mark notification as read
        $input = json_decode(file_get_contents('php://input'), true);
        $notification_id = $input['notification_id'] ?? 0;
        
        if ($notification_id) {
            $query = "UPDATE notifications 
                      SET is_read = 1, read_at = NOW() 
                      WHERE id = :id AND recipient_id = :user_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $notification_id);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update notification']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
        }
        break;
        
    case 'PUT':
        // Mark all notifications as read
        $query = "UPDATE notifications 
                  SET is_read = 1, read_at = NOW() 
                  WHERE recipient_id = :user_id AND is_read = 0";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update notifications']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>