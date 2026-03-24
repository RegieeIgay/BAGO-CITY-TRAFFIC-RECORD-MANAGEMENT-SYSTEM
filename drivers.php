<?php
include('sidebar.php'); 
require_once("db.php");

// Get role from URL for access control
$user_role = $_GET['role'] ?? 'User';
$role_param = "?role=" . urlencode($user_role);

// Check if user is Admin to restrict actions
$is_admin = (strtolower($user_role) === 'admin');

$status = "";
$upload_dir = "uploads/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 1. Handle Delete Request - Only if NOT Admin
if (isset($_GET['delete']) && !$is_admin) {
    $id = $_GET['delete'];
    $img_stmt = $conn->prepare("SELECT profile_image FROM drivers WHERE driver_id = ?");
    $img_stmt->bind_param("s", $id);
    $img_stmt->execute();
    $res = $img_stmt->get_result();
    if($row = $res->fetch_assoc()){
        if($row['profile_image'] != 'default-avatar.png' && file_exists($upload_dir . $row['profile_image'])){
            unlink($upload_dir . $row['profile_image']);
        }
    }
    $stmt = $conn->prepare("DELETE FROM drivers WHERE driver_id = ?");
    $stmt->bind_param("s", $id);
    if ($stmt->execute()) {
        $status = "<div class='alert success'>Driver deleted successfully!</div>";
    } else {
        $status = "<div class='alert error'>Error: Cannot delete driver. They may have active violations.</div>";
    }
}

