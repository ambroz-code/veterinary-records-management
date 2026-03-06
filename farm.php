<?php
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$farm_id = $_SESSION['farm_id'];

// Get farm details
$query = "SELECT name, location, contact_person FROM farms WHERE farm_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$result = $stmt->get_result();
$farm_details = $result->fetch_assoc();

// Total animals
$query = "SELECT COUNT(*) AS total_animals FROM animals WHERE farm_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$result = $stmt->get_result();
$total_animals = $result->fetch_assoc()['total_animals'];

// Upcoming reminders
$query = "SELECT COUNT(*) AS upcoming_treatments 
          FROM reminders r
          JOIN animals a ON r.animal_id = a.animal_id
          WHERE a.farm_id = ? AND r.due_date >= CURDATE()";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$result = $stmt->get_result();
$upcoming_treatments = $result->fetch_assoc()['upcoming_treatments'];

// Recent medical records
$query = "SELECT a.tag_number, m.date, m.type, m.details, v.name as vet_name
          FROM medical_records m 
          JOIN animals a ON m.animal_id = a.animal_id 
          LEFT JOIN veterinarians v ON m.vet_id = v.vet_id
          WHERE a.farm_id = ? 
          ORDER BY m.date DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$result = $stmt->get_result();
$recent_records = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>
