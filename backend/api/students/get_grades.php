




<?php
// // backend/api/students/get_grades.php
// // Updated for NEW database structure

// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');
// require_once '../../config/database.php';

// $student_id = $_GET['student_id'] ?? null;

// if (!$student_id) {
//     echo json_encode(['success' => false, 'message' => 'Student ID required']);
//     exit;
// }

// try {
//     $db = new Database();
//     $conn = $db->getConnection();
    
//     // Get grades from grades table (NEW)
//     $query = "SELECT 
//                 g.id,
//                 g.score,
//                 g.max_score,
//                 g.percentage,
//                 g.grade_type,
//                 g.comments,
//                 g.grade_date,
//                 s.subject_name,
//                 s.subject_code,
//                 g.subject_id
//               FROM grades g
//               JOIN subjects s ON g.subject_id = s.id
//               WHERE g.student_id = ?
//               ORDER BY g.grade_date DESC";
    
//     $stmt = $conn->prepare($query);
//     $stmt->execute([$student_id]);
//     $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     echo json_encode([
//         'success' => true,
//         'grades' => $grades
//     ]);
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Error: ' . $e->getMessage()
//     ]);
// }
?>






<?php
// backend/api/students/get_grades.php
// Shows BOTH assignment grades AND course grades (exams, quizzes, etc.)

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once '../../config/database.php';

$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Student ID required']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // PART 1: Get assignment grades (from assignment_submissions table)
    $assignmentQuery = "SELECT 
                asub.id,
                asub.grade as score,
                a.total_points as max_score,
                ROUND((asub.grade / a.total_points) * 100, 2) as percentage,
                'assignment' as grade_type,
                asub.feedback as comments,
                DATE(asub.graded_at) as grade_date,
                s.subject_name,
                s.subject_code,
                s.id as subject_id,
                a.title as assignment_title,
                asub.status
              FROM assignment_submissions asub
              JOIN assignments a ON asub.assignment_id = a.id
              JOIN subjects s ON a.subject_id = s.id
              WHERE asub.student_id = ?
              AND asub.status = 'graded'
              AND asub.grade IS NOT NULL
              ORDER BY asub.graded_at DESC";
    
    $stmt = $conn->prepare($assignmentQuery);
    $stmt->execute([$student_id]);
    $assignmentGrades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // PART 2: Get course grades (from grades table - exams, quizzes, etc.)
    $courseQuery = "SELECT 
                g.id,
                g.score,
                g.max_score,
                g.percentage,
                g.grade_type,
                g.comments,
                g.grade_date,
                s.subject_name,
                s.subject_code,
                g.subject_id,
                NULL as assignment_title,
                'graded' as status
              FROM grades g
              JOIN subjects s ON g.subject_id = s.id
              WHERE g.student_id = ?
              ORDER BY g.grade_date DESC";
    
    $stmt = $conn->prepare($courseQuery);
    $stmt->execute([$student_id]);
    $courseGrades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // PART 3: Combine both types of grades
    $allGrades = array_merge($assignmentGrades, $courseGrades);
    
    // Sort by date (most recent first)
    usort($allGrades, function($a, $b) {
        return strtotime($b['grade_date']) - strtotime($a['grade_date']);
    });
    
    // Add a display label for each grade
    foreach ($allGrades as &$grade) {
        if ($grade['assignment_title']) {
            $grade['title'] = $grade['assignment_title'] . ' (Assignment)';
        } else {
            $grade['title'] = ucfirst($grade['grade_type']);
        }
    }
    
    echo json_encode([
        'success' => true,
        'grades' => $allGrades,
        'summary' => [
            'total_grades' => count($allGrades),
            'assignment_grades' => count($assignmentGrades),
            'course_grades' => count($courseGrades)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>