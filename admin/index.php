<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | HUSUEMS</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* Local Override for Sidebar Active State */
        .nav-dashboard { background: rgba(255, 255, 255, 0.1); border-left-color: var(--accent); color: white; }
        
        /* Utils for Live Results */
        .d-flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .align-center { align-items: center; }
        .mb-3 { margin-bottom: 1rem; }
        .text-center { text-align: center; }
        .card { background: var(--white); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow-md); margin-top: 2rem; }
    </style>
</head>
<body>

<div class="admin-layout">
    
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            🗳️ Election Admin
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-dashboard">📊 Dashboard</a>
            <a href="elections.php">🗳️ Manage Elections</a>
            <a href="candidates.php">👥 Manage Candidates</a>
            <a href="voters.php">🎓 Manage Voters</a>
            <a href="../index.php" target="_blank">🌐 View Public Site</a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout" style="text-align: center; display: block;">Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-header">
            <h1>Dashboard Overview</h1>
            <span class="text-muted"><?php echo date('F j, Y'); ?></span>
        </div>

        <div class="alert alert-success">
            Welcome back, <strong>Administrator</strong>. Here is what's happening today.
        </div>

        <div class="dashboard-grid">
            <!-- Widget 1: Active Elections -->
            <div class="stat-box blue">
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM elections WHERE status = 'active'");
                $activeElections = $stmt->fetchColumn();
                ?>
                <span class="stat-number"><?php echo $activeElections; ?></span>
                <span class="stat-label">Active Elections</span>
            </div>

            <!-- Widget 2: Total Votes -->
            <div class="stat-box green">
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM votes");
                $totalVotes = $stmt->fetchColumn();
                ?>
                <span class="stat-number"><?php echo $totalVotes; ?></span>
                <span class="stat-label">Total Votes Cast</span>
            </div>

            <!-- Widget 3: Candidates -->
            <div class="stat-box orange">
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM candidates");
                $totalCandidates = $stmt->fetchColumn();
                ?>
                <span class="stat-number"><?php echo $totalCandidates; ?></span>
                <span class="stat-label">Total Candidates</span>
            </div>
        </div>

        <div class="card">
            <div class="d-flex justify-between align-center mb-3">
                <h2 style="margin:0;">🔴 Live Election Results</h2>
                <button onclick="window.location.reload();" class="btn-primary" style="width: auto; padding: 0.5rem 1rem; font-size: 0.9rem;">🔄 Refresh Data</button>
            </div>

            <?php
            // Fetch Active Elections with Vote Counts
            $electionsStmt = $pdo->query("SELECT * FROM elections WHERE status = 'active'");
            $activeElectionsDropdown = $electionsStmt->fetchAll();

            if (empty($activeElectionsDropdown)) {
                echo '<p class="text-muted text-center" style="padding: 2rem;">No active elections to display results for.</p>';
            } else {
                foreach ($activeElectionsDropdown as $elec) {
                    echo '<div style="margin-bottom: 2rem; border-bottom: 1px solid #eee; padding-bottom: 1.5rem;">';
                    echo '<h3 style="color: var(--primary);">' . htmlspecialchars($elec['title']) . '</h3>';
                    
                    // Get Candidates and Votes for this election, grouped by position
                    $positions = ['President', 'Vice President', 'Secretary'];
                    
                    foreach ($positions as $pos) {
                        $resStmt = $pdo->prepare("
                            SELECT c.full_name, COUNT(v.vote_id) as vote_count 
                            FROM candidates c 
                            LEFT JOIN votes v ON c.candidate_id = v.candidate_id 
                            WHERE c.election_id = ? AND c.position = ?
                            GROUP BY c.candidate_id 
                            ORDER BY vote_count DESC
                        ");
                        $resStmt->execute([$elec['election_id'], $pos]);
                        $results = $resStmt->fetchAll();

                        if (empty($results)) continue;

                        echo '<h4 style="margin: 1rem 0 0.5rem; color: var(--text-light); text-transform: uppercase; font-size: 0.85rem; border-bottom: 2px solid #eee; display: inline-block;">' . $pos . '</h4>';

                        // Calculate Total for Percentage per position
                        $totalPosVotes = 0;
                        foreach ($results as $r) $totalPosVotes += $r['vote_count'];

                        echo '<div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 0.5rem;">';
                        foreach ($results as $res) {
                            $percent = $totalPosVotes > 0 ? round(($res['vote_count'] / $totalPosVotes) * 100, 1) : 0;
                            echo '<div>';
                            echo '<div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">';
                            echo '<strong>' . htmlspecialchars($res['full_name']) . '</strong>';
                            echo '<span>' . $res['vote_count'] . ' votes (' . $percent . '%)</span>';
                            echo '</div>';
                            echo '<div style="background: #e5e7eb; border-radius: 10px; height: 10px; width: 100%; overflow: hidden;">';
                            echo '<div style="background: var(--success); width: ' . $percent . '%; height: 100%;"></div>';
                            echo '</div>';
                            echo '</div>';
                        }
                        echo '</div>'; // End candidates list for position
                    }
                    
                    echo '</div>'; // End election block
                }
            }
            ?>
        </div>
    </main>

</div>

</body>
</html>