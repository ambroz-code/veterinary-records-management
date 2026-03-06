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
if (isset($_POST['delete']) && isset($_POST['animal_id'])) {
    $animal_id = $_POST['animal_id'];
    $query = "DELETE FROM animals WHERE animal_id = ? AND farm_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $animal_id, $farm_id);
    if ($stmt->execute()) {
        $message = "Animal deleted successfully";
    } else {
        $message = "Error deleting animal";
    }
}

// Handle Add/Update Operation
if (isset($_POST['submit'])) {
    $tag_number = $_POST['tag_number'];
    $species = $_POST['species'];
    $breed = $_POST['breed'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];

    if (isset($_POST['animal_id'])) {
        // Update existing animal
        $animal_id = $_POST['animal_id'];
        $query = "UPDATE animals SET tag_number=?, species=?, breed=?, dob=?, gender=? 
                  WHERE animal_id=? AND farm_id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssii", $tag_number, $species, $breed, $dob, $gender, $animal_id, $farm_id);
        if ($stmt->execute()) {
            $message = "Animal updated successfully";
        } else {
            $message = "Error updating animal";
        }
    } else {
        // Add new animal
        $query = "INSERT INTO animals (tag_number, species, breed, dob, gender, farm_id) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssi", $tag_number, $species, $breed, $dob, $gender, $farm_id);
        if ($stmt->execute()) {
            $message = "Animal added successfully";
        } else {
            $message = "Error adding animal";
        }
    }
}

// Fetch all animals for the farm
$query = "SELECT * FROM animals WHERE farm_id = ? ORDER BY tag_number";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$result = $stmt->get_result();
$animals = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animal Management</title>
    <link rel="stylesheet" href="farm.css">
    <link rel="stylesheet" href="animals.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Animal Management</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="medical_records.php">Medical Records</a>
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

        <!-- Add/Edit Animal Form -->
        <section class="animal-form">
            <h2>Add New Animal</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="tag_number">Tag Number</label>
                    <input type="text" id="tag_number" name="tag_number" required>
                </div>

                <div class="form-group">
                    <label for="species">Species</label>
                    <input type="text" id="species" name="species" required>
                </div>

                <div class="form-group">
                    <label for="breed">Breed</label>
                    <input type="text" id="breed" name="breed" required>
                </div>

                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" required>
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <button type="submit" name="submit" class="submit-btn">Add Animal</button>
            </form>
        </section>

        <!-- Animals List -->
        <section class="animals-list">
            <h2>Current Animals</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tag Number</th>
                        <th>Species</th>
                        <th>Breed</th>
                        <th>Date of Birth</th>
                        <th>Gender</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($animals as $animal): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($animal['tag_number']); ?></td>
                            <td><?php echo htmlspecialchars($animal['species']); ?></td>
                            <td><?php echo htmlspecialchars($animal['breed']); ?></td>
                            <td><?php echo htmlspecialchars($animal['dob']); ?></td>
                            <td><?php echo htmlspecialchars($animal['gender']); ?></td>
                            <td class="actions">
                                <button onclick="editAnimal(<?php echo htmlspecialchars(json_encode($animal)); ?>)" 
                                        class="edit-btn">Edit</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="animal_id" value="<?php echo $animal['animal_id']; ?>">
                                    <button type="submit" name="delete" class="delete-btn" 
                                            onclick="return confirm('Are you sure you want to delete this animal?')">
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
        function editAnimal(animal) {
            document.getElementById('tag_number').value = animal.tag_number;
            document.getElementById('species').value = animal.species;
            document.getElementById('breed').value = animal.breed;
            document.getElementById('dob').value = animal.dob;
            document.getElementById('gender').value = animal.gender;
            
            // Add hidden input for animal_id
            let form = document.querySelector('.animal-form form');
            let hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'animal_id';
            hiddenInput.value = animal.animal_id;
            form.appendChild(hiddenInput);
            
            // Change button text
            document.querySelector('.submit-btn').textContent = 'Update Animal';
            document.querySelector('.animal-form h2').textContent = 'Edit Animal';
        }
    </script>
</body>
</html> 