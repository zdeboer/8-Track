<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Admin Page</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/header.css">
    <link rel="stylesheet" href="styles/buttons.css">
    <link rel="stylesheet" href="styles/lists.css">
    <link rel="stylesheet" href="styles/forms.css">
    <link rel="icon" type="image/x-icon" href="images/8.svg.svg">
</head>
<body>
    <header>
        <div class="header-user-info">
            <h2>User Admin Page</h2>
            <p>Logged in as: <strong><?=$_SESSION['role']?></strong></p>
        </div>
        <div class="header-nav">
            <a class="button" href="dashboard.php">Back</a>
            <a class="button" href="logout.php">Logout</a>  
        </div>
    </header>
    <form class="edit-form" action="process_registration.php" method="post">
        <label for="username">Username</label>
        <input class="text-input" id="username" name="username" required>

        <label for="email">Email</label>
        <input class="text-input" id="email" name="email" required>

        <label for="role">Role</label>
        <select name="role" id="role">
          <option value="listener">Listener</option>
          <option value="admin">Admin</option>
        </select>

        <br><br>

        <label for="password">Password:</label>
        <input class="text-input" type="password" id="password" name="password" required>

        <label for="retype-password">Retype Password:</label>
        <input class="text-input" type="password" id="retype-password" name="retype-password" required>
        
        <input class="button" type="submit" name="submit" value="Add User">
    </form>
</body>
</html>