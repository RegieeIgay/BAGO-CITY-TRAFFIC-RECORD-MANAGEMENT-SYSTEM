<?php
include('sidebar.php'); 
require_once("db.php");

// 1. Setup role parameters for navigation
$user_role = $_GET['role'] ?? 'User';
$role_param = "?role=" . urlencode($user_role);

// RESTRICTION LOGIC: Check if the user is an Admin
$is_admin = (strtolower($user_role) === 'admin');

$status = "";

// 2. Handle Delete Request - RESTRICTED IF ADMIN
if (isset($_GET['delete'])) {
    if ($is_admin) {
        $status = "<div class='alert error'>Access Denied: Admin accounts cannot delete violation types.</div>";
    } else {
        $id = $_GET['delete'];
        
        // Safety Check: Check 'violations' table to see if this type is in use
        // Based on your database screenshot, the column is named 'violation_id'
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM violations WHERE violation_id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result()->fetch_row();

        if ($check_result[0] > 0) {
            $status = "<div class='alert error'>Error: Cannot delete. This violation type is linked to existing violation records.</div>";
        } else {
            // If not linked, proceed with deletion
            $stmt = $conn->prepare("DELETE FROM violation_types WHERE type_id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $status = "<div class='alert success'>Violation type deleted successfully!</div>";
            } else {
                $status = "<div class='alert error'>Error: Operation failed.</div>";
            }
        }
    }
}

