<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'farm.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($farm_details['name']); ?></title>
    <link rel="stylesheet" href="farm.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="farm-info">
                <h1><?php echo htmlspecialchars($farm_details['name']); ?></h1>
                <p><?php echo htmlspecialchars($farm_details['location']); ?></p>
            </div>
            <nav>
                <a href="animals.php">Animals</a>
                <a href="medical_records.php">Medical Records</a>
                <a href="events.php">Events</a>
                <a href="veterinarians.php">Veterinarians</a>
                <a href="medicines.php">Medicines</a>
                <a href="reminders.php">Reminders</a>
            </nav>
            <a href="logout.php" class="logout-btn">Logout</a>
        </header>
        <div class="image">
            <img src="animals.jpeg" alt="Farm Image">
            <img src="animal.jpeg" alt="Farm Image">
            <img src="farmanimals.jpeg" alt="Farm Image">
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>Total Animals</h3>
                <p id="total-animals"><?php echo $total_animals; ?></p>
            </div>
            <div class="stat-card">
                <h3>Upcoming Treatments</h3>
                <p id="upcoming-treatments"><?php echo $upcoming_treatments; ?></p>
            </div>
        </div>

        <section class="recent-records">
            <h2>Recent Medical Records</h2>
            <table>
                <thead>
                    <tr>
                        <th>Animal Tag</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Details</th>
                        <th>Veterinarian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['tag_number']); ?></td>
                            <td><?php echo htmlspecialchars($record['date']); ?></td>
                            <td><?php echo htmlspecialchars($record['type']); ?></td>
                            <td><?php echo htmlspecialchars($record['details']); ?></td>
                            <td><?php echo htmlspecialchars($record['vet_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
    <script src="script.js"></script>
    <footer>
        <p>&copy; 2024 veterinary record management system. All rights reserved.</p>
    </footer>
</body>
</html>