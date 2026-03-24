<?php
include('sidebar.php'); 
require_once("db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Get role from URL for access control
$user_role = $_GET['role'] ?? 'User';
$role_param = "?role=" . urlencode($user_role);

// Check if user is Admin to restrict actions
$is_admin = (strtolower($user_role) === 'admin');

$status = "";

// 2. Handle Delete Request - Only if NOT Admin
if (isset($_GET['delete']) && !$is_admin) {
    $a_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM accidents WHERE accident_id = ?");
    $stmt->bind_param("i", $a_id);
    
    if ($stmt->execute()) {
        $status = "<div class='alert success'><i class='fa-solid fa-circle-check'></i> Accident record deleted successfully!</div>";
    } else {
        $status = "<div class='alert error'><i class='fa-solid fa-circle-exclamation'></i> Error: Could not delete record.</div>";
    }
}

// 3. Handle Form Submission - Only if NOT Admin
if (isset($_POST['save_accident']) && !$is_admin) {
    $driver_id = $_POST['driver_id'];
    $plate_no = $_POST['plate_no'];
    $location = $_POST['location'];
    $severity = $_POST['severity'];
    $accident_date = $_POST['accident_date'];
    $description = $_POST['description'];
    $recorded_by = $_SESSION['user_id'] ?? 1; 
    $is_edit = $_POST['is_edit'];
    $accident_id = $_POST['accident_id'] ?? null;

    if ($is_edit == "1" && $accident_id) {
        $stmt = $conn->prepare("UPDATE accidents SET driver_id=?, plate_no=?, location=?, severity=?, accident_date=?, description=? WHERE accident_id=?");
        $stmt->bind_param("ssssssi", $driver_id, $plate_no, $location, $severity, $accident_date, $description, $accident_id);
        $msg = "Accident record updated successfully!";
    } else {
        $stmt = $conn->prepare("INSERT INTO accidents (driver_id, plate_no, location, severity, accident_date, description, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $driver_id, $plate_no, $location, $severity, $accident_date, $description, $recorded_by);
        $msg = "Accident recorded successfully!";
    }

    if ($stmt->execute()) {
        $status = "<div class='alert success'><i class='fa-solid fa-circle-check'></i> $msg</div>";
    } else {
        $status = "<div class='alert error'><i class='fa-solid fa-circle-exclamation'></i> Error: Operation failed.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accident Records | BCTRMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; }
        .main-content { flex: 1; margin-left: 260px; padding: 40px 20px; min-height: 100vh; transition: all 0.3s ease; width: calc(100% - 260px); }
        body.sidebar-is-collapsed .main-content { margin-left: 70px; width: calc(100% - 70px); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .header h1 { font-size: 1.5rem; color: #003366; font-weight: 700; }
        .search-container { flex: 1; max-width: 400px; position: relative; }
        .search-container input { width: 100%; padding: 12px 15px 12px 40px; border: 1px solid #ddd; border-radius: 8px; outline: none; font-size: 14px; }
        .search-container i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888; }
        .btn-add { background: #003366; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; white-space: nowrap; display: flex; align-items: center; gap: 8px; }
        .btn-add:hover { background: #0059b3; transform: translateY(-2px); }
        .table-card { background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; color: #666; font-size: 12px; text-transform: uppercase; cursor: pointer; transition: 0.2s; }
        th:hover { color: #003366; background: #f1f1f1; }
        .btn-edit { color: #0059b3; cursor: pointer; font-size: 18px; background: none; border: none; }
        .btn-delete { color: #e74c3c; font-size: 18px; margin-left: 10px; }
        .severity-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .minor { background: #e3f2fd; color: #0059b3; }
        .major { background: #fff3e0; color: #ef6c00; }
        .fatal { background: #ffebee; color: #c62828; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px; }
        .modal-content { background: #fff; border-radius: 15px; width: 100%; max-width: 500px; animation: slideDown 0.3s ease; }
        .modal-header { background-color: #003366; padding: 20px; color: white; display: flex; justify-content: space-between; align-items: center; border-radius: 15px 15px 0 0; }
        .modal-body { padding: 25px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 14px; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-save { background: #003366; color: white; width: 100%; padding: 14px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 10px; }
        .success { background: #d4edda; color: #155724; border-left: 5px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; border-left: 5px solid #dc3545; }
        @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <h1>Accident Records</h1>

        <div class="search-container">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="driverSearch" placeholder="Search by driver's name..." onkeyup="filterAccidents()">
        </div>

        <?php if (!$is_admin): ?>
        <button class="btn-add" onclick="openAddModal()">
            <i class="fa-solid fa-plus"></i> Report New Accident
        </button>
        <?php endif; ?>
    </div>

    <?php echo $status; ?>

    <div class="table-card">
        <div class="table-responsive">
            <table id="accidentsTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)">Driver Name <i class="fa-solid fa-sort"></i></th>
                        <th onclick="sortTable(1)">Plate No. <i class="fa-solid fa-sort"></i></th>
                        <th onclick="sortTable(2)">Location <i class="fa-solid fa-sort"></i></th>
                        <th onclick="sortTable(3)">Severity <i class="fa-solid fa-sort"></i></th>
                        <th onclick="sortTable(4)">Date & Time <i class="fa-solid fa-sort"></i></th>
                        <?php if (!$is_admin): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT a.*, d.full_name FROM accidents a 
                            LEFT JOIN drivers d ON a.driver_id = d.driver_id 
                            ORDER BY a.accident_id DESC";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $json_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                            $severity_class = strtolower($row['severity']);
                            $driver_display = $row['full_name'] ?? 'Unassigned';
                            echo "<tr>
                                    <td class='driver-col'>$driver_display</td>
                                    <td><strong>{$row['plate_no']}</strong></td>
                                    <td>{$row['location']}</td>
                                    <td><span class='severity-badge $severity_class'>{$row['severity']}</span></td>
                                    <td>" . date('M d, Y | h:i A', strtotime($row['accident_date'])) . "</td>";
                            
                            if (!$is_admin) {
                                echo "<td>
                                        <button class='btn-edit' onclick='openEditModal($json_data)' title='Edit'><i class='fa-solid fa-pen-to-square'></i></button>
                                        <a href='accidents.php{$role_param}&delete={$row['accident_id']}' class='btn-delete' title='Delete' onclick='return confirm(\"Are you sure?\")'>
                                            <i class='fa-solid fa-trash'></i>
                                        </a>
                                    </td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        $colspan = $is_admin ? 5 : 6;
                        echo "<tr id='no-data-row'><td colspan='$colspan' align='center' style='padding: 40px; color: #999;'>No records found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="accidentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">New Accident Report</h2>
            <span onclick="closeModal()" style="cursor:pointer; font-size:24px;"><i class="fa-solid fa-xmark"></i></span>
        </div>
        <div class="modal-body">
            <form action="accidents.php<?php echo $role_param; ?>" method="POST" id="accidentForm">
                <input type="hidden" name="is_edit" id="is_edit" value="0">
                <input type="hidden" name="accident_id" id="form_accident_id">
                <div class="form-group">
                    <label>Driver Involved</label>
                    <select name="driver_id" id="form_driver_id" required>
                        <option value="">-- Select Driver --</option>
                        <?php
                        $drivers = $conn->query("SELECT driver_id, full_name FROM drivers ORDER BY full_name ASC");
                        while($d = $drivers->fetch_assoc()) {
                            echo "<option value='{$d['driver_id']}'>{$d['full_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Vehicle Plate No.</label>
                    <select name="plate_no" id="form_plate_no" required>
                        <option value="">-- Select Plate Number --</option>
                        <?php
                        $vehicles = $conn->query("SELECT plate_no FROM vehicles ORDER BY plate_no ASC");
                        while($v = $vehicles->fetch_assoc()) {
                            echo "<option value='{$v['plate_no']}'>{$v['plate_no']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" id="form_location" required placeholder="Street / Brgy Name">
                </div>
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Severity</label>
                        <select name="severity" id="form_severity" required>
                            <option value="Minor">Minor</option>
                            <option value="Major">Major</option>
                            <option value="Fatal">Fatal</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Date & Time</label>
                        <input type="datetime-local" name="accident_date" id="form_accident_date" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Brief Description</label>
                    <textarea name="description" id="form_description" rows="3"></textarea>
                </div>
                <button type="submit" name="save_accident" id="submitBtn" class="btn-save">Submit Report</button>
            </form>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById("accidentModal");
    const form = document.getElementById("accidentForm");

    function filterAccidents() {
        const input = document.getElementById("driverSearch");
        const filter = input.value.toLowerCase();
        const table = document.getElementById("accidentsTable");
        const tr = table.getElementsByTagName("tr");
        for (let i = 1; i < tr.length; i++) {
            const td = tr[i].getElementsByClassName("driver-col")[0];
            if (td) {
                const txtValue = td.textContent || td.innerText;
                tr[i].style.display = txtValue.toLowerCase().indexOf(filter) > -1 ? "" : "none";
            }
        }
    }

    function sortTable(n) {
        let table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
        table = document.getElementById("accidentsTable");
        switching = true;
        dir = "asc";
        while (switching) {
            switching = false;
            rows = table.rows;
            for (i = 1; i < (rows.length - 1); i++) {
                if(rows[i].id === 'no-data-row') continue;
                shouldSwitch = false;
                x = rows[i].getElementsByTagName("TD")[n];
                y = rows[i + 1].getElementsByTagName("TD")[n];
                if (dir == "asc") {
                    if (x.innerText.toLowerCase() > y.innerText.toLowerCase()) { shouldSwitch = true; break; }
                } else if (dir == "desc") {
                    if (x.innerText.toLowerCase() < y.innerText.toLowerCase()) { shouldSwitch = true; break; }
                }
            }
            if (shouldSwitch) {
                rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                switching = true;
                switchcount++;
            } else {
                if (switchcount == 0 && dir == "asc") { dir = "desc"; switching = true; }
            }
        }
    }

    function openAddModal() {
        form.reset();
        document.getElementById("modalTitle").innerText = "New Accident Report";
        document.getElementById("submitBtn").innerText = "Submit Report";
        document.getElementById("is_edit").value = "0";
        modal.style.display = "flex";
    }

    function openEditModal(data) {
        document.getElementById("modalTitle").innerText = "Edit Accident Report";
        document.getElementById("submitBtn").innerText = "Update Report";
        document.getElementById("is_edit").value = "1";
        document.getElementById("form_accident_id").value = data.accident_id;
        document.getElementById("form_driver_id").value = data.driver_id;
        document.getElementById("form_plate_no").value = data.plate_no;
        document.getElementById("form_location").value = data.location;
        document.getElementById("form_severity").value = data.severity;
        document.getElementById("form_description").value = data.description;
        const dateVal = data.accident_date.replace(" ", "T").substring(0, 16);
        document.getElementById("form_accident_date").value = dateVal;
        modal.style.display = "flex";
    }

    function closeModal() { modal.style.display = "none"; }
    window.onclick = function(event) { if (event.target == modal) { closeModal(); } }
</script>

</body>
</html>