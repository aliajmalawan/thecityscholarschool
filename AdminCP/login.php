<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

header('Cache-Control: no-store, private');

// Brute-force protection: after 5 wrong passwords from one IP address within 15 minutes,
// that address must wait 15 minutes. Attempts are kept in the database (a new browser
// session doesn't reset them). The table is created on first use.
const LOGIN_MAX_FAILURES = 5;
const LOGIN_WINDOW_MINUTES = 15;

$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
$attempts_table_ok = true;
try {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admin_login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        username VARCHAR(50) NOT NULL DEFAULT '',
        attempted_at DATETIME NOT NULL,
        KEY ip_time (ip, attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (mysqli_sql_exception $e) {
    $attempts_table_ok = false;   // no CREATE permission: fall back to a per-session limit
}

$recent_failures = function () use ($conn, $client_ip, $attempts_table_ok) {
    if (!$attempts_table_ok) {
        return (int) ($_SESSION['login_failures'] ?? 0);
    }
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM admin_login_attempts WHERE ip = ? AND attempted_at > NOW() - INTERVAL " . LOGIN_WINDOW_MINUTES . " MINUTE");
    mysqli_stmt_bind_param($stmt, 's', $client_ip);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return (int) $count;
};

$record_failure = function ($username) use ($conn, $client_ip, $attempts_table_ok) {
    $_SESSION['login_failures'] = (int) ($_SESSION['login_failures'] ?? 0) + 1;
    if ($attempts_table_ok) {
        $stmt = mysqli_prepare($conn, "INSERT INTO admin_login_attempts (ip, username, attempted_at) VALUES (?, ?, NOW())");
        $username = mb_substr($username, 0, 50);
        mysqli_stmt_bind_param($stmt, 'ss', $client_ip, $username);
        mysqli_stmt_execute($stmt);
        // Old rows are of no use; keep the table small
        mysqli_query($conn, "DELETE FROM admin_login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY");
    }
};

if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

$error_message = '';
$info_message = isset($_GET['expired']) ? 'You were logged out after 2 hours without activity. Please log in again.' : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!hash_equals($_SESSION['login_csrf'], (string) ($_POST['login_csrf'] ?? ''))) {
        $error_message = 'The login form expired. Please try again.';
    } elseif ($recent_failures() >= LOGIN_MAX_FAILURES) {
        $error_message = 'Too many wrong attempts. Please wait ' . LOGIN_WINDOW_MINUTES . ' minutes and try again.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM admin_users WHERE username = ? AND status = 'active' LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($admin && password_verify($password, $admin['password'])) {
            // New session id at login, so an id planted before login is worthless (session fixation)
            session_regenerate_id(true);
            unset($_SESSION['login_failures'], $_SESSION['login_csrf']);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_last_seen'] = time();

            // Upgrade the stored hash when PHP's recommended algorithm/cost changes
            if (password_needs_rehash($admin['password'], PASSWORD_DEFAULT)) {
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $up = mysqli_prepare($conn, "UPDATE admin_users SET password = ? WHERE id = ?");
                mysqli_stmt_bind_param($up, 'si', $new_hash, $admin['id']);
                mysqli_stmt_execute($up);
            }
            mysqli_query($conn, "UPDATE admin_users SET last_login = NOW() WHERE id = " . intval($admin['id']));

            header('Location: dashboard.php');
            exit;
        }

        // Same message whether the username or the password was wrong
        $record_failure($username);
        $error_message = 'Invalid username or password';
        usleep(400000);   // slows down automated guessing
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Admin Login - Tameer-e-Nau Public School</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="../<?php echo htmlspecialchars(getLogoPath()); ?>">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, #5F6D3D 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            padding: 50px 40px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h1 {
            color: var(--primary-color);
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: var(--text-light);
        }

        .login-logo {
            width: 96px;
            height: 96px;
            object-fit: contain;
            display: block;
            margin: 0 auto 20px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), #5F6D3D);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            font-weight: 600;
            margin-top: 10px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: var(--primary-color);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../<?php echo htmlspecialchars(getLogoSmallPath()); ?>" alt="Tameer-e-Nau Public School Logo" class="login-logo">
            <h1>Admin Login</h1>
            <p><?php echo htmlspecialchars(getSiteName()); ?><br><small><?php echo htmlspecialchars(trim((string) strtok(getSiteAddress(), "\n"))); ?></small></p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($info_message): ?>
            <div class="alert alert-success"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($info_message); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="on">
            <input type="hidden" name="login_csrf" value="<?php echo htmlspecialchars($_SESSION['login_csrf']); ?>">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" required autofocus autocomplete="username" maxlength="50" placeholder="Enter your username">
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt"></i> Login to Dashboard
            </button>
        </form>

        <div class="back-link">
            <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Website</a>
        </div>

    </div>
</body>
</html>
