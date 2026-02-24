<?php
include('sidebar.php'); 
require_once("db.php");

$status = "";

// 1. Handle Delete Request
if (isset($_GET['delete'])) {
    $plate = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM vehicles WHERE plate_no = ?");
    $stmt->bind_param("s", $plate);
    
    if ($stmt->execute()) {
        $status = "<div class='alert success'>Vehicle deleted successfully!</div>";
    } else {
        $status = "<div class='alert error'>Error: Cannot delete vehicle. It may be linked to active violations.</div>";
    }
}

// 2. Handle Form Submission
if (isset($_POST['save_vehicle'])) {
    $plate_no = $_POST['plate_no'];
    $vehicle_type = $_POST['vehicle_type'];
    $driver_id = $_POST['driver_id']; 
    $is_edit = $_POST['is_edit']; 
    $old_plate = $_POST['old_plate']; 

    if ($is_edit == "1") {
        $stmt = $conn->prepare("UPDATE vehicles SET plate_no=?, vehicle_type=?, driver_id=? WHERE plate_no=?");
        $stmt->bind_param("ssss", $plate_no, $vehicle_type, $driver_id, $old_plate);
        $msg = "Vehicle updated successfully!";
    } else {
        $stmt = $conn->prepare("INSERT INTO vehicles (plate_no, vehicle_type, driver_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $plate_no, $vehicle_type, $driver_id);
        $msg = "New vehicle registered successfully!";
    }

    if ($stmt->execute()) {
        $status = "<div class='alert success'>$msg</div>";
    } else {
        $status = "<div class='alert error'>Error: Operation failed. This plate number might already be registered.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicles | BCTRMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; }

        /* FIXED RESPONSIVE LAYOUT ENGINE */
        .main-content { 
            flex: 1;
            margin-left: 260px; 
            padding: 40px 20px; 
            min-height: 100vh; 
            transition: all 0.3s ease; 
            width: calc(100% - 260px);
        }

        body.sidebar-is-collapsed .main-content {
            margin-left: 70px;
            width: calc(100% - 70px);
        }

        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            flex-wrap: wrap; 
            gap: 15px;
        }

        .header h1 { font-size: 1.5rem; color: #003366; }

        .btn-add { 
            background: #003366; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600; 
            transition: 0.3s; 
            white-space: nowrap;
        }

        /* Table Card and Responsive Container */
        .table-card { 
            background: #fff; 
            padding: 20px; 
            border-radius: 15px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto; 
            -webkit-overflow-scrolling: touch;
        }

        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; color: #666; font-size: 12px; text-transform: uppercase; }

        .btn-edit { color: #0059b3; cursor: pointer; font-size: 18px; }
        .btn-delete { color: #e74c3c; font-size: 18px; margin-left: 10px; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { 
            background: #fff; 
            margin: 50px auto; 
            border-radius: 15px; 
            width: 90%; 
            max-width: 450px; 
            overflow: hidden; 
            animation: slideDown 0.3s ease; 
        }
        .modal-header { background-color: #0059b3; padding: 20px; color: white; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 25px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 14px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; outline: none; }
        .btn-save { background: #0059b3; color: white; width: 100%; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }

        /* Alerts */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }

        /* Mobile Breakpoint Fixes */
        @media (max-width: 768px) {
            .main-content { 
                margin-left: 70px !important; 
                width: calc(100% - 70px);
                padding: 20px 10px; 
            }
            .header h1 { font-size: 1.2rem; }
            .btn-add { width: 100%; }
        }

        @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <h1>Vehicles Management</h1>
        <button class="btn-add" onclick="openAddModal()">+ Register New Vehicle</button>
    </div>

    <?php echo $status; ?>

    <div class="table-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Plate No.</th>
                        <th>Vehicle Type</th>
                        <th>Assigned Driver</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT v.*, d.full_name FROM vehicles v 
                            LEFT JOIN drivers d ON v.driver_id = d.driver_id 
                            ORDER BY v.plate_no ASC";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $json_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                            echo "<tr>
                                    <td><strong>{$row['plate_no']}</strong></td>
                                    <td>{$row['vehicle_type']}</td>
                                    <td>" . ($row['full_name'] ?? 'Unassigned') . " <small>({$row['driver_id']})</small></td>
                                    <td>
                                        <i class='fa-solid fa-pen-to-square btn-edit' onclick='openEditModal($json_data)' title='Edit'></i>
                                        <a href='vehicles.php?delete=" . urlencode($row['plate_no']) . "' onclick='return confirmDelete()'>
                                            <i class='fa-solid fa-trash btn-delete' title='Delete'></i>
                                        </a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' align='center'>No vehicles registered yet.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="vehicleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">New Vehicle</h2>
            <span onclick="closeModal()" style="cursor:pointer; font-size:24px;">&times;</span>
        </div>
        <div class="modal-body">
            <form action="vehicles.php" method="POST" id="vehicleForm">
                <input type="hidden" name="is_edit" id="is_edit" value="0">
                <input type="hidden" name="old_plate" id="old_plate">
                
                <div class="form-group">
                    <label>Plate No.</label>
                    <input type="text" name="plate_no" id="form_plate" required placeholder="e.g. ABC 1234">
                </div>
                <div class="form-group">
                    <label>Vehicle Type</label>
                    <input type="text" name="vehicle_type" id="form_type" required placeholder="e.g. Sedan, Truck">
                </div>
                <div class="form-group">
                    <label>Assigned Driver</label>
                    <select name="driver_id" id="form_driver" required>
                        <option value="">-- Select Driver --</option>
                        <?php
                        $drivers = $conn->query("SELECT driver_id, full_name FROM drivers ORDER BY full_name ASC");
                        while($d = $drivers->fetch_assoc()) {
                            echo "<option value='{$d['driver_id']}'>{$d['full_name']} ({$d['driver_id']})</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" name="save_vehicle" id="submitBtn" class="btn-save">Save Vehicle</button>
            </form>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById("vehicleModal");
    const vehicleForm = document.getElementById("vehicleForm");

    function openAddModal() {
        vehicleForm.reset();
        document.getElementById("modalTitle").innerText = "New Vehicle";
        document.getElementById("submitBtn").innerText = "Save Vehicle";
        document.getElementById("is_edit").value = "0";
        document.getElementById("old_plate").value = "";
        modal.style.display = "block";
    }

    function openEditModal(data) {
        document.getElementById("modalTitle").innerText = "Edit Vehicle";
        document.getElementById("submitBtn").innerText = "Update Vehicle";
        document.getElementById("is_edit").value = "1";
        document.getElementById("old_plate").value = data.plate_no;
        document.getElementById("form_plate").value = data.plate_no;
        document.getElementById("form_type").value = data.vehicle_type;
        document.getElementById("form_driver").value = data.driver_id;
        modal.style.display = "block";
    }

    function closeModal() { modal.style.display = "none"; }
    function confirmDelete() { return confirm("Are you sure you want to delete this vehicle?"); }
    window.onclick = function(event) { if (event.target == modal) { closeModal(); } }
</script>

</body>
</html>