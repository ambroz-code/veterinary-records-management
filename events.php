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
if (isset($_POST['delete']) && isset($_POST['event_id'])) {
    $event_id = $_POST['event_id'];
    $query = "DELETE e FROM events e 
              JOIN animals a ON e.animal_id = a.animal_id 
              WHERE e.event_id = ? AND a.farm_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $event_id, $farm_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Event deleted successfully";
    } else {
        $_SESSION['message'] = "Error deleting event";
    }
    header("Location: events.php");
    exit();
}

// Handle Add/Update Operation
if (isset($_POST['submit'])) {
    $animal_id = $_POST['animal_id'];
    $event_type = $_POST['event_type'];
    $date = $_POST['date'];
    $description = $_POST['description'];

    // Verify animal belongs to farm
    $query = "SELECT animal_id FROM animals WHERE animal_id = ? AND farm_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $animal_id, $farm_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $_SESSION['message'] = "Invalid animal selected";
    } else {
        if (isset($_POST['event_id'])) {
            // Update existing event
            $event_id = $_POST['event_id'];
            $query = "UPDATE events SET animal_id=?, event_type=?, date=?, description=? 
                      WHERE event_id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isssi", $animal_id, $event_type, $date, $description, $event_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Event updated successfully";
            } else {
                $_SESSION['message'] = "Error updating event";
            }
        } else {
            // Add new event
            $query = "INSERT INTO events (animal_id, event_type, date, description) 
                      VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isss", $animal_id, $event_type, $date, $description);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Event added successfully";
            } else {
                $_SESSION['message'] = "Error adding event";
            }
        }
    }
    header("Location: events.php");
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

// Fetch all events for the farm
$query = "SELECT e.*, a.tag_number, a.species 
          FROM events e
          JOIN animals a ON e.animal_id = a.animal_id
          WHERE a.farm_id = ?
          ORDER BY e.date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management</title>
    <link rel="stylesheet" href="events.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Event Management</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="animals.php">Animals</a>
                <a href="medical_records.php">Medical Records</a>
                <a href="veterinarians.php">Veterinarians</a>
                <a href="medicines.php">Medicines</a>
                <a href="reminders.php">Reminders</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </nav>
        </header>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Add/Edit Event Form -->
        <section class="animal-form">
            <h2>Add New Event</h2>
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
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type" required>
                        <option value="Birth">Birth</option>
                        <option value="Death">Death</option>
                        <option value="Sale">Sale</option>
                        <option value="Transfer">Transfer</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required rows="3"></textarea>
                </div>

                <button type="submit" name="submit" class="submit-btn">Add Event</button>
            </form>
        </section>

        <!-- Event List -->
        <section class="animals-list">
            <h2>Events</h2>
            <table>
                <thead>
                    <tr>
                        <th>Animal</th>
                        <th>Date</th>
                        <th>Event Type</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['tag_number'] . ' - ' . $event['species']); ?></td>
                            <td><?php echo htmlspecialchars($event['date']); ?></td>
                            <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                            <td><?php echo htmlspecialchars($event['description']); ?></td>
                            <td class="actions">
                                <button onclick="editEvent(<?php echo htmlspecialchars(json_encode($event)); ?>)" 
                                        class="edit-btn">Edit</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                    <button type="submit" name="delete" class="delete-btn" 
                                            onclick="return confirm('Are you sure you want to delete this event?')">
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
        function editEvent(event) {
            document.getElementById('animal_id').value = event.animal_id;
            document.getElementById('event_type').value = event.event_type;
            document.getElementById('date').value = event.date;
            document.getElementById('description').value = event.description;
            
            // Add hidden input for event_id
            let form = document.querySelector('.animal-form form');
            let hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'event_id';
            hiddenInput.value = event.event_id;
            form.appendChild(hiddenInput);
            
            // Change button text
            document.querySelector('.submit-btn').textContent = 'Update Event';
            document.querySelector('.animal-form h2').textContent = 'Edit Event';
            
            // Scroll to form
            document.querySelector('.animal-form').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
