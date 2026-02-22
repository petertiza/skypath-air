<?php
header('Access-Control-Allow-Origin: *');
require 'db.php';

try {
    $db = Database::connect();
    echo "✅ DB OK<br>";
    
    $stmt = $db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='tizaPDT'");
    echo "<h3>Tabuľky v tizaPDT:</h3>";
    $found = false;
    while($row = $stmt->fetch()) {
        echo "- " . $row['TABLE_NAME'] . "<br>";
        $found = true;
    }
    if (!$found) echo "Žiadne tabuľky!";
    
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM users");
    echo "<br>Users: " . $stmt->fetch()['cnt'];
    
} catch(Exception $e) {
    echo "❌ " . $e->getMessage();
}
?>
