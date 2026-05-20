<?php
include "db.php";

$emailError = "";
$passwordError = "";
$success = "";

// 🔥 DEFAULT VALUES
$firstname = "";
$lastname = "";
$age = "";
$birthday = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $firstname = trim($_POST['fn']);
    $lastname = trim($_POST['ln']);
    $age = trim($_POST['age']);
    $birthday = $_POST['birthday'];
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // 🔥 PASSWORD RULES
    if (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[\W]/', $password)
    ) {

        $passwordError = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
    }

    // 🔥 CHECK PASSWORD MATCH
    elseif ($password !== $confirmPassword) {

        $passwordError = "Passwords do not match!";
    }

    if (empty($emailError) && empty($passwordError)) {

        try {

            $conn->beginTransaction();

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // 🔥 INSERT USER
            $sql = "
                INSERT INTO users (email, password, provider)
                VALUES (:email, :password, 'local')
                RETURNING id
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ':email' => $email,
                ':password' => $hashedPassword
            ]);

            $user_id = $stmt->fetchColumn();

            // 🔥 INSERT PROFILE
            $sql2 = "
                INSERT INTO user_profiles
                (user_id, firstname, lastname, age, birthday)
                VALUES
                (:user_id, :firstname, :lastname, :age, :birthday)
            ";

            $stmt2 = $conn->prepare($sql2);

            $stmt2->execute([
                ':user_id' => $user_id,
                ':firstname' => $firstname,
                ':lastname' => $lastname,
                ':age' => $age,
                ':birthday' => $birthday
            ]);

            $conn->commit();

            $success = "Account created successfully!";

            // 🔥 CLEAR INPUTS AFTER SUCCESS
            $firstname = "";
            $lastname = "";
            $age = "";
            $birthday = "";
            $email = "";

        } catch (PDOException $e) {

            $conn->rollBack();

            if (
                strpos(strtolower($e->getMessage()), 'duplicate') !== false ||
                strpos(strtolower($e->getMessage()), 'email') !== false
            ) {

                $emailError = "Email already exists!";

            } else {

                $emailError = "Database error occurred!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Sunvarge - Sign Up</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="styles/signup.css" />

</head>

<body>

<div class="bg-grid"></div>
<div class="bg-glow"></div>

<div class="site-logo">
    <img src="img/logo.png" class="logo-icon" alt="Sunvarge">
</div>

<div class="card">

    <h2 class="card-title">Create Account</h2>

    <?php if (!empty($success)) { ?>
        <div class="success-message">
            <?php echo $success; ?>
        </div>
    <?php } ?>

    <form action="signup.php" method="POST">

        <div class="two-col">

            <div class="form-group">

                <label>First Name</label>

                <div class="input-wrap">

                    <input
                        type="text"
                        name="fn"
                        class="input-field"
                        placeholder="First Name"
                        value="<?php echo htmlspecialchars($firstname); ?>"
                        required
                    >

                </div>

            </div>

            <div class="form-group">

                <label>Last Name</label>

                <div class="input-wrap">

                    <input
                        type="text"
                        name="ln"
                        class="input-field"
                        placeholder="Last Name"
                        value="<?php echo htmlspecialchars($lastname); ?>"
                        required
                    >

                </div>

            </div>

        </div>

        <div class="two-col">

            <div class="form-group">

                <label>Age</label>

                <div class="input-wrap">

                    <input
                        type="number"
                        name="age"
                        class="input-field"
                        placeholder="Age"
                        value="<?php echo htmlspecialchars($age); ?>"
                        required
                    >

                </div>

            </div>

            <div class="form-group">

                <label>Birthday</label>

                <div class="input-wrap">

                    <input
                        type="date"
                        name="birthday"
                        class="input-field"
                        value="<?php echo htmlspecialchars($birthday); ?>"
                        required
                    >

                </div>

            </div>

        </div>

        <div class="form-group">

            <label>Email</label>

            <div class="input-wrap">

                <input
                    type="email"
                    name="email"
                    class="input-field"
                    placeholder="Enter your Email"
                    value="<?php echo htmlspecialchars($email); ?>"
                    required
                >

            </div>

            <?php if (!empty($emailError)) { ?>
                <div class="error-message">
                    <?php echo $emailError; ?>
                </div>
            <?php } ?>

        </div>

        <div class="form-group">

            <label>Password</label>

            <div class="input-wrap">

                <input
                    type="password"
                    name="password"
                    id="password"
                    class="input-field"
                    placeholder="Enter your Password"
                    minlength="8"
                    required
                >

            </div>

            <div class="password-note">
                Must contain at least 8 characters, uppercase, lowercase, number, and special character.
            </div>

        </div>

        <div class="form-group">

            <label>Confirm Password</label>

            <div class="input-wrap">

                <input
                    type="password"
                    name="confirm_password"
                    id="confirm_password"
                    class="input-field"
                    placeholder="Confirm your Password"
                    minlength="8"
                    required
                >

            </div>

            <?php if (!empty($passwordError)) { ?>
                <div class="error-message">
                    <?php echo $passwordError; ?>
                </div>
            <?php } ?>

        </div>

        <button type="submit" class="btn-primary">
            SIGN UP
        </button>

    </form>

    <p class="switch-link">
        Already have an account?
        <a href="login.php">Login</a>
    </p>

</div>

</body>
</html>
