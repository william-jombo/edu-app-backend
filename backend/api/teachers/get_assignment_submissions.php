<?php
require_once '../../includes/cors.php';
// FILE: backend/api/teachers/get_assignment_submissions.php
// Get all submissions for a specific assignment
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once '../../config/Database.php';

$assignment_id = $_GET['assignment_id'] ?? null;

if (!$assignment_id) {
    echo json_encode(['success' => false, 'message' => 'Assignment ID required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get assignment details
    $assignmentQuery = "SELECT 
                          a.title,
                          a.due_date,
                          a.total_points,
                          s.subject_name,
                          c.class_name
                        FROM assignments a
                        JOIN subjects s ON a.subject_id = s.id
                        JOIN classes c ON a.class_id = c.id
                        WHERE a.id = ?";
    
    $stmt = $conn->prepare($assignmentQuery);
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assignment) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        exit;
    }
    
    // Get all students who should submit (enrolled in the subject and class)
    $query = "SELECT 
                st.id as student_id,
                st.student_number,
                st.firstname,
                st.lastname,
                CONCAT(st.firstname, ' ', st.lastname) as full_name,
                u.email,
                asub.id as submission_id,
                asub.submitted_at,
                asub.submission_file,
                asub.submission_text,
                asub.grade,
                asub.feedback,
                asub.status as submission_status,
                asub.graded_at,
                t.firstname as graded_by_firstname,
                t.lastname as graded_by_lastname
              FROM students st
              JOIN users u ON st.user_id = u.id
              JOIN subject_enrollments se ON se.student_id = st.id
              JOIN assignments a ON a.id = ?
              LEFT JOIN assignment_submissions asub ON asub.assignment_id = a.id 
                    AND asub.student_id = st.id
              LEFT JOIN teachers t ON asub.graded_by = t.id
              WHERE st.class_id = a.class_id
              AND se.subject_id = a.subject_id
              AND st.status = 'active'
              AND se.status = 'active'
              ORDER BY st.lastname, st.firstname";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$assignment_id]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add status flags
    foreach ($submissions as &$submission) {
        if (!$submission['submission_id']) {
            $submission['display_status'] = 'Not submitted';
            $submission['status_class'] = 'missing';
        } elseif ($submission['submission_status'] === 'graded') {
            $submission['display_status'] = 'Graded';
            $submission['status_class'] = 'graded';
        } elseif ($submission['submission_status'] === 'submitted') {
            $submission['display_status'] = 'Submitted - Needs grading';
            $submission['status_class'] = 'submitted';
        } elseif ($submission['submission_status'] === 'late') {
            $submission['display_status'] = 'Late submission';
            $submission['status_class'] = 'late';
        }
    }
    
    echo json_encode([
        'success' => true,
        'assignment' => $assignment,
        'submissions' => $submissions
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
       