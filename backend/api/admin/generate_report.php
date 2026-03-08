<?php
/**
 * Student Progress Report Generator
 * Generates comprehensive PDF reports for individual students or entire classes
 * Handles both "2025" and "2024/2025" academic year formats
 */

header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

// Initialize response
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }

    $reportType = $data['report_type'] ?? ''; // 'single' or 'class'
    $studentId = $data['student_id'] ?? null;
    $classId = $data['class_id'] ?? null;
    $academicYear = $data['academic_year'] ?? '';
    $semester = $data['semester'] ?? '';
    $schoolName = $data['school_name'] ?? 'Educational Institution';
    
    if (!$academicYear) {
        throw new Exception('Academic year is required');
    }

    $database = new Database();
    $db = $database->getConnection();

    if ($reportType === 'single') {
        // Generate single student report
        if (!$studentId) {
            throw new Exception('Student ID is required for single report');
        }
        
        $reportData = generateSingleReport($db, $studentId, $academicYear, $semester, $schoolName);
        
        $response['success'] = true;
        $response['message'] = 'Report data generated successfully';
        $response['data'] = $reportData;
        
    } elseif ($reportType === 'class') {
        // Generate reports for entire class
        if (!$classId) {
            throw new Exception('Class ID is required for class report');
        }
        
        $reportData = generateClassReports($db, $classId, $academicYear, $semester, $schoolName);
        
        $response['success'] = true;
        $response['message'] = 'Class reports data generated successfully';
        $response['data'] = $reportData;
        
    } else {
        throw new Exception('Invalid report type. Use "single" or "class"');
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);

/**
 * Normalize academic year format
 * Converts "2025" to "2025" and "2024/2025" to "2025" for database queries
 */
function normalizeAcademicYear($academicYear) {
    // If format is "2024/2025", extract the second year
    if (strpos($academicYear, '/') !== false) {
        $parts = explode('/', $academicYear);
        return $parts[1]; // Return "2025"
    }
    return $academicYear; // Return as is "2025"
}

/**
 * Format academic year for display
 * Converts "2025" to "2024/2025" format
 */
function formatAcademicYearForDisplay($academicYear) {
    // If already in "2024/2025" format, return as is
    if (strpos($academicYear, '/') !== false) {
        return $academicYear;
    }
    // Convert "2025" to "2024/2025"
    $year = intval($academicYear);
    return ($year - 1) . '/' . $year;
}

/**
 * Generate report for a single student
 */
