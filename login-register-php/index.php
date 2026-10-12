
<?php
session_start();

$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/users.json';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0750, true);
}

if (!file_exists($dataFile)) {
    file_put_contents($dataFile, '[]', LOCK_EX);
}

$errors = [];
$success = '';
$activeForm = 'login';

function loadUsers($file) {
    $json = file_get_contents($file);
    $users = json_decode($json, true);

    return is_array($users) ? $users : [];
}

function escapeHtml($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $activeForm = $action === 'register' ? 'register' : 'login';

    if ($action === 'register') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($name === '') {
            $errors[] = 'Please enter your full name.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        $users = loadUsers($dataFile);

        foreach ($users as $user) {
            if (strtolower($user['email']) === $email) {
                $errors[] = 'This email is already registered.';
                break;
            }
        }

        if (empty($errors)) {
            $users[] = [
                'id' => bin2hex(random_bytes(8)),
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ];

            $json = json_encode(
                $users,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            );

            $saved = file_put_contents(
                $dataFile,
                $json,
                LOCK_EX
            );

            if ($saved === false) {
                $errors[] = 'Could not save your account. Check folder permissions.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'name' => $name,
                    'email' => $email
                ];

                header('Location: index.php');
                exit;
            }
        }
    } elseif ($action === 'login') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        $users = loadUsers($dataFile);
        $matchedUser = null;

        foreach ($users as $user) {
            if (
                strtolower($user['email']) === $email &&
                password_verify($password, $user['password'])
            ) {
                $matchedUser = $user;
                break;
            }
        }

        if ($matchedUser) {
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'name' => $matchedUser['name'],
                'email' => $matchedUser['email']
            ];

            header('Location: index.php');
            exit;
        }

        $errors[] = 'Incorrect email or password.';
    } else {
        $errors[] = 'Invalid request.';
    }
}

$isLoggedIn = isset($_SESSION['user']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Login & Register</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="navbar">
    <a class="logo" href="index.php">
        <span class="log">✦</span> in
    </a>
    <span class="nav-text">Learn together. Grow together.</span>
</header>

<main class="page-container">

<?php if ($isLoggedIn): ?>

    <section class="welcome-card">
        <div class="bee-icon">🐝</div>
        <p class="eyebrow">MASTERBEES COMMUNITY</p>
        <h1>Welcome, <?= escapeHtml($_SESSION['user']['name']) ?>!</h1>
        <p>You are now signed in to MasterBees.</p>
        <p class="email-text">
            <?= escapeHtml($_SESSION['user']['email']) ?>
        </p>
        <a class="primary-btn" href="index.php?logout=1">
            Log Out
        </a>
    </section>

<?php else: ?>

    <section class="auth-card">
        <div class="auth-intro">
            <div class="bee-icon">🐝</div>
            <p class="eyebrow">YOUR SKILLS. YOUR COMMUNITY.</p>
            <h1>Learn something new.</h1>
            <p>
                Share what you know and discover what other students
                can teach you.
            </p>
        </div>

        <div class="form-area">
            <div class="tabs">
                <button type="button"
                        class="tab <?= $activeForm === 'login' ? 'active' : '' ?>"
                        data-form="login">
                    Login
                </button>

                <button type="button"
                        class="tab <?= $activeForm === 'register' ? 'active' : '' ?>"
                        data-form="register">
                    Register
                </button>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert error" role="alert">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= escapeHtml($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="loginForm"
                  method="POST"
                  action="index.php"
                  class="auth-form <?= $activeForm === 'login' ? 'show' : '' ?>">

                <input type="hidden" name="action" value="login">

                <h2>Welcome back!</h2>
                <p class="form-description">Log in to continue your journey.</p>

                <label for="loginEmail">Email Address</label>
                <input id="loginEmail"
                       type="email"
                       name="email"
                       placeholder="you@example.com"
                       autocomplete="email"
                       required>

                <label for="loginPassword">Password</label>
                <input id="loginPassword"
                       type="password"
                       name="password"
                       placeholder="Enter your password"
                       autocomplete="current-password"
                       required>

                <button class="primary-btn" type="submit">Login</button>

                <p class="switch-text">
                    Don't have an account?
                    <button type="button" class="text-btn" data-form="register">
                        Register
                    </button>
                </p>
            </form>

            <form id="registerForm"
                  method="POST"
                  action="index.php"
                  class="auth-form <?= $activeForm === 'register' ? 'show' : '' ?>">

                <input type="hidden" name="action" value="register">

                <h2>Create an account</h2>
                <p class="form-description">
                    Join students who love sharing knowledge.
                </p>

                <label for="registerName">Full Name</label>
                <input id="registerName"
                       type="text"
                       name="name"
                       placeholder="Enter your full name"
                       autocomplete="name"
                       maxlength="100"
                       required>

                <label for="registerEmail">Email Address</label>
                <input id="registerEmail"
                       type="email"
                       name="email"
                       placeholder="you@example.com"
                       autocomplete="email"
                       required>

                <label for="registerPassword">Password</label>
                <input id="registerPassword"
                       type="password"
                       name="password"
                       placeholder="At least 8 characters"
                       autocomplete="new-password"
                       minlength="8"
                       required>

                <label for="confirmPassword">Confirm Password</label>
                <input id="confirmPassword"
                       type="password"
                       name="confirm_password"
                       placeholder="Enter your password again"
                       autocomplete="new-password"
                       minlength="8"
                       required>

                <button class="primary-btn" type="submit">
                    Create Account
                </button>

                <p class="switch-text">
                    Already have an account?
                    <button type="button" class="text-btn" data-form="login">
                        Login
                    </button>
                </p>
            </form>
        </div>
    </section>

<?php endif; ?>

</main>

<footer class="footer">
    <p>© <?= date('Y') ?> MasterBees. Share skills. Build connections.</p>
</footer>

<script>
const tabs = document.querySelectorAll('[data-form]');
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');

function showForm(formName) {
    if (!loginForm || !registerForm) return;

    loginForm.classList.toggle('show', formName === 'login');
    registerForm.classList.toggle('show', formName === 'register');

    document.querySelectorAll('.tab').forEach((tab) => {
        tab.classList.toggle(
            'active',
            tab.dataset.form === formName
        );
    });
}

tabs.forEach((button) => {
    button.addEventListener('click', () => {
        showForm(button.dataset.form);
    });
});
</script>

</body>
</html>
