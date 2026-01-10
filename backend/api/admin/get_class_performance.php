


<?php
require_once '../../includes/cors.php';
// ============================================================================
// FILE: backend/api/admin/get_student_performance.php
// ============================================================================
require_once '../../config/database.php';

$class_id = $_GET['class_id'] ?? null;
$subject_id = $_GET['subject_id'] ?? null;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $whereClause = "WHERE s.status = 'active'";
    $params = [];
    
    if ($class_id) {
        $whereClause .= " AND s.class_id = ?";
        $params[] = $class_id;
    }
    
    // Base query
    $query = "SELECT 
                s.id as student_id,
                s.student_number,
                CONCAT(s.firstname, ' ', s.lastname) as student_name,
                c.class_name,
                c.grade_level,
                subj.subject_name,
                ROUND(AVG(g.score), 2) as average_score,
                ROUND(AVG(g.percentage), 2) as average_percentage,
                COUNT(g.id) as total_assessments,
                (SELECT COUNT(*) 
                 FROM attendance att 
                 WHERE att.student_id = s.id AND att.status = 'present') as present_days,
                (SELECT COUNT(*) 
                 FROM attendance att 
                 WHERE att.student_id = s.id) as total_days
              FROM students s
              INNER JOIN classes c ON s.class_id = c.id
              LEFT JOIN grades g ON g.student_id = s.id";
    
    if ($subject_id) {
        $query .= " AND g.subject_id = ?";
        array_unshift($params, $subject_id);
    }
    
    $query .= " LEFT JOIN subjects subj ON g.subject_id = subj.id
              $whereClause
              GROUP BY s.id, c.class_name, c.grade_level, subj.subject_name
              ORDER BY average_percentage DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $performance
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
