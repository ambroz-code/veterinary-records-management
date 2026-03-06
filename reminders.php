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
if (isset($_POST['delete']) && isset($_POST['reminder_id'])) {
    $reminder_id = $_POST['reminder_id'];
    $query = "DELETE r FROM reminders r
              JOIN animals a ON r.animal_id = a.animal_id
              WHERE r.reminder_id = ? AND a.farm_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $reminder_id, $farm_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Reminder deleted successfully";
    } else {
        $_SESSION['message'] = "Error deleting reminder";
    }
    header("Location: reminders.php");
    exit();
}

// Handle Add/Update Operation
if (isset($_POST['submit'])) {
    $animal_id = $_POST['animal_id'];
    $due_date = $_POST['due_date'];
    $task_type = $_POST['task_type'];
    $description = $_POST['description'];

    // Verify animal belongs to farm
    $query = "SELECT animal_id FROM animals WHERE animal_id = ? AND farm_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $animal_id, $farm_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $_SESSION['message'] = "Invalid animal selected";
    } else {
        if (isset($_POST['reminder_id'])) {
            // Update existing reminder
            $reminder_id = $_POST['reminder_id'];
            $query = "UPDATE reminders SET animal_id = ?, due_date = ?, task_type = ?, description = ?
                      WHERE reminder_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isssi", $animal_id, $due_date, $task_type, $description, $reminder_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Reminder updated successfully";
            } else {
                $_SESSION['message'] = "Error updating reminder";
            }
        } else {
            // Add new reminder
            $query = "INSERT INTO reminders (animal_id, due_date, task_type, description) 
                      VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isss", $animal_id, $due_date, $task_type, $description);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Reminder added successfully";
            } else {
                $_SESSION['message'] = "Error adding reminder";
            }
        }
    }
    header("Location: reminders.php");
    exit();
}

// Display message if exists and clear it
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Fetch all animals for the farm (for dropdown)
$query = "SELECT animal_id, tag_number, species, breed FROM animals WHERE farm_id = ? ORDER BY tag_number";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$animals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch all reminders for the farm
$query = "SELECT r.*, a.tag_number, a.species, a.breed 
          FROM reminders r
          JOIN animals a ON r.animal_id = a.animal_id
          WHERE a.farm_id = ?
          ORDER BY r.due_date ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$reminders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reminders Management</title>
    <link rel="stylesheet" href="reminders.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Reminders Management</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="animals.php">Animals</a>
                <a href="medical_records.php">Medical Records</a>
                <a href="events.php">Events</a>
                <a href="veterinarians.php">Veterinarians</a>
                <a href="medicines.php">Medicines</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </nav>
        </header>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Add/Edit Reminder Form -->
        <section class="reminder-form">
            <h2>Add New Reminder</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="animal_id">Animal</label>
                    <select id="animal_id" name="animal_id" required>
                        <option value="">Select Animal</option>
                        <?php foreach ($animals as $animal): ?>
                            <option value="<?php echo $animal['animal_id']; ?>">
                                <?php echo htmlspecialchars($animal['tag_number'] . ' - ' . $animal['species'] . ' (' . $animal['breed'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input type="date" id="due_date" name="due_date" required>
                </div>

                <div class="form-group">
                    <label for="task_type">Task Type</label>
                    <select id="task_type" name="task_type" required>
                        <option value="Vaccination">Vaccination</option>
                        <option value="Treatment">Treatment</option>
                        <option value="Check-up">Check-up</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required rows="3"></textarea>
                </div>

                <button type="submit" name="submit" class="submit-btn">Add Reminder</button>
            </form>
        </section>

        <!-- Reminders List -->
        <section class="reminders-list">
            <h2>Reminders</h2>
            <table>
                <thead>
                    <tr>
                        <th>Animal</th>
                        <th>Due Date</th>
                        <th>Task Type</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reminders as $reminder): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reminder['tag_number'] . ' - ' . $reminder['species'] . ' (' . $reminder['breed'] . ')'); ?></td>
                            <td><?php echo htmlspecialchars($reminder['due_date']); ?></td>
                            <td><?php echo htmlspecialchars($reminder['task_type']); ?></td>
                            <td><?php echo htmlspecialchars($reminder['description']); ?></td>
                            <td class="actions">
                                <button onclick="editReminder(<?php echo htmlspecialchars(json_encode($reminder)); ?>)" 
                                        class="edit-btn">Edit</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="reminder_id" value="<?php echo $reminder['reminder_id']; ?>">
                                    <button type="submit" name="delete" class="delete-btn" 
                                            onclick="return confirm('Are you sure you want to delete this reminder?')">
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
        function editReminder(reminder) {
            document.getElementById('animal_id').value = reminder.animal_id;
            document.getElementById('due_date').value = reminder.due_date;
            document.getElementById('task_type').value = reminder.task_type;
            document.getElementById('description').value = reminder.description;
            
            // Add hidden input for reminder_id
            let form = document.querySelector('.reminder-form form');
            let hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'reminder_id';
            hiddenInput.value = reminder.reminder_id;
            form.appendChild(hiddenInput);
            
            // Change button text
            document.querySelector('.submit-btn').textContent = 'Update Reminder';
            document.querySelector('.reminder-form h2').textContent = 'Edit Reminder';
            
            // Scroll to form
            document.querySelector('.reminder-form').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
