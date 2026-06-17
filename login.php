<?php
session_start();
require_once 'db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM tbl_users WHERE Username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['full_name'] = $user['FullName'];
            $_SESSION['role'] = $user['Role'];

            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NHC Reservation - Sign In</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background-color: #f8f9fa; 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .login-card { 
            width: 100%; 
            max-width: 450px; 
            border: none; 
            border-radius: 15px; 
            background: white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .brand-logo { max-width: 140px; }
        .divider { width: 50px; height: 3px; background: #0d6efd; margin: 15px auto; }
    </style>
</head>
<body>

<div class="card login-card p-4">
    <div class="card-body">
        <div class="text-center mb-4">
            <img src="./images/nhc-logo.png" alt="NHC Logo" class="brand-logo mb-3">
            <h4 class="fw-bold mb-0">New Hope Training Restaurant</h4>
            <br>
            <div class="text-muted fw-bold small">RESERVATION SYSTEM</div>
            <div class="divider"></div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label text-muted small fw-bold">USERNAME</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-user text-secondary"></i></span>
                    <input type="text" name="username" id="username" class="form-control" required autocomplete="username">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label text-muted small fw-bold">PASSWORD</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-lock text-secondary"></i></span>
                    <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">SIGN IN</button>
        </form>
    </div>
</div>

<footer class="text-center text-secondary mt-5">
    <small>&copy; <?= date('Y') ?> New Hope Cambodia</small>
</footer>

</body>
</html>