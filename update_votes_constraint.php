<?php
// update_votes_constraint.php
// This script updates the votes table to allow per-position voting

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'election_system';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Updating votes table constraint...\n";
    
    // Drop the old unique constraint
    $pdo->exec("ALTER TABLE votes DROP INDEX unique_vote");
    echo "✓ Removed old unique_vote constraint\n";
    
    // Add new unique constraint for election_id, student_id, and position
    $pdo->exec("ALTER TABLE votes ADD UNIQUE KEY unique_vote (election_id, student_id, position)");
    echo "✓ Added new unique_vote constraint (election_id, student_id, position)\n";
    
    echo "\n✅ Database updated successfully!\n";
    echo "Students can now vote for each position separately.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
