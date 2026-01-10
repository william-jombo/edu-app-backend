


<?php
require_once '../../includes/cors.php';
// FILE: backend/api/teachers/get_submission_list.php
// Updated for new database structure
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once '../../config/Database.php';

$teacher_id = $_GET['teacher_id'] ?? null;

if (!$teacher_id) {
    echo json_encode(['success' => false, 'message' => 'Teacher ID required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Updated query for new database structure
    $query = "SELECT 
                a.id as assignment_id,
                a.title as assignment_title,
                a.due_date,
                a.total_points,
                a.created_at,
                s.subject_name,
                c.class_name,
                c.grade_level,
                (SELECT COUNT(DISTINCT st.id) 
                 FROM students st 
                 WHERE st.class_id = a.class_id 
                 AND st.status = 'active'
                 AND EXISTS (
                     SELECT 1 FROM subject_enrollments se 
                     WHERE se.student_id = st.id 
                     AND se.subject_id = a.subject_id 
                     AND se.status = 'active'
                 )) as total_students,
                (SELECT COUNT(*) 
                 FROM assignment_submissions asub 
                 WHERE asub.assignment_id = a.id) as submitted_count,
                (SELECT COUNT(*) 
                 FROM assignment_submissions asub 
                 WHERE asub.assignment_id = a.id 
                 AND asub.status = 'graded') as graded_count,
                ((SELECT COUNT(DISTINCT st.id) 
                  FROM students st 
                  WHERE st.class_id = a.class_id 
                  AND st.status = 'active'
                  AND EXISTS (
                      SELECT 1 FROM subject_enrollments se 
                      WHERE se.student_id = st.id 
                      AND se.subject_id = a.subject_id 
                      AND se.status = 'active'
                  )) - 
                 (SELECT COUNT(*) 
                  FROM assignment_submissions asub 
                  WHERE asub.assignment_id = a.id)) as pending_count
              FROM assignments a
              INNER JOIN subjects s ON a.subject_id = s.id
              INNER JOIN classes c ON a.class_id = c.id
              WHERE a.teacher_id = ? 
              AND a.status = 'active'
              ORDER BY a.due_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$teacher_id]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add percentage calculations and status flags
    foreach ($assignments as &$assignment) {
        $total = (int)$assignment['total_students'];
        $submitted = (int)$assignment['submitted_count'];
        $graded = (int)$assignment['graded_count'];
        
        $assignment['submission_percentage'] = $total > 0 ? round(($submitted / $total) * 100, 1) : 0;
        $assignment['grading_percentage'] = $submitted > 0 ? round(($graded / $submitted) * 100, 1) : 0;
        
        // Add status indicator
        if ($submitted === 0) {
            $assignment['status_text'] = 'No submissions yet';
        } elseif ($graded === $submitted) {
            $assignment['status_text'] = 'All graded';
        } elseif ($graded > 0) {
            $assignment['status_text'] = 'Partially graded';
        } else {
            $assignment['status_text'] = 'Needs grading';
        }
        
        // Check if overdue
        $due_date = new DateTime($assignment['due_date']);
        $now = new DateTime();
        $assignment['is_overdue'] = $now > $due_date;
    }
    
    echo json_encode([
        'success' => true,
        'assignments' => $assignments
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>