<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'db.php';

$farm_id = $_SESSION['farm_id'];
$message = '';

// Handle Delete Operation
if (isset($_POST['delete']) && isset($_POST['record_id'])) {
    $record_id = $_POST['record_id'];
    $query = "DELETE mr FROM medical_records mr 
              JOIN animals a ON mr.animal_id = a.animal_id 
              WHERE mr.record_id = ? AND a.farm_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $record_id, $farm_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Record deleted successfully";
    } else {
        $_SESSION['message'] = "Error deleting record";
    }
    header("Location: medical_records.php");
    exit();
}

// Handle Add/Update Operation
if (isset($_POST['submit'])) {
    $animal_id = $_POST['animal_id'];
    $date = $_POST['date'];
    $type = $_POST['type'];
    $details = $_POST['details'];
    $vet_id = $_POST['vet_id'];

    // Verify animal belongs to farm
    $query = "SELECT animal_id FROM animals WHERE animal_id = ? AND farm_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $animal_id, $farm_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $_SESSION['message'] = "Invalid animal selected";
    } else {
        if (isset($_POST['record_id'])) {
            // Update existing record
            $record_id = $_POST['record_id'];
            $query = "UPDATE medical_records SET animal_id=?, date=?, type=?, details=?, vet_id=? 
                      WHERE record_id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isssii", $animal_id, $date, $type, $details, $vet_id, $record_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Record updated successfully";
            } else {
                $_SESSION['message'] = "Error updating record";
            }
        } else {
            // Add new record
            $query = "INSERT INTO medical_records (animal_id, date, type, details, vet_id) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isssi", $animal_id, $date, $type, $details, $vet_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Record added successfully";
            } else {
                $_SESSION['message'] = "Error adding record";
            }
        }
    }
    header("Location: medical_records.php");
    exit();
}

// Display message if exists and clear it
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Fetch all animals for the farm (for dropdown)
$query = "SELECT animal_id, tag_number, species FROM animals WHERE farm_id = ? ORDER BY tag_number";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$animals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch all veterinarians (for dropdown)
$query = "SELECT vet_id, name FROM veterinarians ORDER BY name";
$vets = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Fetch all medical records for the farm
$query = "SELECT mr.*, a.tag_number, a.species, v.name as vet_name 
          FROM medical_records mr
          JOIN animals a ON mr.animal_id = a.animal_id
          LEFT JOIN veterinarians v ON mr.vet_id = v.vet_id
          WHERE a.farm_id = ?
          ORDER BY mr.date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records Management</title>
    <link rel="stylesheet" href="farm.css">
    <link rel="stylesheet" href="animals.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Medical Records Management</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
               <a href="animals.php">Animals</a>
                <a href="events.php">Events</a>
                <a href="veterinarians.php">Veterinarians</a>
                <a href="medicines.php">Medicines</a>
                <a href="reminders.php">Reminders</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </nav>
        </header>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Add/Edit Medical Record Form -->
        <section class="animal-form">
            <h2>Add New Medical Record</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="animal_id">Animal</label>
                    <select id="animal_id" name="animal_id" required>
                        <option value="">Select Animal</option>
                        <?php foreach ($animals as $animal): ?>
                            <option value="<?php echo $animal['animal_id']; ?>">
                                <?php echo htmlspecialchars($animal['tag_number'] . ' - ' . $animal['species']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" required>
                </div>

                <div class="form-group">
                    <label for="type">Treatment Type</label>
                    <select id="type" name="type" required>
                        <option value="Vaccination">Vaccination</option>
                        <option value="Treatment">Treatment</option>
                        <option value="Surgery">Surgery</option>
                        <option value="Check-up">Check-up</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="details">Details</label>
                    <textarea id="details" name="details" required rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="vet_id">Veterinarian</label>
                    <select id="vet_id" name="vet_id" required>
                        <option value="">Select Veterinarian</option>
                        <?php foreach ($vets as $vet): ?>
                            <option value="<?php echo $vet['vet_id']; ?>">
                                <?php echo htmlspecialchars($vet['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" name="submit" class="submit-btn">Add Record</button>
            </form>
        </section>

        <!-- Medical Records List -->
        <section class="animals-list">
            <h2>Medical Records</h2>
            <table>
                <thead>
                    <tr>
                        <th>Animal</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Details</th>
                        <th>Veterinarian</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['tag_number'] . ' - ' . $record['species']); ?></td>
                            <td><?php echo htmlspecialchars($record['date']); ?></td>
                            <td><?php echo htmlspecialchars($record['type']); ?></td>
                            <td><?php echo htmlspecialchars($record['details']); ?></td>
                            <td><?php echo htmlspecialchars($record['vet_name']); ?></td>
                            <td class="actions">
                                <button onclick="editRecord(<?php echo htmlspecialchars(json_encode($record)); ?>)" 
                                        class="edit-btn">Edit</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="record_id" value="<?php echo $record['record_id']; ?>">
                                    <button type="submit" name="delete" class="delete-btn" 
                                            onclick="return confirm('Are you sure you want to delete this record?')">
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
        function editRecord(record) {
            document.getElementById('animal_id').value = record.animal_id;
            document.getElementById('date').value = record.date;
            document.getElementById('type').value = record.type;
            document.getElementById('details').value = record.details;
            document.getElementById('vet_id').value = record.vet_id;
            
            // Add hidden input for record_id
            let form = document.querySelector('.animal-form form');
            let hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'record_id';
            hiddenInput.value = record.record_id;
            form.appendChild(hiddenInput);
            
            // Change button text
            document.querySelector('.submit-btn').textContent = 'Update Record';
            document.querySelector('.animal-form h2').textContent = 'Edit Medical Record';
            
            // Scroll to form
            document.querySelector('.animal-form').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html> 