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
    $v_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM violations WHERE violation_id = ?");
    $stmt->bind_param("i", $v_id);
    
    if ($stmt->execute()) {
        $status = "<div class='alert success'>Violation record deleted successfully!</div>";
    } else {
        $status = "<div class='alert error'>Error: Could not delete record.</div>";
    }
}

// 3. Handle Form Submission - Only if NOT Admin
if (isset($_POST['save_violation']) && !$is_admin) {
    $driver_id = $_POST['driver_id'];
    $plate_no = $_POST['plate_no'];
    $violation_type = $_POST['violation_type'];
    $violation_date = $_POST['violation_date'];
    $recorded_by = $_SESSION['user_id'] ?? 1; 
    $is_edit = $_POST['is_edit'];
    $violation_id = $_POST['violation_id'] ?? null;

    if ($is_edit == "1" && $violation_id) {
        $stmt = $conn->prepare("UPDATE violations SET driver_id=?, plate_no=?, violation_type=?, violation_date=? WHERE violation_id=?");
        $stmt->bind_param("ssssi", $driver_id, $plate_no, $violation_type, $violation_date, $violation_id);
        $msg = "Violation updated successfully!";
    } else {
        $stmt = $conn->prepare("INSERT INTO violations (driver_id, plate_no, violation_type, violation_date, recorded_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $driver_id, $plate_no, $violation_type, $violation_date, $recorded_by);
        $msg = "Violation recorded successfully!";
    }

    if ($stmt->execute()) {
        $status = "<div class='alert success'>$msg</div>";
    } else {
        $status = "<div class='alert error'>Error: Operation failed.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traffic Violations | BCTRMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; }

        .main-content { 
            flex: 1; margin-left: 260px; padding: 40px 20px; 
            min-height: 100vh; transition: all 0.3s ease; 
            width: calc(100% - 260px);
        }

        body.sidebar-is-collapsed .main-content { margin-left: 70px; width: calc(100% - 70px); }

        .header { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-bottom: 30px; flex-wrap: wrap; gap: 15px;
        }

        .header h1 { font-size: 1.5rem; color: #003366; }

        .search-container {
            display: flex; gap: 10px; align-items: center; flex: 1; max-width: 400px;
        }
        .search-box {
            position: relative; width: 100%;
        }
        .search-box input {
            width: 100%; padding: 10px 15px 10px 35px;
            border: 1px solid #ddd; border-radius: 8px; outline: none;
        }
        .search-box i {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #888;
        }

        .btn-add { 
            background: #003366; color: white; padding: 10px 20px; 
            border: none; border-radius: 8px; cursor: pointer; 
            font-weight: 600; transition: 0.3s; white-space: nowrap;
        }

        .table-card { background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table-responsive { width: 100%; overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; min-width: 850px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; color: #666; font-size: 12px; text-transform: uppercase; cursor: pointer; }
        th:hover { color: #003366; }

        .btn-edit { color: #0059b3; cursor: pointer; font-size: 18px; border: none; background: none; }
        .btn-delete { color: #e74c3c; font-size: 18px; margin-left: 10px; }

        .modal { 
            display: none; position: fixed; z-index: 2000; left: 0; top: 0; 
            width: 100%; height: 100%; background: rgba(0,0,0,0.5); 
            align-items: center; justify-content: center; overflow-y: auto; padding: 20px;
        }

        .modal-content { 
            background: #fff; border-radius: 15px; width: 100%; 
            max-width: 480px; overflow: hidden; animation: slideDown 0.3s ease; 
        }

        .modal-header { background-color: #0059b3; padding: 20px; color: white; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 25px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 14px; font-weight: 500; }
        .form-group select, .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; outline: none; }
        .btn-save { background: #0059b3; color: white; width: 100%; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }

        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <h1>Traffic Violations</h1>
        
        <div class="search-container">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="driverSearch" placeholder="Search by driver's name..." onkeyup="filterByDriver()">
            </div>
        </div>

        <?php if (!$is_admin): ?>
            <button class="btn-add" onclick="openAddModal()">+ Record New Violation</button>
        <?php endif; ?>
    </div>

    <?php echo $status; ?>

    <div class="table-card">
        <div class="table-responsive">
            <table id="violationsTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)">Driver Name <i class="fa-solid fa-sort"></i></th>
                        <th onclick="sortTable(1)">Plate No. <i class="fa-solid fa-sort"></i></th>
                        <th onclick="sortTable(2)">Violation Type <i class="fa-solid fa-sort"></i></th>
                        <th onclick="sortTable(3)">Date & Time <i class="fa-solid fa-sort"></i></th>
                        <?php if (!$is_admin): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT v.*, d.full_name 
                            FROM violations v 
                            LEFT JOIN drivers d ON v.driver_id = d.driver_id 
                            ORDER BY v.violation_id DESC";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $json_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                            echo "<tr>
                                    <td class='driver-name'>" . ($row['full_name'] ?? $row['driver_id']) . "</td>
                                    <td><strong>{$row['plate_no']}</strong></td>
                                    <td>{$row['violation_type']}</td>
                                    <td>" . date('M d, Y h:i A', strtotime($row['violation_date'])) . "</td>";
                            
                            if (!$is_admin) {
                                echo "<td>
                                        <button class='btn-edit' onclick='openEditModal($json_data)' title='Edit'><i class='fa-solid fa-pen-to-square'></i></button>
                                        <a href='violations.php{$role_param}&delete={$row['violation_id']}' onclick='return confirm(\"Delete this record?\")'>
                                            <i class='fa-solid fa-trash btn-delete' title='Delete'></i>
                                        </a>
                                    </td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        $colspan = $is_admin ? 4 : 5;
                        echo "<tr id='noData'><td colspan='$colspan' align='center'>No violations recorded yet.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="violationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">New Violation</h2>
            <span onclick="closeModal()" style="cursor:pointer; font-size:24px;">&times;</span>
        </div>
        <div class="modal-body">
            <form action="violations.php<?php echo $role_param; ?>" method="POST" id="violationForm">
                <input type="hidden" name="is_edit" id="is_edit" value="0">
                <input type="hidden" name="violation_id" id="form_violation_id">

                <div class="form-group">
                    <label>Driver</label>
                    <select name="driver_id" id="form_driver_id" required>
                        <option value="">-- Select Driver --</option>
                        <?php
                        $drivers = $conn->query("SELECT driver_id, full_name FROM drivers ORDER BY full_name ASC");
                        while($d = $drivers->fetch_assoc()) {
                            echo "<option value='{$d['driver_id']}'>{$d['full_name']} ({$d['driver_id']})</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Vehicle (Plate No.)</label>
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
                    <label>Violation Type</label>
                    <select name="violation_type" id="form_violation_type" required>
                        <option value="">-- Select Type --</option>
                        <?php
                        $types = $conn->query("SELECT violation_name FROM violation_types ORDER BY violation_name ASC");
                        while($t = $types->fetch_assoc()) {
                            $v_name = htmlspecialchars($t['violation_name']);
                            echo "<option value='$v_name'>$v_name</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date & Time</label>
                    <input type="datetime-local" name="violation_date" id="form_violation_date" required>
                </div>

                <button type="submit" name="save_violation" id="submitBtn" class="btn-save">Save Violation Record</button>
            </form>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById("violationModal");
    const form = document.getElementById("violationForm");

    function filterByDriver() {
        const input = document.getElementById("driverSearch");
        const filter = input.value.toLowerCase();
        const table = document.getElementById("violationsTable");
        const tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            const td = tr[i].getElementsByClassName("driver-name")[0];
            if (td) {
                const txtValue = td.textContent || td.innerText;
                tr[i].style.display = (txtValue.toLowerCase().indexOf(filter) > -1) ? "" : "none";
            }
        }
    }

    function sortTable(n) {
        let table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
        table = document.getElementById("violationsTable");
        switching = true;
        dir = "asc";
        while (switching) {
            switching = false;
            rows = table.rows;
            for (i = 1; i < (rows.length - 1); i++) {
                if(rows[i].id === 'noData') continue;
                shouldSwitch = false;
                x = rows[i].getElementsByTagName("TD")[n];
                y = rows[i + 1].getElementsByTagName("TD")[n];
                if (dir == "asc") {
                    if (x.innerText.toLowerCase() > y.innerText.toLowerCase()) { shouldSwitch = true; break; }
                } else {
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
        document.getElementById("modalTitle").innerHTML = '<i class="fa-solid fa-file-circle-plus"></i> New Violation';
        document.getElementById("submitBtn").innerText = "Save Violation Record";
        document.getElementById("is_edit").value = "0";
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById("form_violation_date").value = now.toISOString().slice(0, 16);
        modal.style.display = "flex";
    }

    function openEditModal(data) {
        document.getElementById("modalTitle").innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Edit Violation';
        document.getElementById("submitBtn").innerText = "Update Violation Record";
        document.getElementById("is_edit").value = "1";
        document.getElementById("form_violation_id").value = data.violation_id;
        document.getElementById("form_driver_id").value = data.driver_id;
        document.getElementById("form_plate_no").value = data.plate_no;
        document.getElementById("form_violation_type").value = data.violation_type;
        const dateVal = data.violation_date.replace(" ", "T").substring(0, 16);
        document.getElementById("form_violation_date").value = dateVal;
        modal.style.display = "flex";
    }

    function closeModal() { modal.style.display = "none"; }
    window.onclick = function(event) { if (event.target == modal) closeModal(); }
</script>

</body>
</html>