// 2. Handle Form Submission - Only if NOT Admin
if (isset($_POST['save_driver']) && !$is_admin) {
    $driver_id = $_POST['driver_id'];
    $full_name = $_POST['full_name'];
    $license_no = $_POST['license_no'];
    $address = $_POST['address'];
    $contact_no = $_POST['contact_no'];
    $is_edit = $_POST['is_edit'];
    
    $image_name = $_POST['old_image'] ?? 'default-avatar.png';
    if (!empty($_FILES['profile_image']['name'])) {
        $file_ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_filename = "DRV_" . time() . "_" . rand(1000, 9999) . "." . $file_ext;
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_filename)) {
            if ($is_edit == "1" && $image_name != 'default-avatar.png' && file_exists($upload_dir . $image_name)) {
                unlink($upload_dir . $image_name);
            }
            $image_name = $new_filename;
        }
    }

    if ($is_edit == "1") {
        $stmt = $conn->prepare("UPDATE drivers SET full_name=?, license_no=?, address=?, contact_no=?, profile_image=? WHERE driver_id=?");
        $stmt->bind_param("ssssss", $full_name, $license_no, $address, $contact_no, $image_name, $driver_id);
        $msg = "Driver updated successfully!";
    } else {
        $check = $conn->prepare("SELECT driver_id FROM drivers WHERE driver_id = ?");
        $check->bind_param("s", $driver_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $status = "<div class='alert error'>Error: Driver ID already exists!</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO drivers (driver_id, full_name, license_no, address, contact_no, profile_image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $driver_id, $full_name, $license_no, $address, $contact_no, $image_name);
            $msg = "Driver registered successfully!";
        }
    }
    if (!empty($stmt) && $stmt->execute()) {
        $status = "<div class='alert success'>$msg</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Drivers | BCTRMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; }

        .main-content { 
            flex: 1; 
            margin-left: 260px; 
            padding: 40px 20px; 
            min-height: 100vh; 
            transition: all 0.3s ease; 
            width: calc(100% - 260px); 
        }

        body.sidebar-is-collapsed .main-content { margin-left: 70px; width: calc(100% - 70px); }

        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            flex-wrap: wrap; 
            gap: 15px;
        }

        .header h1 { font-size: 1.5rem; color: #003366; }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-container {
            position: relative;
            min-width: 250px;
        }
        .search-container input {
            width: 100%;
            padding: 10px 15px 10px 35px;
            border-radius: 8px;
            border: 1px solid #ddd;
            outline: none;
            font-size: 14px;
        }
        .search-container i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }

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

        .table-card { background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; color: #666; font-size: 12px; text-transform: uppercase; }
        
        .driver-thumb { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #eee; cursor: zoom-in; transition: 0.2s; }
        .driver-thumb:hover { transform: scale(1.1); border-color: #0059b3; }

        .lightbox { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); align-items: center; justify-content: center; cursor: zoom-out; }
        .lightbox img { max-width: 90%; max-height: 90%; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.5); animation: zoomIn 0.3s ease; }

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
        .modal-body { padding: 20px; }
        
        .preview-container { text-align: center; margin-bottom: 15px; }
        #img_preview { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #0059b3; margin-bottom: 5px; }
        
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; outline: none; }
        .btn-save { background: #0059b3; color: white; width: 100%; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; margin-top: 10px; }

        .btn-edit { color: #0059b3; cursor: pointer; font-size: 18px; }
        .btn-delete { color: #e74c3c; font-size: 18px; margin-left: 10px; }

        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }

        @media (max-width: 768px) {
            .main-content { 
                margin-left: 70px !important; 
                width: calc(100% - 70px); 
                padding: 20px 10px; 
            }
        }
        @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>

<div id="imageLightbox" class="lightbox" onclick="this.style.display='none'">
    <img id="expandedImg" src="">
</div>

<div class="main-content">
    <div class="header">
        <h1>Drivers Management</h1>
        <div class="header-actions">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search by Full Name..." onkeyup="filterTable()">
            </div>
            <?php if (!$is_admin): ?>
                <button class="btn-add" onclick="openAddModal()">+ Register New Driver</button>
            <?php endif; ?>
        </div>
    </div>

    <?php echo $status; ?>

    <div class="table-card">
        <div class="table-responsive">
            <table id="driversTable">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th onclick="sortTable(1)" style="cursor:pointer">Driver ID <i class="fa-solid fa-sort"></i></th>
                        <th onclick="sortTable(2)" style="cursor:pointer">Full Name <i class="fa-solid fa-sort"></i></th>
                        <th onclick="sortTable(3)" style="cursor:pointer">License No. <i class="fa-solid fa-sort"></i></th>
                        <th onclick="sortTable(4)" style="cursor:pointer">Contact <i class="fa-solid fa-sort"></i></th>
                        <?php if (!$is_admin): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM drivers ORDER BY full_name ASC";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $img_path = $upload_dir . $row['profile_image'];
                            $display_img = (file_exists($img_path)) ? $img_path : $upload_dir . 'default-avatar.png';
                            $json_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                            echo "<tr>
                                    <td><img src='$display_img' class='driver-thumb' onclick='expandImage(\"$display_img\")'></td>
                                    <td><strong>{$row['driver_id']}</strong></td>
                                    <td class='name-cell'>{$row['full_name']}</td>
                                    <td>{$row['license_no']}</td>
                                    <td>{$row['contact_no']}</td>";
                            
                            if (!$is_admin) {
                                echo "<td>
                                        <i class='fa-solid fa-pen-to-square btn-edit' onclick='openEditModal($json_data)'></i>
                                        <a href='drivers.php{$role_param}&delete={$row['driver_id']}' onclick='return confirmDelete()'>
                                            <i class='fa-solid fa-trash btn-delete'></i>
                                        </a>
                                    </td>";
                            }
                            
                            echo "</tr>";
                        }
                    } else {
                        $colspan = $is_admin ? 5 : 6;
                        echo "<tr class='no-data'><td colspan='$colspan' align='center'>No drivers registered yet.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="driverModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">New Driver</h2>
            <span onclick="closeModal()" style="cursor:pointer; font-size:24px;">&times;</span>
        </div>
        <div class="modal-body">
            <form action="drivers.php<?php echo $role_param; ?>" method="POST" id="driverForm" enctype="multipart/form-data">
                <input type="hidden" name="is_edit" id="is_edit" value="0">
                <input type="hidden" name="old_image" id="old_image" value="default-avatar.png">
                <div class="preview-container">
                    <img id="img_preview" src="uploads/default-avatar.png">
                    <div class="form-group">
                        <label>Upload Photo</label>
                        <input type="file" name="profile_image" id="file_input" accept="image/*">
                    </div>
                </div>
                <div class="form-group"><label>Driver ID</label><input type="text" name="driver_id" id="form_driver_id" required></div>
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" id="form_full_name" required></div>
                <div class="form-group"><label>License No.</label><input type="text" name="license_no" id="form_license_no" required></div>
                <div class="form-group"><label>Contact No.</label><input type="text" name="contact_no" id="form_contact_no" required></div>
                <div class="form-group"><label>Address</label><textarea name="address" id="form_address" rows="2"></textarea></div>
                <button type="submit" name="save_driver" id="submitBtn" class="btn-save">Register Driver</button>
            </form>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById("driverModal");
    const driverForm = document.getElementById("driverForm");
    const fileInput = document.getElementById("file_input");
    const imgPreview = document.getElementById("img_preview");
    const lightbox = document.getElementById("imageLightbox");
    const expandedImg = document.getElementById("expandedImg");

    function filterTable() {
        const input = document.getElementById("searchInput");
        const filter = input.value.toLowerCase();
        const table = document.getElementById("driversTable");
        const tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            if(tr[i].classList.contains('no-data')) continue;
            const nameCell = tr[i].getElementsByClassName("name-cell")[0];
            if (nameCell) {
                const txtValue = nameCell.textContent || nameCell.innerText;
                tr[i].style.display = (txtValue.toLowerCase().indexOf(filter) > -1) ? "" : "none";
            }
        }
    }

    function sortTable(n) {
        var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
        table = document.getElementById("driversTable");
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

    function expandImage(src) {
        expandedImg.src = src;
        lightbox.style.display = 'flex';
    }

    if(fileInput) {
        fileInput.onchange = evt => {
            const [file] = fileInput.files;
            if (file) { imgPreview.src = URL.createObjectURL(file); }
        }
    }

    function openAddModal() {
        driverForm.reset();
        imgPreview.src = "uploads/default-avatar.png";
        document.getElementById("modalTitle").innerText = "New Driver";
        document.getElementById("submitBtn").innerText = "Register Driver";
        document.getElementById("is_edit").value = "0";
        document.getElementById("form_driver_id").readOnly = false;
        modal.style.display = "flex";
    }

    function openEditModal(data) {
        document.getElementById("modalTitle").innerText = "Edit Driver";
        document.getElementById("submitBtn").innerText = "Update Driver";
        document.getElementById("is_edit").value = "1";
        document.getElementById("old_image").value = data.profile_image;
        document.getElementById("form_driver_id").value = data.driver_id;
        document.getElementById("form_driver_id").readOnly = true;
        document.getElementById("form_full_name").value = data.full_name;
        document.getElementById("form_license_no").value = data.license_no;
        document.getElementById("form_contact_no").value = data.contact_no;
        document.getElementById("form_address").value = data.address;
        imgPreview.src = "uploads/" + (data.profile_image || "default-avatar.png");
        modal.style.display = "flex";
    }

    function closeModal() { modal.style.display = "none"; }
    function confirmDelete() { return confirm("Are you sure you want to delete this driver?"); }
    window.onclick = function(event) { if (event.target == modal) { closeModal(); } }
</script>

</body>
</html>