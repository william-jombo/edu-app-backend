<?php
// backend/api/common/get_classes.php
// Fetch all active classes for student registration

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once '../../config/database.php';

try {
    $db = getDBConnection();
    
    // Get query parameters for filtering
    $status = $_GET['status'] ?? 'active';
    $academicYear = $_GET['academic_year'] ?? null;
    $gradeLevel = $_GET['grade_level'] ?? null;
    $classId = $_GET['class_id'] ?? null;
    
    // Build query
    $query = "SELECT 
                c.id,
                c.class_name,
                c.grade_level,
                c.academic_year,
                c.semester,
                c.capacity,
                c.room_number,
                c.class_teacher_id,
                c.status,
                c.created_at,
                c.updated_at,
                -- Count enrolled students
                COUNT(DISTINCT ce.student_id) as enrolled_students,
                -- Get class teacher info
                t.firstname as teacher_firstname,
                t.lastname as teacher_lastname,
                t.teacher_id as teacher_code
              FROM classes c
              LEFT JOIN class_enrollments ce ON c.id = ce.class_id AND ce.status = 'active'
              LEFT JOIN teachers t ON c.class_teacher_id = t.id
              WHERE 1=1";
    
    $params = [];
    
    // Apply filters
    if ($classId) {
        $query .= " AND c.id = :class_id";
        $params[':class_id'] = $classId;
    }
    
    if ($status !== 'all') {
        $query .= " AND c.status = :status";
        $params[':status'] = $status;
    }
    
    if ($academicYear) {
        $query .= " AND c.academic_year = :academic_year";
        $params[':academic_year'] = $academicYear;
    }
    
    if ($gradeLevel) {
        $query .= " AND c.grade_level = :grade_level";
        $params[':grade_level'] = $gradeLevel;
    }
    
    $query .= " GROUP BY c.id, c.class_name, c.grade_level, c.academic_year, c.semester, 
                c.capacity, c.room_number, c.class_teacher_id, c.status, c.created_at, 
                c.updated_at, t.firstname, t.lastname, t.teacher_id";
    $query .= " ORDER BY c.academic_year DESC, c.class_name ASC";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $formattedClasses = array_map(function($class) {
        return [
            'id' => (int)$class['id'],
            'class_name' => $class['class_name'],
            'grade_level' => $class['grade_level'],
            'academic_year' => $class['academic_year'],
            'semester' => $class['semester'],
            'capacity' => (int)$class['capacity'],
            'enrolled_students' => (int)$class['enrolled_students'],
            'available_slots' => (int)$class['capacity'] - (int)$class['enrolled_students'],
            'room_number' => $class['room_number'],
            'status' => $class['status'],
            'class_teacher' => $class['teacher_firstname'] ? [
                'id' => (int)$class['class_teacher_id'],
                'name' => $class['teacher_firstname'] . ' ' . $class['teacher_lastname'],
                'code' => $class['teacher_code']
            ] : null,
            'created_at' => $class['created_at'],
            'updated_at' => $class['updated_at']
        ];
    }, $classes);
    
    // Return with BOTH 'data' and 'classes' keys for compatibility
    echo json_encode([
        'success' => true,
        'message' => 'Classes retrieved successfully',
        'data' => $formattedClasses,      // For new components (report generator)
        'classes' => $formattedClasses,   // For old components (registration)
        'count' => count($formattedClasses)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching classes: ' . $e->getMessage(),
        'data' => [],
        'classes' => []
    ]);
}
?>