function generateSingleReport($db, $studentId, $academicYear, $semester, $schoolName) {
    // Normalize academic year for database queries
    $dbYear = normalizeAcademicYear($academicYear);
    
    // Get student information
    $studentQuery = "
        SELECT 
            s.id,
            s.student_number,
            s.firstname,
            s.lastname,
            s.profile_pic,
            s.date_of_birth,
            s.gender,
            s.guardian_name,
            s.guardian_phone,
            c.class_name,
            c.grade_level,
            c.academic_year,
            c.id as class_id
        FROM students s
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.id = :student_id AND s.status = 'active'
    ";
    
    $stmt = $db->prepare($studentQuery);
    $stmt->bindParam(':student_id', $studentId);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        throw new Exception('Student not found');
    }

    // Get total students in class for ranking
    $totalStudentsQuery = "
        SELECT COUNT(*) as total
        FROM students
        WHERE class_id = :class_id AND status = 'active'
    ";
    $stmt = $db->prepare($totalStudentsQuery);
    $stmt->bindParam(':class_id', $student['class_id']);
    $stmt->execute();
    $totalStudents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get grades with subject and teacher information
    // Try both exact match and LIKE match for academic year
    $gradesQuery = "
        SELECT 
            g.id,
            g.grade_type,
            g.score,
            g.max_score,
            g.percentage,
            g.weight,
            g.comments,
            g.grade_date,
            s.subject_name,
            s.subject_code,
            t.firstname as teacher_firstname,
            t.lastname as teacher_lastname,
            t.teacher_id as teacher_code
        FROM grades g
        INNER JOIN subjects s ON g.subject_id = s.id
        INNER JOIN teachers t ON g.teacher_id = t.id
        WHERE g.student_id = :student_id 
        AND (
            g.academic_year = :academic_year
            OR g.academic_year LIKE :academic_year_pattern
        )
        " . ($semester ? "AND g.semester = :semester" : "") . "
        ORDER BY s.subject_name, g.grade_type
    ";
    
    $stmt = $db->prepare($gradesQuery);
    $stmt->bindParam(':student_id', $studentId);
    $stmt->bindParam(':academic_year', $dbYear);
    $stmt->bindValue(':academic_year_pattern', '%' . $dbYear . '%');
    if ($semester) {
        $stmt->bindParam(':semester', $semester);
    }
    $stmt->execute();
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize grades by subject
    $subjectGrades = [];
    
    foreach ($grades as $grade) {
        $subjectName = $grade['subject_name'];
        
        if (!isset($subjectGrades[$subjectName])) {
            $subjectGrades[$subjectName] = [
                'subject_code' => $grade['subject_code'],
                'teacher' => $grade['teacher_firstname'] . ' ' . $grade['teacher_lastname'],
                'teacher_code' => $grade['teacher_code'],
                'grades' => [],
                'total_score' => 0,
                'total_max' => 0,
                'average' => 0
            ];
        }
        
        $subjectGrades[$subjectName]['grades'][] = [
            'type' => $grade['grade_type'],
            'score' => $grade['score'],
            'max_score' => $grade['max_score'],
            'percentage' => $grade['percentage'],
            'weight' => $grade['weight'],
            'comments' => $grade['comments'],
            'date' => $grade['grade_date']
        ];
        
        $subjectGrades[$subjectName]['total_score'] += $grade['score'];
        $subjectGrades[$subjectName]['total_max'] += $grade['max_score'];
    }

    // Calculate averages and overall performance
    $overallTotal = 0;
    $subjectCount = 0;
    
    foreach ($subjectGrades as $subject => &$data) {
        if ($data['total_max'] > 0) {
            $data['average'] = round(($data['total_score'] / $data['total_max']) * 100, 2);
            $overallTotal += $data['average'];
            $subjectCount++;
        }
    }
    
    $overallAverage = $subjectCount > 0 ? round($overallTotal / $subjectCount, 2) : 0;

    // Calculate class position
    $positionQuery = "
        SELECT 
            student_id,
            AVG(percentage) as avg_percentage
        FROM grades
        WHERE class_id = :class_id 
        AND (
            academic_year = :academic_year
            OR academic_year LIKE :academic_year_pattern
        )
        " . ($semester ? "AND semester = :semester" : "") . "
        GROUP BY student_id
        ORDER BY avg_percentage DESC
    ";
    
    $stmt = $db->prepare($positionQuery);
    $stmt->bindParam(':class_id', $student['class_id']);
    $stmt->bindParam(':academic_year', $dbYear);
    $stmt->bindValue(':academic_year_pattern', '%' . $dbYear . '%');
    if ($semester) {
        $stmt->bindParam(':semester', $semester);
    }
    $stmt->execute();
    $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $position = 1;
    foreach ($rankings as $rank) {
        if ($rank['student_id'] == $studentId) {
            break;
        }
        $position++;
    }

    // Get attendance summary - use the year from academic_year
    $attendanceQuery = "
        SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
            SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_days
        FROM attendance
        WHERE student_id = :student_id
        AND EXTRACT(YEAR FROM attendance_date) = :year
    ";
    
    $stmt = $db->prepare($attendanceQuery);
    $stmt->bindParam(':student_id', $studentId);
    $stmt->bindParam(':year', $dbYear);
    $stmt->execute();
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

    // Use the class's academic year if available, otherwise format the provided year
    $displayYear = $student['academic_year'] ?? formatAcademicYearForDisplay($dbYear);

    // Compile report data
    return [
        'school_name' => $schoolName,
        'academic_year' => $displayYear,
        'semester' => $semester,
        'generated_date' => date('Y-m-d'),
        'student' => $student,
        'position' => $position,
        'total_students' => $totalStudents,
        'overall_average' => $overallAverage,
        'subjects' => $subjectGrades,
        'attendance' => $attendance,
        'grade_interpretation' => getGradeInterpretation($overallAverage)
    ];
}

/**
 * Generate reports for entire class
 */
function generateClassReports($db, $classId, $academicYear, $semester, $schoolName) {
    // Get all active students in the class
    $studentsQuery = "
        SELECT id
        FROM students
        WHERE class_id = :class_id AND status = 'active'
        ORDER BY lastname, firstname
    ";
    
    $stmt = $db->prepare($studentsQuery);
    $stmt->bindParam(':class_id', $classId);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reports = [];
    foreach ($students as $student) {
        try {
            $reports[] = generateSingleReport($db, $student['id'], $academicYear, $semester, $schoolName);
        } catch (Exception $e) {
            // Log error but continue with other students
            error_log("Error generating report for student {$student['id']}: " . $e->getMessage());
        }
    }

    return [
        'class_id' => $classId,
        'total_reports' => count($reports),
        'reports' => $reports
    ];
}

/**
 * Get grade interpretation
 */
function getGradeInterpretation($average) {
    if ($average >= 90) return ['grade' => 'A+', 'remark' => 'Excellent'];
    if ($average >= 80) return ['grade' => 'A', 'remark' => 'Very Good'];
    if ($average >= 70) return ['grade' => 'B', 'remark' => 'Good'];
    if ($average >= 60) return ['grade' => 'C', 'remark' => 'Satisfactory'];
    if ($average >= 50) return ['grade' => 'D', 'remark' => 'Pass'];
    return ['grade' => 'F', 'remark' => 'Fail'];
}
?>