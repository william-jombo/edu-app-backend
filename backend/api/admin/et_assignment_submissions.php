

<?php
// ============================================================================
// FILE: backend/api/admin/get_assignment_submissions.php
// ============================================================================
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once '../../config/database.php';

$assignment_id = $_GET['assignment_id'] ?? null;

if (!$assignment_id) {
    echo json_encode(['success' => false, 'message' => 'Assignment ID required']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT 
                asub.id,
                asub.submission_file,
                asub.submission_text,
                asub.submitted_at,
                asub.grade,
                asub.feedback,
                asub.status,
                asub.graded_at,
                CONCAT(s.firstname, ' ', s.lastname) as student_name,
                s.student_number,
                u.email as student_email,
                a.total_points
              FROM assignment_submissions asub
              INNER JOIN students s ON asub.student_id = s.id
              INNER JOIN users u ON s.user_id = u.id
              INNER JOIN assignments a ON asub.assignment_id = a.id
              WHERE asub.assignment_id = ?
              ORDER BY asub.submitted_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$assignment_id]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get assignment details
    $assignQuery = "SELECT 
                      a.id,
                      a.title,
                      a.description,
                      a.due_date,
                      a.total_points,
                      a.status,
                      subj.subject_name,
                      subj.subject_code,
                      c.class_name,
                      c.grade_level,
                      CONCAT(t.firstname, ' ', t.lastname) as teacher_name
                    FROM assignments a
                    INNER JOIN subjects subj ON a.subject_id = subj.id
                    INNER JOIN classes c ON a.class_id = c.id
                    INNER JOIN teachers t ON a.teacher_id = t.id
                    WHERE a.id = ?";
    
    $assignStmt = $conn->prepare($assignQuery);
    $assignStmt->execute([$assignment_id]);
    $assignmentInfo = $assignStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'submissions' => $submissions,
        'assignment_info' => $assignmentInfo
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>


<?php