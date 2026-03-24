<?php
require_once("db.php");

if (isset($_GET['plate_no'])) {
    $plate = $_GET['plate_no'];
    
    // Selecting vehicle and driver details based on your table columns
    $stmt = $conn->prepare("SELECT v.color, v.engine_no, v.driver_id 
                            FROM vehicles v 
                            WHERE v.plate_no = ?");
    $stmt->bind_param("s", $plate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Vehicle not found']);
    }
}
?>