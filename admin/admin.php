<?php
include('../db.php');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// ✅ Access control
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// ✅ Handle new admin creation
if (isset($_POST['add_admin'])) {
    $username = $conn->real_escape_string($_POST['new_username']);
    $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO admins (username, password) VALUES ('$username', '$password')";
    if ($conn->query($sql) === TRUE) {
        $conn->query("INSERT INTO activity_log (action, actor) VALUES ('Added new admin: $username', '{$_SESSION['admin_username']}')");
        echo "<p style='color:lime;'>New admin added successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
    }
}

// ✅ Handle block/unblock users
if (isset($_GET['block_user'])) {
    $id = intval($_GET['block_user']);
    $conn->query("UPDATE users SET status='blocked' WHERE id=$id");
    $conn->query("INSERT INTO activity_log (action, actor) VALUES ('Blocked user ID: $id', '{$_SESSION['admin_username']}')");
    header("Location: admin.php");
    exit;
}
if (isset($_GET['unblock_user'])) {
    $id = intval($_GET['unblock_user']);
    $conn->query("UPDATE users SET status='active' WHERE id=$id");
    $conn->query("INSERT INTO activity_log (action, actor) VALUES ('Unblocked user ID: $id', '{$_SESSION['admin_username']}')");
    header("Location: admin.php");
    exit;
}

// ✅ Handle delete/resolve for contact messages
if (isset($_GET['delete_contact'])) {
    $id = intval($_GET['delete_contact']);
    $conn->query("DELETE FROM contact_messages WHERE id = $id");
    $conn->query("INSERT INTO activity_log (action, actor) VALUES ('Deleted contact message ID: $id', '{$_SESSION['admin_username']}')");
    header("Location: admin.php");
    exit;
}
if (isset($_GET['resolve_contact'])) {
    $id = intval($_GET['resolve_contact']);
    $conn->query("UPDATE contact_messages SET resolved = 1 WHERE id = $id");
    $conn->query("INSERT INTO activity_log (action, actor) VALUES ('Resolved contact message ID: $id', '{$_SESSION['admin_username']}')");
    header("Location: admin.php");
    exit;
}

// ✅ Handle delete/resolve for feedback messages
if (isset($_GET['delete_feedback'])) {
    $id = intval($_GET['delete_feedback']);
    $conn->query("DELETE FROM feedback_messages WHERE id = $id");
    $conn->query("INSERT INTO activity_log (action, actor) VALUES ('Deleted feedback message ID: $id', '{$_SESSION['admin_username']}')");
    header("Location: admin.php");
    exit;
}
if (isset($_GET['resolve_feedback'])) {
    $id = intval($_GET['resolve_feedback']);
    $conn->query("UPDATE feedback_messages SET resolved = 1 WHERE id = $id");
    $conn->query("INSERT INTO activity_log (action, actor) VALUES ('Resolved feedback message ID: $id', '{$_SESSION['admin_username']}')");
    header("Location: admin.php");
    exit;
}

// ✅ Queries for messages
$contactResult = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$feedbackResult = $conn->query("SELECT * FROM feedback_messages ORDER BY created_at DESC");

