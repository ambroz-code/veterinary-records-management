<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'db.php';

$message = '';



// Handle Delete Operation
if (isset($_POST['delete']) && isset($_POST['medicine_id'])) {
    $medicine_id = $_POST['medicine_id'];

    $query = "DELETE FROM medicines WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $medicine_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Medicine deleted successfully";
    } else {
        $_SESSION['message'] = "Error deleting medicine";
    }
    header("Location: medicines.php");
    exit();
}

// Handle Add/Update Operation
if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    if (isset($_POST['medicine_id'])) {
        // Update existing medicine
        $medicine_id = $_POST['medicine_id'];
        $query = "UPDATE medicines SET name=?, description=?, category=?, price=?, stock=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssdi", $name, $description, $category, $price, $stock, $medicine_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Medicine updated successfully";
        } else {
            $_SESSION['message'] = "Error updating medicine";
        }
    } else {
        // Add new medicine
        $query = "INSERT INTO medicines (name, description, category, price, stock) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssd", $name, $description, $category, $price, $stock);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Medicine added successfully";
        } else {
            $_SESSION['message'] = "Error adding medicine";
        }
    }
    header("Location: medicines.php");
    exit();
}

// Display message if exists and clear it
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Fetch all medicines
$query = "SELECT * FROM medicines ORDER BY name";
$result = $conn->query($query);
$medicines = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Management</title>
    <link rel="stylesheet" href="medicines.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Medicine Management</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="animals.php">Animals</a>
                <a href="medical_records.php">Medical Records</a>
                <a href="events.php">Events</a>
                <a href="veterinarians.php">Veterinarians</a>
                <a href="reminders.php">Reminders</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </nav>
        </header>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Add/Edit Medicine Form -->
        <section class="medicine-form">
            <h2>Add New Medicine</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Medicine Name</label>
                    <input type="text" id="name" name="name" required maxlength="100">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required maxlength="255"></textarea>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" required maxlength="100">
                </div>

                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" id="price" name="price" required step="0.01">
                </div>

                <div class="form-group">
                    <label for="stock">Stock</label>
                    <input type="number" id="stock" name="stock" required>
                </div>

                <button type="submit" name="submit" class="submit-btn">Add Medicine</button>
            </form>
        </section>

        <!-- Medicines List -->
        <section class="medicines-list">
            <h2>Current Medicines</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicines as $medicine): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                            <td><?php echo htmlspecialchars($medicine['description']); ?></td>
                            <td><?php echo htmlspecialchars($medicine['category']); ?></td>
                            <td><?php echo $medicine['price']; ?></td>
                            <td><?php echo $medicine['stock']; ?></td>
                            <td class="actions">
                                <button onclick="editMedicine(<?php echo htmlspecialchars(json_encode($medicine)); ?>)" 
                                        class="edit-btn">Edit</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="medicine_id" value="<?php echo $medicine['id']; ?>">
                                    <button type="submit" name="delete" class="delete-btn" 
                                            onclick="return confirm('Are you sure you want to delete this medicine?')">
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
        function editMedicine(medicine) {
            document.getElementById('name').value = medicine.name;
            document.getElementById('description').value = medicine.description;
            document.getElementById('category').value = medicine.category;
            document.getElementById('price').value = medicine.price;
            document.getElementById('stock').value = medicine.stock;
            
            // Add hidden input for medicine_id
            let form = document.querySelector('.medicine-form form');
            let hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'medicine_id';
            hiddenInput.value = medicine.id;
            form.appendChild(hiddenInput);
            
            // Change button text
            document.querySelector('.submit-btn').textContent = 'Update Medicine';
            document.querySelector('.medicine-form h2').textContent = 'Edit Medicine';
            
            // Scroll to form
            document.querySelector('.medicine-form').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
