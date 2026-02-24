<?php
include('sidebar.php'); 
require_once("db.php");

$status = "";

// 1. Handle Delete Request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM violation_types WHERE type_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $status = "<div class='alert success'>Violation type deleted successfully!</div>";
    } else {
        $status = "<div class='alert error'>Error: Cannot delete this type. It may be linked to existing violation records.</div>";
    }
}

// 2. Handle Form Submission
if (isset($_POST['save_type'])) {
    $type_id = $_POST['type_id']; 
    $violation_name = trim($_POST['violation_name']);
    $fine_amount = $_POST['fine_amount'];
    $description = $_POST['description'];
    $is_edit = $_POST['is_edit']; 

    if ($is_edit == "1") {
        $check = $conn->prepare("SELECT type_id FROM violation_types WHERE violation_name = ? AND type_id != ?");
        $check->bind_param("si", $violation_name, $type_id);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $status = "<div class='alert error'>Error: A violation type with the name '$violation_name' already exists!</div>";
        } else {
            $stmt = $conn->prepare("UPDATE violation_types SET violation_name=?, fine_amount=?, description=? WHERE type_id=?");
            $stmt->bind_param("sdsi", $violation_name, $fine_amount, $description, $type_id);
            if ($stmt->execute()) {
                $status = "<div class='alert success'>Violation type updated successfully!</div>";
            }
        }
    } else {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Violation Types | BCTRMS</title>
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

        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; color: #666; font-size: 12px; text-transform: uppercase; }

        .btn-edit { color: #0059b3; cursor: pointer; font-size: 18px; border: none; background: none; }
        .btn-delete { color: #e74c3c; font-size: 18px; margin-left: 10px; }

        /* FIXED RESPONSIVE MODAL */
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 2000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5); 
            align-items: center; 
            justify-content: center;
            overflow-y: auto; 
            padding: 20px;
        }

        .modal-content { 
            background: #fff; 
            margin: auto; 
            border-radius: 15px; 
            width: 100%; 
            max-width: 450px; 
            overflow: hidden; 
            animation: slideDown 0.3s ease; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .modal-header { background-color: #0059b3; padding: 20px; color: white; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 25px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 14px; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; outline: none; }
        .btn-save { background: #0059b3; color: white; width: 100%; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }

        /* Alerts */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Mobile Breakpoint Fixes */
        @media (max-width: 768px) {
            .main-content { 
                margin-left: 70px !important; 
                width: calc(100% - 70px);
                padding: 20px 10px; 
            }
            .header h1 { font-size: 1.2rem; }
            .btn-add { width: 100%; }
            .modal { align-items: flex-start; } 
            .modal-content { margin-top: 20px; }
        }

        @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <h1>Violation Types</h1>
        <button class="btn-add" onclick="openAddModal()">+ Add New Type</button>
    </div>

    <?php echo $status; ?>

    <div class="table-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Violation Name</th>
                        <th>Fine Amount</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM violation_types ORDER BY violation_name ASC";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $json_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                            echo "<tr>
                                    <td><strong>{$row['violation_name']}</strong></td>
                                    <td>₱" . number_format($row['fine_amount'], 2) . "</td>
                                    <td>{$row['description']}</td>
                                    <td>
                                        <button class='btn-edit' onclick='openEditModal($json_data)' title='Edit'><i class='fa-solid fa-pen-to-square'></i></button>
                                        <a href='violation_types.php?delete={$row['type_id']}' onclick='return confirmDelete()'>
                                            <i class='fa-solid fa-trash btn-delete' title='Delete'></i>
                                        </a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' align='center'>No violation types defined yet.</td></tr>";
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
            <h2 id="modalTitle">New Violation Type</h2>
            <span onclick="closeModal()" style="cursor:pointer; font-size:24px;">&times;</span>
        </div>
        <div class="modal-body">
            <form action="violation_types.php" method="POST" id="typeForm">
                <input type="hidden" name="is_edit" id="is_edit" value="0">
                <input type="hidden" name="type_id" id="form_type_id">
                
                <div class="form-group">
                    <label>Violation Name</label>
                    <input type="text" name="violation_name" id="form_name" required placeholder="e.g. Illegal Parking">
                </div>
                <div class="form-group">
                    <label>Fine Amount (PHP)</label>
                    <input type="number" step="0.01" name="fine_amount" id="form_fine" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="form_desc" rows="3" placeholder="Brief details about this offense..."></textarea>
                </div>
                <button type="submit" name="save_type" id="submitBtn" class="btn-save">Save Violation Type</button>
            </form>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById("typeModal");
    const typeForm = document.getElementById("typeForm");

    function openAddModal() {
        typeForm.reset();
        document.getElementById("modalTitle").innerText = "New Violation Type";
        document.getElementById("submitBtn").innerText = "Save Violation Type";
        document.getElementById("is_edit").value = "0";
        modal.style.display = "flex"; /* Using flex for centering */
    }

    function openEditModal(data) {
        document.getElementById("modalTitle").innerText = "Edit Violation Type";
        document.getElementById("submitBtn").innerText = "Update Violation Type";
        document.getElementById("is_edit").value = "1";
        
        document.getElementById("form_type_id").value = data.type_id;
        document.getElementById("form_name").value = data.violation_name;
        document.getElementById("form_fine").value = data.fine_amount;
        document.getElementById("form_desc").value = data.description;
        
        modal.style.display = "flex"; /* Using flex for centering */
    }

    function closeModal() { modal.style.display = "none"; }
    function confirmDelete() { return confirm("Are you sure you want to delete this violation type?"); }
    window.onclick = function(event) { if (event.target == modal) { closeModal(); } }
</script>

</body>
</html>