// 3. Handle Form Submission (Add/Edit) - RESTRICTED IF ADMIN
if (isset($_POST['save_type'])) {
    if ($is_admin) {
        $status = "<div class='alert error'>Access Denied: Admin accounts cannot add or edit violation types.</div>";
    } else {
        $type_id = $_POST['type_id']; 
        $violation_name = trim($_POST['violation_name']);
        $fine_amount = $_POST['fine_amount'];
        $description = $_POST['description'];
        $is_edit = $_POST['is_edit']; 

        if ($is_edit == "1") {
            // Check for duplicates excluding current ID
            $check = $conn->prepare("SELECT type_id FROM violation_types WHERE violation_name = ? AND type_id != ?");
            $check->bind_param("si", $violation_name, $type_id);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                $status = "<div class='alert error'>Error: A violation with the name '$violation_name' already exists!</div>";
            } else {
                $stmt = $conn->prepare("UPDATE violation_types SET violation_name=?, fine_amount=?, description=? WHERE type_id=?");
                $stmt->bind_param("sdsi", $violation_name, $fine_amount, $description, $type_id);
                if ($stmt->execute()) {
                    $status = "<div class='alert success'>Violation type updated successfully!</div>";
                }
            }
        } else {
            // Check for duplicates
            $check = $conn->prepare("SELECT type_id FROM violation_types WHERE violation_name = ?");
            $check->bind_param("s", $violation_name);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                $status = "<div class='alert error'>Error: The violation '$violation_name' is already registered!</div>";
            } else {
                $stmt = $conn->prepare("INSERT INTO violation_types (violation_name, fine_amount, description) VALUES (?, ?, ?)");
                $stmt->bind_param("sds", $violation_name, $fine_amount, $description);
                if ($stmt->execute()) {
                    $status = "<div class='alert success'>New violation type added successfully!</div>";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Violation Types Setup | BCTRMS</title>
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

        .header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        
        .search-container { position: relative; min-width: 250px; }
        .search-container input {
            width: 100%; padding: 10px 15px 10px 35px;
            border-radius: 8px; border: 1px solid #ddd; outline: none; font-size: 14px;
        }
        .search-container i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #888; }

        .btn-add { 
            background: #003366; color: white; padding: 10px 20px; 
            border: none; border-radius: 8px; cursor: pointer; 
            font-weight: 600; transition: 0.3s; white-space: nowrap;
        }

        .table-card { background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table-responsive { width: 100%; overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; color: #666; font-size: 12px; text-transform: uppercase; cursor: pointer; }
        th:hover { color: #003366; }

        .btn-edit { color: #0059b3; cursor: pointer; font-size: 18px; }
        .btn-delete { color: #e74c3c; cursor: pointer; font-size: 18px; margin-left: 10px; }

        .modal { 
            display: none; position: fixed; z-index: 2000; left: 0; top: 0; 
            width: 100%; height: 100%; background: rgba(0,0,0,0.5); 
            align-items: center; justify-content: center; padding: 20px;
        }

        .modal-content { 
            background: #fff; border-radius: 15px; width: 100%; 
            max-width: 450px; overflow: hidden; animation: slideDown 0.3s ease; 
        }

        .modal-header { background-color: #0059b3; padding: 20px; color: white; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 25px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; outline: none; }
        .btn-save { background: #0059b3; color: white; width: 100%; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }

        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }

        @media (max-width: 768px) {
            .main-content { margin-left: 70px !important; width: calc(100% - 70px); padding: 20px 10px; }
        }
        @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <h1>Violation Types Setup</h1>
        <div class="header-actions">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search violation types..." onkeyup="filterTable()">
            </div>
            <?php if (!$is_admin): ?>
                <button class="btn-add" onclick="openAddModal()">+ Add New Type</button>
            <?php endif; ?>
        </div>
    </div>

    <?php echo $status; ?>

    <div class="table-card">
        <div class="table-responsive">
            <table id="typeTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)">Violation Name <i class="fa-solid fa-sort"></i></th>
                        <th onclick="sortTable(1)">Fine Amount <i class="fa-solid fa-sort"></i></th>
                        <th onclick="sortTable(2)">Description <i class="fa-solid fa-sort"></i></th>
                        <?php if (!$is_admin): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM violation_types ORDER BY violation_name ASC";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $json_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                            echo "<tr>
                                    <td><strong>{$row['violation_name']}</strong></td>
                                    <td>₱" . number_format($row['fine_amount'], 2) . "</td>
                                    <td>{$row['description']}</td>";
                            
                            if (!$is_admin) {
                                echo "<td>
                                        <i class='fa-solid fa-pen-to-square btn-edit' onclick='openEditModal($json_data)' title='Edit'></i>
                                        <a href='violation_types.php{$role_param}&delete={$row['type_id']}' onclick='return confirm(\"Delete this violation type?\")'>
                                            <i class='fa-solid fa-trash btn-delete' title='Delete'></i>
                                        </a>
                                    </td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr class='no-data'><td colspan='4' align='center'>No violation types defined.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="typeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Violation Type</h2>
            <span onclick="closeModal()" style="cursor:pointer; font-size:24px;">&times;</span>
        </div>
        <div class="modal-body">
            <form action="violation_types.php<?php echo $role_param; ?>" method="POST" id="typeForm">
                <input type="hidden" name="is_edit" id="is_edit" value="0">
                <input type="hidden" name="type_id" id="form_type_id">
                
                <div class="form-group">
                    <label>Violation Name</label>
                    <input type="text" name="violation_name" id="form_name" placeholder="e.g. Speeding, No Helmet" required>
                </div>

                <div class="form-group">
                    <label>Fine Amount (PHP)</label>
                    <input type="number" step="0.01" name="fine_amount" id="form_fine" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label>Description (Optional)</label>
                    <textarea name="description" id="form_description" rows="3"></textarea>
                </div>

                <button type="submit" name="save_type" id="submitBtn" class="btn-save">Save Violation Type</button>
            </form>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById("typeModal");
    const typeForm = document.getElementById("typeForm");

    function filterTable() {
        const input = document.getElementById("searchInput");
        const filter = input.value.toLowerCase();
        const table = document.getElementById("typeTable");
        const tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            if(tr[i].classList.contains('no-data')) continue;
            let textValue = tr[i].textContent || tr[i].innerText;
            tr[i].style.display = (textValue.toLowerCase().indexOf(filter) > -1) ? "" : "none";
        }
    }

    function sortTable(n) {
        var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
        table = document.getElementById("typeTable");
        switching = true;
        dir = "asc";
        while (switching) {
            switching = false;
            rows = table.rows;
            for (i = 1; i < (rows.length - 1); i++) {
                if(rows[i].classList.contains('no-data')) continue;
                shouldSwitch = false;
                x = rows[i].getElementsByTagName("TD")[n];
                y = rows[i + 1].getElementsByTagName("TD")[n];
                
                let xVal = x.innerText.toLowerCase();
                let yVal = y.innerText.toLowerCase();
                
                if(n === 1) { 
                    xVal = parseFloat(xVal.replace(/[^\d.]/g, '')) || 0;
                    yVal = parseFloat(yVal.replace(/[^\d.]/g, '')) || 0;
                }

                if (dir == "asc") {
                    if (xVal > yVal) { shouldSwitch = true; break; }
                } else {
                    if (xVal < yVal) { shouldSwitch = true; break; }
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
        typeForm.reset();
        document.getElementById("modalTitle").innerText = "Add Violation Type";
        document.getElementById("submitBtn").innerText = "Save Violation Type";
        document.getElementById("is_edit").value = "0";
        modal.style.display = "flex";
    }

    function openEditModal(data) {
        document.getElementById("modalTitle").innerText = "Edit Violation Type";
        document.getElementById("submitBtn").innerText = "Update Violation Type";
        document.getElementById("is_edit").value = "1";
        document.getElementById("form_type_id").value = data.type_id;
        document.getElementById("form_name").value = data.violation_name;
        document.getElementById("form_fine").value = data.fine_amount;
        document.getElementById("form_description").value = data.description;
        modal.style.display = "flex";
    }

    function closeModal() { modal.style.display = "none"; }
    window.onclick = function(e) { if (e.target == modal) closeModal(); }
</script>

</body>
</html>