// ✅ Query for posts with user info
$postsResult = $conn->query("SELECT h.id AS post_id, h.title, h.price, h.location, h.name AS contact_name, h.phone, h.image, h.video, h.user_id, 
                                    u.id AS user_id, u.name AS user_name, u.email, u.status 
                             FROM houses h 
                             JOIN users u ON h.user_id = u.id 
                             ORDER BY h.id DESC");

// ✅ Dashboard summary stats
$totalContacts = $conn->query("SELECT COUNT(*) AS c FROM contact_messages")->fetch_assoc()['c'];
$resolvedContacts = $conn->query("SELECT COUNT(*) AS c FROM contact_messages WHERE resolved=1")->fetch_assoc()['c'];
$unresolvedContacts = $totalContacts - $resolvedContacts;

$totalFeedback = $conn->query("SELECT COUNT(*) AS c FROM feedback_messages")->fetch_assoc()['c'];
$resolvedFeedback = $conn->query("SELECT COUNT(*) AS c FROM feedback_messages WHERE resolved=1")->fetch_assoc()['c'];
$unresolvedFeedback = $totalFeedback - $resolvedFeedback;

$totalPosts = $conn->query("SELECT COUNT(*) AS c FROM houses")->fetch_assoc()['c'];

// ✅ Activity log query
$activityResult = $conn->query("SELECT * FROM activity_log ORDER BY timestamp DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - HouseHubKe</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background:#111; color:#fff; font-family:Arial, sans-serif; }
        h2 { color:#FFD700; margin-top:30px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background:#1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #333;
            text-align: left;
        }
        th { background:#FFD700; color:#000; }
        tr:nth-child(even) { background:#222; }
        tr:hover { background:#333; }
        .container { width:90%; margin:auto; }
        .logout { text-align:right; margin:20px; }
        .logout a {
            color:#FFD700; text-decoration:none; font-weight:bold;
            border:1px solid #FFD700; padding:6px 12px; border-radius:6px;
        }
        .logout a:hover { background:#FFD700; color:#000; }
        .delete-btn {
            color:red; font-weight:bold; text-decoration:none;
            border:1px solid red; padding:4px 8px; border-radius:4px;
        }
        .delete-btn:hover { background:red; color:#fff; }
        .resolve-btn {
            color:lime; font-weight:bold; text-decoration:none;
            border:1px solid lime; padding:4px 8px; border-radius:4px;
            margin-left:6px;
        }
        .resolve-btn:hover { background:lime; color:#000; }
        .block-btn {
            color:orange; font-weight:bold; text-decoration:none;
            border:1px solid orange; padding:4px 8px; border-radius:4px;
        }
        .block-btn:hover { background:orange; color:#000; }
        .unblock-btn {
            color:lime; font-weight:bold; text-decoration:none;
            border:1px solid lime; padding:4px 8px; border-radius:4px;
        }
        .unblock-btn:hover { background:lime; color:#000; }
        .summary {
            display:flex; justify-content:space-around; margin:30px 0;
            text-align:center;
        }
        .summary div {
            background:#1a1a1a; padding:20px; border-radius:8px;
            border:2px solid #FFD700; width:22%;
        }
        .summary h3 { color:#FFD700; margin-bottom:10px; }
        .add-admin {
            margin:20px 0; text-align:center;
        }
        .add-admin input {
            padding:8px; margin:5px; border:1px solid #FFD700; border-radius:6px;
            background:#111; color:#fff;
        }
        .add-admin button {
            padding:8px 12px; border:none; border-radius:6px;
            background:#FFD700; color:#000; font-weight:bold; cursor:pointer;
        }
        .add-admin button:hover { background:#e6c200; }
        .thumbnail {
            max-width:100px; max-height:80px; border:1px solid #FFD700; border-radius:4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="color:#FFD700; text-align:center;">Admin Dashboard</h1>
        
        <!-- Logout link -->
        <div class="logout">
            <a href="admin_logout.php">Logout</a>
        </div>

        <!-- Add Admin Form -->
        <div class="add-admin">
            <h2>Add New Admin</h2>
            <form method="post" action="admin.php">
                <input type="text" name="new_username" placeholder="Username" required>
                <input type="password" name="new_password" placeholder="Password" required>
                <button type="submit" name="add_admin">Add Admin</button>
            </form>
        </div>

        <!-- Summary stats -->
        <div class="summary">
            <div>
                <h3>Contact Messages</h3>
                <p>Total: <?= $totalContacts ?></p>
                <p>Resolved: <?= $resolvedContacts ?></p>
                <p>Unresolved: <?= $unresolvedContacts ?></p>
            </div>
            <div>
                <h3>Feedback Messages</h3>
                <p>Total: <?= $totalFeedback ?></p>
                <p>Resolved: <?= $resolvedFeedback ?></p>
                <p>Unresolved: <?= $unresolvedFeedback ?></p>
            </div>
            <div>
                <h3>User Posts</h3>
                <p>Total: <?= $totalPosts ?></p>
            </div>
        </div>

        <!-- Contact Messages -->
        <h2>Contact Messages</h2>
        <table>
            <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Message</th><th>Date</th><th>Action</th>
            </tr>
            <?php while($row = $contactResult->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                <td><?= $row['created_at'] ?></td>
                <td>
                    <a class="delete-btn" href="admin.php?delete_contact=<?= $row['id'] ?>" onclick="return confirm('Delete this contact message?');">Delete</a>
                    <?php if ($row['resolved'] == 0): ?>
                        <a class="resolve-btn" href="admin.php?resolve_contact=<?= $row['id'] ?>" onclick="return confirm('Mark this message as resolved?');">Resolve</a>
                    <?php else: ?>
                        <span style="color:lime; font-weight:bold;">Resolved</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <!-- Feedback Messages -->
        <h2>Feedback Messages</h2>
        <table>
            <tr>
                <th>ID</th><th>Email</th><th>Message</th><th>Date</th><th>Action</th>
            </tr>
            <?php while($row = $feedbackResult->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                <td><?= $row['created_at'] ?></td>
                <td>
                    <a class="delete-btn" href="admin.php?delete_feedback=<?= $row['id'] ?>" onclick="return confirm('Delete this feedback message?');">Delete</a>
                    <?php if ($row['resolved'] == 0): ?>
                        <a class="resolve-btn" href="admin.php?resolve_feedback=<?= $row['id'] ?>" onclick="return confirm('Mark this feedback as resolved?');">Resolve</a>
                    <?php else: ?>
                        <span style="color:lime; font-weight:bold;">Resolved</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <!-- User Posts -->
        <h2>User Posts</h2>
        <table>
            <tr>
                <th>Post ID</th><th>Title</th><th>Price</th><th>Location</th>
                <th>Contact Name</th><th>Phone</th><th>Image</th><th>User</th><th>Email</th>
                <th>Status</th><th>Action</th>
            </tr>
            <?php while($row = $postsResult->fetch_assoc()): ?>
            <tr>
                <td><?= $row['post_id'] ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['price']) ?></td>
                <td><?= htmlspecialchars($row['location']) ?></td>
                <td><?= htmlspecialchars($row['contact_name']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td>
                    <?php if (!empty($row['image'])): ?>
                        <img src="<?= htmlspecialchars($row['image']) ?>" class="thumbnail" alt="House Image">
                    <?php else: ?>
                        <span style="color:#888;">No image</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['user_name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= $row['status'] ?></td>
                <td>
                    <?php if ($row['status'] === 'active'): ?>
                        <a class="block-btn" href="admin.php?block_user=<?= $row['user_id'] ?>" onclick="return confirm('Block this user?');">Block</a>
                    <?php else: ?>
                        <a class="unblock-btn" href="admin.php?unblock_user=<?= $row['user_id'] ?>" onclick="return confirm('Unblock this user?');">Unblock</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <!-- Activity Log -->
        <h2>Recent Admin Activity</h2>
        <table>
            <tr>
                <th>ID</th><th>Action</th><th>Actor</th><th>Timestamp</th>
            </tr>
            <?php while($row = $activityResult->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['action']) ?></td>
                <td><?= htmlspecialchars($row['actor']) ?></td>
                <td><?= $row['timestamp'] ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
