<?php
require_once 'includes/config.php';

try {
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/database/create_tables.sql');
    
    // Split the SQL into individual queries
    $queries = explode(';', $sql);
    
    // Execute each query
    $pdo->beginTransaction();
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
    
    $pdo->commit();
    
    echo "Tables created successfully!<br>";
    echo "<a href='index.php'>Go to Dashboard</a>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Error creating tables: " . $e->getMessage());
}
?>
