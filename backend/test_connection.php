<?php
// require_once 'config/Database.php';

// echo "<h2>Testing Supabase Connection...</h2>";

// try {
//     $database = new Database();
//     $conn = $database->getConnection();
    
//     // Test query
//     $query = "SELECT COUNT(*) as count FROM users";
//     $stmt = $conn->prepare($query);
//     $stmt->execute();
//     $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
//     echo "<p style='color: green;'>✅ Connected to Supabase successfully!</p>";
//     echo "<p>Users in database: " . $result['count'] . "</p>";
    
//     // Test another table
//     $query2 = "SELECT COUNT(*) as count FROM classes";
//     $stmt2 = $conn->prepare($query2);
//     $stmt2->execute();
//     $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    
//     echo "<p>Classes in database: " . $result2['count'] . "</p>";
    
// } catch (Exception $e) {
//     echo "<p style='color: red;'>❌ Connection failed!</p>";
//     echo "<p>Error: " . $e->getMessage() . "</p>";
// }
?>





<!-- C:\Users\BR\Desktop\calmtech\php\htdocs\edu-app-backend\backend\test_connection.php -->

<?php
require_once 'config/Database.php';

echo "<h2>📊 Checking All Tables in Supabase</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // List of all tables
    $tables = [
        'users', 'students', 'teachers', 'classes', 'subjects',
        'lessons', 'lesson_questions', 'lesson_answers', 'lesson_views',
        'assignments', 'assignment_submissions', 'attendance', 'grades',
        'class_enrollments', 'subject_enrollments', 'teacher_assignments',
        'fee_structures', 'payments'
    ];
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Table Name</th><th>Row Count</th><th>Status</th></tr>";
    
    foreach ($tables as $table) {
        try {
            $query = "SELECT COUNT(*) as count FROM $table";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>{$result['count']}</td>";
            echo "<td style='color: green;'>✅ OK</td>";
            echo "</tr>";
        } catch (Exception $e) {
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>-</td>";
            echo "<td style='color: red;'>❌ Error</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>