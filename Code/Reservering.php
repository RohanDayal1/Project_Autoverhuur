<?php
$host = 'localhost';
$db   = 'autoverhuur';
$user = 'root';
$pass = "";

$dsn = "mysql:host=$host;dbname=$db;";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Function to map status to display values
function getStatusDisplay($status) {
    $statusInfo = [
        'Pending' => ['class' => 'badge-pending', 'text' => 'In afwachting'],
        'Confirmed' => ['class' => 'badge-confirmed', 'text' => 'Bevestigd'],
        'Cancelled' => ['class' => 'badge-cancelled', 'text' => 'Geannuleerd'],
        'Completed' => ['class' => 'badge-completed', 'text' => 'Voltooid']
    ];

    return $statusInfo[$status] ?? ['class' => 'badge-unknown', 'text' => 'Onbekend'];
}

// Function to insert an image into the database, if it doesn't already exist
function insertImage($pdo, $imagePath) {
    if (!file_exists($imagePath)) {
        throw new Exception("Bestand niet gevonden: $imagePath");
    }

    $imageData = file_get_contents($imagePath);
    $imageName = basename($imagePath);

    // Check if the image already exists in the database by its name or data
    $stmt = $pdo->prepare("SELECT id FROM images WHERE name = :name OR data = :data LIMIT 1");
    $stmt->bindParam(':name', $imageName);
    $stmt->bindParam(':data', $imageData, PDO::PARAM_LOB);
    $stmt->execute();
    $existingImage = $stmt->fetch();



    // Insert the image into the database if it doesn't exist
    $stmt = $pdo->prepare("INSERT INTO images (name, data) VALUES (:name, :data)");
    $stmt->bindParam(':name', $imageName);
    $stmt->bindParam(':data', $imageData, PDO::PARAM_LOB);

  
}

// Function to retrieve an image by ID
function getImage($pdo, $id) {
    $stmt = $pdo->prepare("SELECT data FROM images WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);  // Fetch as associative array to access 'data'
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Insert image (this can be moved to a separate script for upload functionality)
    $imagePath = $_SERVER["DOCUMENT_ROOT"] . "/Project_Autoverhuur/Img/Dodge Demon 170.jpg";
    insertImage($pdo, $imagePath);

    // Query for reservations and images
    $stmt = $pdo->prepare("
        SELECT 
            CONCAT(u.first_name, ' ', u.last_name) as klantnaam,
            c.make as automerk,
            c.model as automodel,
            i.id as image_id,  -- Get the image ID for retrieval
            r.start_date as startdatum,
            r.end_date as einddatum,
            r.total_price as totaalprijs,
            rs.status_name as status
        FROM rentals r
        JOIN users u ON r.user_id = u.user_id
        JOIN cars c ON r.car_id = c.car_id
        JOIN rental_statuses rs ON r.status_id = rs.status_id
        LEFT JOIN images i ON i.id = c.images_id  -- Assuming images are linked to cars
        ORDER BY u.last_name, r.start_date
    ");

    $stmt->execute();

} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Reserveringen</title>
</head>
<body class="bg-gray-100">
<nav class="nav-bar">
        <div class="container">
            <h1>????</h1>
            <div class="nav-links">
                <a href="#" class="nav-link">Huurauto's</a>
                <a href="Adminweergave.php" class="nav-link">Admin</a>
                <a href="Test.php" class="nav-link">Contact</a>
                <a href="Reservering.php" class="nav-link">Mijn boekingen</a>
                <a href="Adminreserveringweergave.php" class="nav-link">res_Weergaven</a>
                <a href="Login.php" class="nav-link login">Login</a>
                <a href="register.php" class="nav-link register">Register</a>
            </div>
        </div>
    </nav>

    <main class="main-container">
        <div class="content-container">
            <h2>Reserveringsoverzicht</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Klantnaam</th>
                            <th>Automerk</th>
                            <th>Model</th>
                            <th>Afbeelding</th>  
                            <th>Startdatum</th>
                            <th>Einddatum</th>
                            <th>Totaalprijs</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php
    while ($reservation = $stmt->fetch()) {
    $statusInfo = getStatusDisplay($reservation['status']);

    if ($reservation['automerk'] == 'Dodge') {
        $imageSrc = '/Project_Autoverhuur/Img/Dodge Demon 170.jpg'; 
    } elseif ($reservation['automerk'] == 'Mercedes') {
        $imageSrc = '/Project_Autoverhuur/Img/merrie.jpg'; 
    } elseif ($reservation['automerk'] == 'BMW') {
        $imageSrc = '/Project_Autoverhuur/Img/bmw.jpg'; 
    } else {
        $imageSrc = '/Project_Autoverhuur/Img/placeholder.jpg'; 
    }

    // Output reservation details along with the image
    echo "<tr>";
    echo "<td>" . htmlspecialchars($reservation['klantnaam']) . "</td>";
    echo "<td>" . htmlspecialchars($reservation['automerk']) . "</td>";
    echo "<td>" . htmlspecialchars($reservation['automodel']) . "</td>";
    
    // Display the specific image for each reservation
    echo "<td><img src='" . htmlspecialchars($imageSrc) . "' alt='Car Image' width='100'/></td>";
    echo "<td>" . date('d-m-Y', strtotime($reservation['startdatum'])) . "</td>";
    echo "<td>" . date('d-m-Y', strtotime($reservation['einddatum'])) . "</td>";
    echo "<td>€" . number_format($reservation['totaalprijs'], 2) . "</td>";
    echo "<td><span class='status-badge " . $statusInfo['class'] . "'>" 
        . htmlspecialchars($statusInfo['text']) . "</span></td>";
    echo "</tr>";
}
    ?>
                    </tbody>
                </table>
            </div>
          <form onsubmit="window.location.href='reservering_new.php'; return false;">
    <input type="submit" value="Reservering Editen" name="Submit" class="crud-btn" />
</form>

            </div>
        </div>
    </main>
</body>
</html>