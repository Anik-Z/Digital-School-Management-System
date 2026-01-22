<?php
// config/db_connect.php
session_start();

function connectDB() {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'digital_school_management_system';
    
    $conn = mysqli_connect($host, $username, $password, $database);
    
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }
    
    return $conn;
}

// Check if table exists
function tableExists($conn, $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $result && mysqli_num_rows($result) > 0;
}

// Safe query execution
function executeQuery($conn, $sql) {
    // Check for table existence
    $tables = ['users', 'assessments', 'performance', 'goals', 'policies', 'notices', 'interventions', 'assessment_submissions', 'classes'];
    
    foreach ($tables as $table) {
        if (stripos($sql, $table) !== false) {
            if (!tableExists($conn, $table)) {
                error_log("Table $table doesn't exist for query: " . substr($sql, 0, 100));
                return false;
            }
            break;
        }
    }
    
    return mysqli_query($conn, $sql);
}

// Fetch all rows
function fetchAll($conn, $sql) {
    $result = executeQuery($conn, $sql);
    if (!$result) return [];
    
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

// Fetch single row
function fetchOne($conn, $sql) {
    $result = executeQuery($conn, $sql);
    if (!$result || mysqli_num_rows($result) == 0) {
        return null;
    }
    return mysqli_fetch_assoc($result);
}
?>