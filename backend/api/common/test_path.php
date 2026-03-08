<?php
// FILE: backend/api/common/test_path.php
// This will help diagnose the path issue

echo "<h2>Path Diagnostic Test</h2>";

// Show current file location
echo "<strong>Current File:</strong> " . __FILE__ . "<br>";
echo "<strong>Current Directory:</strong> " . __DIR__ . "<br>";

// Show what we're looking for
echo "<br><strong>Looking for database.php at:</strong><br>";
$db_path = __DIR__ . '/../config/database.php';
echo "Relative path: ../config/database.php<br>";
echo "Absolute path: " . realpath($db_path) . "<br>";

// Check if file exists
if (file_exists($db_path)) {
    echo "<span style='color: green;'>✅ database.php EXISTS at this location!</span><br>";
    
    // Try to include it
    try {
        require_once $db_path;
        echo "<span style='color: green;'>✅ database.php loaded successfully!</span><br>";
        
        // Try to create Database object
        if (class_exists('Database')) {
            echo "<span style='color: green;'>✅ Database class found!</span><br>";
            $database = new Database();
            $conn = $database->getConnection();
            if ($conn) {
                echo "<span style='color: green;'>✅ Database connection successful!</span><br>";
            } else {
                echo "<span style='color: red;'>❌ Database connection failed!</span><br>";
            }
        } else {
            echo "<span style='color: red;'>❌ Database class not found in file!</span><br>";
        }
    } catch (Exception $e) {
        echo "<span style='color: red;'>❌ Error loading database.php: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span style='color: red;'>❌ database.php NOT FOUND at this location!</span><br>";
    
    // Show directory contents to help debug
    echo "<br><strong>Contents of " . dirname($db_path) . ":</strong><br>";
    $parent_dir = dirname($db_path);
    if (is_dir($parent_dir)) {
        $files = scandir($parent_dir);
        echo "<ul>";
        foreach ($files as $file) {
            echo "<li>$file</li>";
        }
        echo "</ul>";
    } else {
        echo "Directory does not exist!<br>";
    }
    
    // Check one level up
    echo "<br><strong>Contents of backend folder:</strong><br>";
    $backend_dir = dirname(dirname(__DIR__));
    if (is_dir($backend_dir)) {
        $files = scandir($backend_dir);
        echo "<ul>";
        foreach ($files as $file) {
            echo "<li>$file</li>";
        }
        echo "</ul>";
    }
}
?>