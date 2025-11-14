 <?php
    $host = 'sql303.iceiy.com';
    $db = 'icei_40412833_serverside'; // Your database name
    $user = 'icei_40412833'; // Your MySQL username (default for XAMPP)
    $pass = 'AlphaArrow'; // Your MySQL password (default for XAMPP is empty)
    $charset = 'utf8';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }

 ?>