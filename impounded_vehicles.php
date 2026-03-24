<?php
include('sidebar.php'); 
require_once("db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Get role and set Admin restriction
$current_user_id = $_SESSION['user_id'] ?? 11; 
$user_role = $_GET['role'] ?? 'User';
$role_param = "?role=" . urlencode($user_role);
$is_admin = (strtolower($user_role) === 'admin');

$status_msg = "";

// 2. Handle Form Submission - Only if NOT Admin
if (isset($_POST['save_impound']) && !$is_admin) {
    $plate_no = $_POST['plate_no'];
    $driver_id = $_POST['driver_id'];
    $violation_id = $_POST['violation_id']; 
    $impound_date = $_POST['impound_date'];
    $status = $_POST['status'];
    $location = $_POST['location'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $is_edit = $_POST['is_edit'];
    $impound_id = $_POST['impound_id'] ?? null;
    $release_date = ($status == 'Released') ? $_POST['release_date'] : null;

    if ($is_edit == "1") {
        $stmt = $conn->prepare("UPDATE impounded_vehicles SET plate_no=?, driver_id=?, violation_id=?, impound_date=?, release_date=?, status=?, location=?, notes=? WHERE impound_id=?");
        $stmt->bind_param("siisssssi", $plate_no, $driver_id, $violation_id, $impound_date, $release_date, $status, $location, $notes, $impound_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO impounded_vehicles (plate_no, driver_id, enforcer_id, violation_id, impound_date, release_date, status, location, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiisssss", $plate_no, $driver_id, $current_user_id, $violation_id, $impound_date, $release_date, $status, $location, $notes);
    }

    if ($stmt->execute()) {
        $status_msg = "<div class='alert success'>Record updated successfully!</div>";
    } else {
        $status_msg = "<div class='alert error'>Error: " . $conn->error . "</div>";
    }
}

// 3. Handle Deletion - Only if NOT Admin
if (isset($_GET['delete_id']) && !$is_admin) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM impounded_vehicles WHERE impound_id = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        $status_msg = "<div class='alert success'>Record deleted successfully!</div>";
    } else {
        $status_msg = "<div class='alert error'>Error deleting record: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impounded Vehicles | BCTRMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; }

        .main-content { 
            flex: 1; margin-left: 260px; padding: 40px 20px; 
            min-height: 100vh; transition: all 0.3s ease; 
            width: calc(100% - 260px);
        }

        .header { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-bottom: 30px; flex-wrap: wrap; gap: 15px;
        }

        .header h1 { font-size: 1.5rem; color: #003366; }

        .search-container { display: flex; gap: 10px; align-items: center; flex: 1; max-width: 400px; }
        .search-box { position: relative; width: 100%; }
        .search-box input {
            width: 100%; padding: 10px 15px 10px 35px;
            border: 1px solid #ddd; border-radius: 8px; outline: none;
        }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #888; }

        .btn-add { 
            background: #003366; color: white; padding: 10px 20px; 
            border: none; border-radius: 8px; cursor: pointer; 
            font-weight: 600; transition: 0.3s; white-space: nowrap;
        }

        .table-card { background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table-responsive { width: 100%; overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; min-width: 850px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; color: #666; font-size: 11px; text-transform: uppercase; cursor: pointer; letter-spacing: 0.5px; }
        
        .plate-box {
            background: #333; color: #fff; padding: 4px 8px; border-radius: 4px;
            font-family: 'Courier New', Courier, monospace; font-weight: bold;
            letter-spacing: 1px; border: 2px solid #000;
        }

        .badge { padding: 5px 10px; border-radius: 4px; font-weight: 600; font-size: 11px; display: inline-block; }
        .badge-imp { background: #fee2e2; color: #dc2626; }
        .badge-rel { background: #dcfce7; color: #16a34a; }

        .info-text { color: #666; font-size: 13px; }
        .info-text i { margin-right: 5px; color: #0059b3; width: 15px; }

        .btn-edit { color: #0059b3; cursor: pointer; font-size: 18px; border: none; background: none; }
        .btn-delete { color: #dc2626; cursor: pointer; font-size: 18px; border: none; background: none; margin-left: 10px; }

        .modal { 
            display: none; position: fixed; z-index: 2000; left: 0; top: 0; 
            width: 100%; height: 100%; background: rgba(0,0,0,0.5); 
            align-items: center; justify-content: center; padding: 20px;
        }
        .modal-content { background: #fff; border-radius: 15px; width: 100%; max-width: 500px; overflow: hidden; animation: slideDown 0.3s ease; }
        .modal-header { background-color: #0059b3; padding: 20px; color: white; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 25px; max-height: 80vh; overflow-y: auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 14px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-save { background: #0059b3; color: white; width: 100%; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; margin-top: 10px;}

        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <h1>Impounded Vehicles</h1>
        <div class="search-container">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="tableSearch" placeholder="Search plate or driver..." onkeyup="filterTable()">
            </div>
        </div>
        <?php if (!$is_admin): ?>
            <button class="btn-add" onclick="openAddModal()">+ New Record</button>
        <?php endif; ?>
    </div>

    <?php echo $status_msg; ?>

    <div class="table-card">
        <div class="table-responsive">
            <table id="impoundTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)">Plate No. <i class="fa fa-sort"></i></th>
                        <th onclick="sortTable(1)">Driver <i class="fa fa-sort"></i></th>
                        <th onclick="sortTable(2)">Violation <i class="fa fa-sort"></i></th>
                        <th onclick="sortTable(3)">Impound Date <i class="fa fa-sort"></i></th>
                        <th onclick="sortTable(4)">Impounded By <i class="fa fa-sort"></i></th>
                        <th onclick="sortTable(5)">Status <i class="fa fa-sort"></i></th>
                        <?php if (!$is_admin): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT i.*, d.full_name as driver_name, u.full_name as enforcer_name, vt.violation_name 
                            FROM impounded_vehicles i 
                            LEFT JOIN drivers d ON i.driver_id = d.driver_id 
                            LEFT JOIN users u ON i.enforcer_id = u.user_id
                            LEFT JOIN violation_types vt ON i.violation_id = vt.type_id
                            ORDER BY i.impound_date DESC";
                    
                    $result = $conn->query($sql);
                    while($row = $result->fetch_assoc()) {
                        $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        $st_cls = ($row['status'] == 'Released') ? 'badge-rel' : 'badge-imp';

                        echo "<tr>
                            <td><span class='plate-box'>{$row['plate_no']}</span></td>
                            <td><div style='font-weight:600;'>{$row['driver_name']}</div></td>
                            <td><div style='font-size:13px; color:#555;'>{$row['violation_name']}</div></td>
                            <td>
                                <div class='info-text'><i class='fa-regular fa-calendar'></i>" . date('M d, Y', strtotime($row['impound_date'])) . "</div>
                            </td>
                            <td>
                                <div class='info-text'><i class='fa-solid fa-user-check'></i>" . ($row['enforcer_name'] ?? 'System Admin') . "</div>
                            </td>
                            <td><span class='badge $st_cls'>{$row['status']}</span></td>";
                        
                        if (!$is_admin) {
                            echo "<td>
                                <button class='btn-edit' onclick='openEditModal($json)' title='Edit'>
                                    <i class='fa-solid fa-pen-to-square'></i>
                                </button>
                                <button class='btn-delete' onclick='confirmDelete({$row['impound_id']})' title='Delete'>
                                    <i class='fa-solid fa-trash-can'></i>
                                </button>
                            </td>";
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="impoundModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">New Entry</h2>
            <span onclick="closeModal()" style="cursor:pointer; font-size:24px;">&times;</span>
        </div>
        <div class="modal-body">
            <form action="" method="POST" id="impoundForm">
                <input type="hidden" name="is_edit" id="is_edit" value="0">
                <input type="hidden" name="impound_id" id="impound_id">
                
                <div class="form-group">
                    <label>Plate Number</label>
                    <select name="plate_no" id="form_plate" required>
                        <option value="">-- Select --</option>
                        <?php
                        $plates = $conn->query("SELECT plate_no FROM vehicles");
                        while($p = $plates->fetch_assoc()) echo "<option value='{$p['plate_no']}'>{$p['plate_no']}</option>";
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Driver</label>
                    <select name="driver_id" id="form_driver" required>
                        <option value="">-- Select Driver --</option>
                        <?php
                        $drivers = $conn->query("SELECT driver_id, full_name FROM drivers");
                        while($d = $drivers->fetch_assoc()) echo "<option value='{$d['driver_id']}'>{$d['full_name']}</option>";
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Violation</label>
                    <select name="violation_id" id="form_violation" required>
                        <?php
                        $viols = $conn->query("SELECT type_id, violation_name FROM violation_types");
                        while($v = $viols->fetch_assoc()) echo "<option value='{$v['type_id']}'>{$v['violation_name']}</option>";
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Impound Date</label>
                    <input type="date" name="impound_date" id="form_impound_date" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="form_status" onchange="toggleReleaseField(this.value)">
                        <option value="Impounded">Impounded</option>
                        <option value="Released">Released</option>
                    </select>
                </div>
                <div class="form-group" id="release_date_group" style="display:none;">
                    <label>Release Date</label>
                    <input type="date" name="release_date" id="form_release_date">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" id="form_location">
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" id="form_notes" rows="2"></textarea>
                </div>
                <button type="submit" name="save_impound" class="btn-save">Save Record</button>
            </form>
        </div>
    </div>
</div>

<script>
    function filterTable() {
        let input = document.getElementById("tableSearch").value.toLowerCase();
        let rows = document.querySelector("#impoundTable tbody").rows;
        for (let row of rows) {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(input) ? "" : "none";
        }
    }

    function sortTable(n) {
        let table = document.getElementById("impoundTable");
        let switching = true, i, x, y, shouldSwitch, dir = "asc", switchcount = 0;
        while (switching) {
            switching = false;
            let rows = table.rows;
            for (i = 1; i < (rows.length - 1); i++) {
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
            } else if (switchcount == 0 && dir == "asc") {
                dir = "desc";
                switching = true;
            }
        }
    }

    function toggleReleaseField(val) {
        const group = document.getElementById('release_date_group');
        group.style.display = (val === 'Released') ? 'block' : 'none';
        if(val !== 'Released') document.getElementById('form_release_date').value = '';
    }

    function openAddModal() {
        document.getElementById("impoundForm").reset();
        document.getElementById("is_edit").value = "0";
        document.getElementById("modalTitle").innerText = "New Impound Record";
        document.getElementById("impoundModal").style.display = "flex";
        toggleReleaseField('Impounded');
    }

    function openEditModal(data) {
        document.getElementById("is_edit").value = "1";
        document.getElementById("impound_id").value = data.impound_id;
        document.getElementById("form_plate").value = data.plate_no;
        document.getElementById("form_driver").value = data.driver_id;
        document.getElementById("form_violation").value = data.violation_id;
        document.getElementById("form_impound_date").value = data.impound_date;
        document.getElementById("form_status").value = data.status;
        document.getElementById("form_location").value = data.location;
        document.getElementById("form_notes").value = data.notes;
        toggleReleaseField(data.status);
        if(data.status === 'Released') document.getElementById("form_release_date").value = data.release_date;
        document.getElementById("modalTitle").innerText = "Edit Impound Record";
        document.getElementById("impoundModal").style.display = "flex";
    }

    function confirmDelete(id) {
        if (confirm("Are you sure you want to delete this impound record?")) {
            const urlParams = new URLSearchParams(window.location.search);
            const role = urlParams.get('role') || 'User';
            window.location.href = `?role=${role}&delete_id=${id}`;
        }
    }

    function closeModal() { document.getElementById("impoundModal").style.display = "none"; }
    window.onclick = function(event) { if (event.target == document.getElementById("impoundModal")) closeModal(); }
</script>
</body>
</html>