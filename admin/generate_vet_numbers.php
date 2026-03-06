<?php
require '../db.php';

function generateVetNumber() {
    static $counter = 100; // Start from 100
    $counter++;
    return 'VET' . $counter;
}

// Add new VET numbers
for ($i = 0; $i < 10; $i++) { // Generate 10 numbers at a time
    $vet_number = generateVetNumber();
    $query = "INSERT INTO authorized_vets (vet_number) VALUES (?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $vet_number);
    $stmt->execute();
}

// Display all VET numbers
$query = "SELECT * FROM authorized_vets ORDER BY created_at DESC";
$result = $conn->query($query);
echo "<h2>Available VET Numbers</h2>";
echo "<table border='1' style='border-collapse: collapse; padding: 5px;'>";
echo "<tr><th>VET Number</th><th>Status</th><th>Created At</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['vet_number'] . "</td>";
    echo "<td>" . ($row['is_used'] ? 'Used' : 'Available') . "</td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?> 