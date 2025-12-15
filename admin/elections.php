<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';
require_once '../includes/functions.php';

$message = '';

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $status = 'inactive';

    if ($title) {
        $stmt = $pdo->prepare("INSERT INTO elections (title, description, status) VALUES (?, ?, ?)");
        $stmt->execute([$title, $description, $status]);
        $message = "🎉 Election created successfully!";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM elections WHERE election_id = ?");
    $stmt->execute([$id]);
    header('Location: elections.php');
    exit;
}

// Handle Status Change
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $status = $_GET['status']; // active, inactive, closed
    $stmt = $pdo->prepare("UPDATE elections SET status = ? WHERE election_id = ?");
    $stmt->execute([$status, $id]);
    header('Location: elections.php');
    exit;
}

// Fetch All Elections
$stmt = $pdo->query("SELECT * FROM elections ORDER BY created_at DESC");
$elections = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Elections | HUSUEMS</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --dark: #2b2d42;
            --light: #f8f9fa;
            --gray: #adb5bd;
            --border: #e9ecef;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
        }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.05);
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            z-index: 10;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-nav {
            flex: 1;
            padding: 1.5rem 0;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.85rem 1.5rem;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            font-weight: 500;
        }
        
        .sidebar-nav a:hover {
            background: rgba(67, 97, 238, 0.08);
            color: var(--primary);
            border-left-color: var(--primary-light);
        }
        
        .sidebar-nav a.active {
            background: linear-gradient(90deg, rgba(67, 97, 238, 0.1) 0%, rgba(67, 97, 238, 0.05) 100%);
            color: var(--primary);
            border-left-color: var(--primary);
            font-weight: 600;
        }
        
        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--border);
        }
        
        .btn-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--danger) 0%, #ff6b9d 100%);
            color: white;
            padding: 0.75rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(247, 37, 133, 0.3);
        }
        
        /* Main Content */
        .admin-main {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }
        
        .admin-header {
            margin-bottom: 2rem;
        }
        
        .admin-header h1 {
            color: white;
            font-size: 2rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        /* Cards */
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .card h3 {
            color: var(--dark);
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Form */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        input[type="text"] {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.5rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            padding: 0.85rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.95rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #38b000 0%, #4cc9f0 100%);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #ff6b9d 100%);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%);
        }
        
        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        /* Table */
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        table thead {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        table th {
            padding: 1rem;
            text-align: left;
            color: var(--dark);
            font-weight: 600;
            border-bottom: 2px solid var(--border);
        }
        
        table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }
        
        table tr {
            transition: all 0.2s ease;
        }
        
        table tr:hover {
            background: rgba(67, 97, 238, 0.03);
        }
        
        table tr:last-child td {
            border-bottom: none;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
        }
        
        .status-active {
            background: linear-gradient(135deg, #28a745 0%, #38b000 100%);
        }
        
        .status-inactive {
            background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%);
        }
        
        .status-closed {
            background: linear-gradient(135deg, #dc3545 0%, var(--danger) 100%);
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .action-btn i {
            font-size: 0.9rem;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-layout {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
            }
            
            .sidebar-nav {
                display: flex;
                overflow-x: auto;
                padding: 1rem;
            }
            
            .sidebar-nav a {
                white-space: nowrap;
                border-left: none;
                border-bottom: 3px solid transparent;
            }
            
            .sidebar-nav a.active {
                border-left: none;
                border-bottom-color: var(--primary);
            }
        }
        
        @media (max-width: 768px) {
            .admin-main {
                padding: 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="admin-layout">
    
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-vote-yea"></i> Election Admin
        </div>
        <nav class="sidebar-nav">
            <a href="index.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="elections.php" class="active">
                <i class="fas fa-vote-yea"></i> Manage Elections
            </a>
            <a href="candidates.php">
                <i class="fas fa-users"></i> Manage Candidates
            </a>
            <a href="voters.php">
                <i class="fas fa-user-graduate"></i> Manage Voters
            </a>
            <a href="../index.php" target="_blank">
                <i class="fas fa-globe"></i> View Public Site
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-vote-yea"></i> Manage Elections</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3><i class="fas fa-plus-circle"></i> Create New Election</h3>
            <form action="" method="POST" class="form-grid">
                <input type="hidden" name="action" value="create">
                <div class="form-group input-icon">
                    <label for="title"><i class="fas fa-heading"></i> Election Title</label>
                    <i class="fas fa-pen"></i>
                    <input type="text" name="title" id="title" placeholder="Enter election title" required>
                </div>
                <div class="form-group input-icon">
                    <label for="description"><i class="fas fa-align-left"></i> Description (Optional)</label>
                    <i class="fas fa-file-alt"></i>
                    <input type="text" name="description" id="description" placeholder="Brief description">
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-plus"></i> Create Election
                </button>
            </form>
        </div>

        <div class="card">
            <h3><i class="fas fa-list"></i> Existing Elections</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>Election Details</th>
                            <th width="120">Status</th>
                            <th width="280">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($elections as $election): ?>
                            <tr>
                                <td><strong>#<?php echo $election['election_id']; ?></strong></td>
                                <td>
                                    <div style="font-weight: 600; color: var(--dark); font-size: 1.1rem; margin-bottom: 0.25rem;">
                                        <?php echo htmlspecialchars($election['title']); ?>
                                    </div>
                                    <?php if ($election['description']): ?>
                                        <div style="color: var(--gray); font-size: 0.9rem;">
                                            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($election['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = 'status-' . $election['status'];
                                    $statusIcon = $election['status'] == 'active' ? 'fa-play' : 
                                                 ($election['status'] == 'closed' ? 'fa-ban' : 'fa-pause');
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <i class="fas <?php echo $statusIcon; ?>"></i>
                                        <?php echo ucfirst($election['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($election['status'] !== 'active'): ?>
                                            <a href="?id=<?php echo $election['election_id']; ?>&status=active" 
                                               class="action-btn btn-primary" 
                                               title="Activate Election">
                                                <i class="fas fa-play"></i> Activate
                                            </a>
                                        <?php else: ?>
                                            <a href="?id=<?php echo $election['election_id']; ?>&status=closed" 
                                               class="action-btn btn-danger" 
                                               title="Close Election"
                                               onclick="return confirm('Close this election? Votes will be final.');">
                                                <i class="fas fa-ban"></i> Close
                                            </a>
                                        <?php endif; ?>

                                        <a href="candidates.php?election_id=<?php echo $election['election_id']; ?>" 
                                           class="action-btn btn-success"
                                           title="Manage Candidates">
                                            <i class="fas fa-users"></i> Candidates
                                        </a>
                                        
                                        <a href="?delete=<?php echo $election['election_id']; ?>" 
                                           class="action-btn btn-secondary"
                                           title="Delete Election"
                                           onclick="return confirm('Are you sure you want to delete this election? This cannot be undone.');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($elections)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 2rem; color: var(--gray);">
                                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                    No elections found. Create your first election above!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</div>

<script>
    // Add some interactive animations
    document.addEventListener('DOMContentLoaded', function() {
        // Animate table rows on load
        const rows = document.querySelectorAll('table tbody tr');
        rows.forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(10px)';
            setTimeout(() => {
                row.style.transition = 'all 0.4s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 50);
        });

        // Add hover effect to cards
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
                card.style.boxShadow = '0 15px 35px rgba(0, 0, 0, 0.15)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.1)';
            });
        });
    });
</script>

</body>
</html>