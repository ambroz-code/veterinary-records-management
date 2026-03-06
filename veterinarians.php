<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$message = '';

// Handle Delete Operation
if (isset($_POST['delete']) && isset($_POST['vet_id'])) {
    $vet_id = $_POST['vet_id'];
    $query = "DELETE FROM veterinarians WHERE vet_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $vet_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Veterinarian deleted successfully";
    } else {
        $_SESSION['message'] = "Error deleting veterinarian";
    }
    header("Location: veterinarians.php");
    exit();
}

// Handle Add/Update Operation
if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $contact_info = $_POST['contact_info'];
    $license_number = $_POST['license_number'];

    if (isset($_POST['vet_id'])) {
        // Update existing veterinarian
        $vet_id = $_POST['vet_id'];
        $query = "UPDATE veterinarians SET name = ?, contact_info = ?, license_number = ? WHERE vet_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $name, $contact_info, $license_number, $vet_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Veterinarian updated successfully";
        } else {
            $_SESSION['message'] = "Error updating veterinarian";
        }
    } else {
        // Add new veterinarian
        $query = "INSERT INTO veterinarians (name, contact_info, license_number) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $name, $contact_info, $license_number);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Veterinarian added successfully";
        } else {
            $_SESSION['message'] = "Error adding veterinarian";
        }
    }
    header("Location: veterinarians.php");
    exit();
}

// Display message if exists and clear it
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Fetch all veterinarians
$query = "SELECT * FROM veterinarians ORDER BY name";
$vets = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinarians Management</title>
    <link rel="stylesheet" href="veterinarians.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Veterinarians Management</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="animals.php">Animals</a>
                <a href="medical_records.php">Medical Records</a>
                <a href="events.php">Events</a>
                <a href="medicines.php">Medicines</a>
                <a href="reminders.php">Reminders</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </nav>
        </header>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Add/Edit Veterinarian Form -->
        <section class="vet-form">
            <h2>Add New Veterinarian</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="contact_info">Contact Information</label>
                    <input type="text" id="contact_info" name="contact_info" required>
                </div>

                <div class="form-group">
                    <label for="license_number">License Number</label>
                    <input type="text" id="license_number" name="license_number" required>
                </div>

                <button type="submit" name="submit" class="submit-btn">Add Veterinarian</button>
            </form>
        </section>

        <!-- Veterinarians List -->
        <section class="vets-list">
            <h2>Veterinarians</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact Info</th>
                        <th>License Number</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vets as $vet): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vet['name']); ?></td>
                            <td><?php echo htmlspecialchars($vet['contact_info']); ?></td>
                            <td><?php echo htmlspecialchars($vet['license_number']); ?></td>
                            <td class="actions">
                                <button onclick="editVet(<?php echo htmlspecialchars(json_encode($vet)); ?>)" class="edit-btn">Edit</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="vet_id" value="<?php echo $vet['vet_id']; ?>">
                                    <button type="submit" name="delete" class="delete-btn" 
                                            onclick="return confirm('Are you sure you want to delete this veterinarian?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <script>
        function editVet(vet) {
            document.getElementById('name').value = vet.name;
            document.getElementById('contact_info').value = vet.contact_info;
            document.getElementById('license_number').value = vet.license_number;

            // Add hidden input for vet_id
            let form = document.querySelector('.vet-form form');
            let hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'vet_id';
            hiddenInput.value = vet.vet_id;
            form.appendChild(hiddenInput);

            // Change button text
            document.querySelector('.submit-btn').textContent = 'Update Veterinarian';
            document.querySelector('.vet-form h2').textContent = 'Edit Veterinarian';

            // Scroll to form
            document.querySelector('.vet-form').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
