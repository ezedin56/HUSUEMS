<?php
require_once 'config/db.php';

try {
    echo "Seeding Database...\n";

    // 1. Create Default Admin (handled in SQL, but ensuring here)
    // Pass: admin123
    $stmt = $pdo->prepare("INSERT IGNORE INTO admin (username, password_hash) VALUES (?, ?)");
    $stmt->execute(['admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi']);
    echo "- Admin created (user: admin, pass: admin123)\n";

    // 2. Create Sample Election
    $stmt = $pdo->prepare("INSERT INTO elections (title, description, status) VALUES (?, ?, ?)");
    $stmt->execute(['Student Union President 2024', 'Vote for your next student union president.', 'active']);
    $election_id = $pdo->lastInsertId();
    echo "- Active Election created: 'Student Union President 2024' (ID: $election_id)\n";

    // 3. Add Candidates
    $candidates = [
        ['Sarah Connor', 'Resistance Party'],
        ['John Wick', 'Action Party'],
        ['Tony Stark', 'Tech Party']
    ];

    $stmt = $pdo->prepare("INSERT INTO candidates (election_id, full_name, details) VALUES (?, ?, ?)");
    foreach ($candidates as $c) {
        $stmt->execute([$election_id, $c[0], $c[1]]);
    }
    echo "- 3 Candidates added.\n";

    // 4. Add Sample Voters
    $voters = [
        ['S1001', 'Alice Johnson'],
        ['S1002', 'Bob Smith'],
        ['S1003', 'Charlie Brown'],
        ['S1004', 'David Wilson'],
        ['S1005', 'Eva Green']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO voters (student_id, full_name) VALUES (?, ?)");
    foreach ($voters as $v) {
        $stmt->execute([$v[0], $v[1]]);
    }
    echo "- 5 Sample Voters added (IDs: S1001-S1005).\n";

    echo "Seeding Compelte! You can now log in as admin or vote as a student.";

} catch (PDOException $e) {
    die("Seeding failed: " . $e->getMessage());
}
?>