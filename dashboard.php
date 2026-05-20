<?php
session_start();

include "db.php";

if (!isset($_SESSION['authenticated'])) {
    header("Location: login.php");
    exit();
}

$timeout = 30;

if (isset($_SESSION['LAST_ACTIVITY'])) {
    if (time() - $_SESSION['LAST_ACTIVITY'] > $timeout) {
        session_unset();
        session_destroy();
        header("Location: login.php?message=Session expired");
        exit();
    }
}

$_SESSION['LAST_ACTIVITY'] = time();

/* =========================
   TAB SYSTEM
========================= */
$tab = $_GET['tab'] ?? 'all';

$where = "";
if ($tab == "signup") {
    $where = "WHERE u.provider = 'local'";
} elseif ($tab == "google") {
    $where = "WHERE u.provider = 'google'";
}

$sql = "
    SELECT 
        u.id,
        u.email,
        u.password,
        u.provider,
        u.created_at,
        p.firstname,
        p.lastname,
        p.age,
        p.birthday,
        CONCAT(p.firstname, ' ', p.lastname) AS full_name
    FROM users u
    LEFT JOIN user_profiles p ON u.id = p.user_id
    $where
    ORDER BY u.id DESC
";

$stmt = $conn->query($sql);

/* =========================
   STATS
========================= */
$totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$signupUsers = $conn->query("SELECT COUNT(*) FROM users WHERE provider = 'local'")->fetchColumn();
$googleUsers = $conn->query("SELECT COUNT(*) FROM users WHERE provider = 'google'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Sunvarge Dashboard</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="styles/dashboard.css" />
</head>

<body>

<div class="bg-grid"></div>
<div class="bg-glow"></div>

<nav class="topbar">

    <div class="topbar-left">

        <div class="topbar-brand">
            Sun<span>varge</span>
        </div>

        <div class="tab-group">

            <a href="dashboard.php?tab=all"
            class="tab-btn <?= $tab == 'all' ? 'active' : '' ?>">
            All Users
            </a>

            <a href="dashboard.php?tab=signup"
            class="tab-btn <?= $tab == 'signup' ? 'active' : '' ?>">
            Sign-up Users
            </a>

            <a href="dashboard.php?tab=google"
            class="tab-btn <?= $tab == 'google' ? 'active' : '' ?>">
            Google Users
            </a>

        </div>
    </div>

    <a href="logout.php" class="btn-logout">
        Logout
    </a>

</nav>

<main class="main-content">

    <div class="page-header">
        <h1 class="page-title">User Dashboard</h1>
        <p class="page-subtitle">
            Manage and monitor all registered accounts
        </p>
    </div>

    <div class="stats-row">

        <div class="stat-card">
            <div class="stat-label">Total Users</div>
            <div class="stat-value teal">
                <?= $totalUsers ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Sign-up Users</div>
            <div class="stat-value">
                <?= $signupUsers ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Google Users</div>
            <div class="stat-value">
                <?= $googleUsers ?>
            </div>
        </div>

    </div>

    <div class="table-card">

        <div class="table-card-header">
            <div class="table-card-title">
                <?= ucfirst($tab) ?> Users
            </div>

            <input
                type="text"
                class="search-input"
                id="searchInput"
                placeholder="Search users..."
            >
        </div>

        <div class="table-wrapper">

            <table id="userTable">

                <thead>
                <tr>

                    <th>ID</th>
                    <th>Name</th>

                    <?php if ($tab != "google") { ?>
                        <th>Age</th>
                        <th>Birthday</th>
                    <?php } ?>

                    <th>Email</th>

                    <?php if ($tab != "google") { ?>
                        <th>Password</th>
                    <?php } ?>

                    <th>Type</th>
                    <th>Created At</th>
                    <th>Action</th>

                </tr>
                </thead>

                <tbody>

                <?php
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                ?>

                <tr>

                    <td class="id-cell">
                        #<?= $row['id'] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($row['full_name']) ?>
                    </td>

                    <?php if ($tab != "google") { ?>

                        <td>
                            <?= htmlspecialchars($row['age']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['birthday']) ?>
                        </td>

                    <?php } ?>

                    <td class="email-cell">
                        <?= htmlspecialchars($row['email']) ?>
                    </td>

                    <?php if ($tab != "google") { ?>

                        <td class="hash-cell"
                            title="<?= htmlspecialchars($row['password']) ?>">
                            <?= htmlspecialchars($row['password']) ?>
                        </td>

                    <?php } ?>

                    <td>
                        <span class="badge <?= $row['provider'] == 'google' ? 'badge-google' : 'badge-signup' ?>">

                            <?= $row['provider'] == 'google'
                                ? 'Google'
                                : 'Sign-up' ?>

                        </span>
                    </td>

                    <td class="date-cell">
                        <?= $row['created_at'] ?>
                    </td>

                    <td>

                        <a
                        class="btn-delete"
                        href="delete.php?id=<?= $row['id'] ?>"
                        onclick="return confirm('Delete this user?')">

                        Delete

                        </a>

                    </td>

                </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</main>

<!-- SESSION MODAL -->
<div id="idleModal">

    <div class="modal-box">

        <h3>Session Timeout</h3>

        <p>You’ve been idle.</p>

        <div id="countdownBox">10</div>

        <p>seconds remaining before logout</p>

        <button
        onclick="stayLoggedIn()"
        class="modal-btn stay-btn">
        Stay Logged In
        </button>

        <button
        onclick="logoutNow()"
        class="modal-btn logout-btn">
        Logout Now
        </button>

    </div>

</div>

<script>
/* SEARCH */
document.getElementById("searchInput").addEventListener("keyup", function () {

    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll("#userTable tbody tr");

    rows.forEach(row => {

        let text = row.innerText.toLowerCase();

        row.style.display = text.includes(filter)
            ? ""
            : "none";

    });

});

/* SESSION */
let idleTime = 0;
let logoutTime = 30;
let warningTime = 20;
let countdown = 10;
let countdownInterval;

document.onmousemove = resetTimer;
document.onkeypress = resetTimer;
document.onclick = resetTimer;
document.onscroll = resetTimer;
document.ontouchstart = resetTimer;

function resetTimer() {
    idleTime = 0;
}

setInterval(() => {

    idleTime++;

    if (idleTime === warningTime) {
        showModal();
    }

    if (idleTime >= logoutTime) {
        logoutNow();
    }

}, 1000);

function showModal() {

    document.getElementById("idleModal").style.display = "block";

    countdown = logoutTime - warningTime;

    updateCountdownUI();

    clearInterval(countdownInterval);

    countdownInterval = setInterval(() => {

        countdown--;

        updateCountdownUI();

        if (countdown <= 0) {
            logoutNow();
        }

    }, 1000);
}

function updateCountdownUI() {
    document.getElementById("countdownBox").innerText = countdown;
}

function stayLoggedIn() {

    document.getElementById("idleModal").style.display = "none";

    idleTime = 0;

    clearInterval(countdownInterval);

    fetch("keep_alive.php");
}

function logoutNow() {
    window.location.href = "logout.php";
}
</script>

</body>
</html>
