<?php
include('sidebar.php'); 
require_once("db.php");

$status = "";
$upload_dir = "uploads/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 1. Handle Delete Request
if (isset($_GET['delete'])) {
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

// 2. Handle Form Submission
if (isset($_POST['save_driver'])) {
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
        .main-content { flex: 1; margin-left: 260px; padding: 40px 20px; min-height: 100vh; transition: 0.3s; width: calc(100% - 260px); }
        body.sidebar-is-collapsed .main-content { margin-left: 70px; width: calc(100% - 70px); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { font-size: 1.5rem; color: #003366; }
        .btn-add { background: #003366; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .table-card { background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; color: #666; font-size: 12px; text-transform: uppercase; }
        
        /* Image Thumbnail Style */
        .driver-thumb { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #eee; cursor: zoom-in; transition: 0.2s; }
        .driver-thumb:hover { transform: scale(1.1); border-color: #0059b3; }

        /* Lightbox (Expanded Image) Style */
        .lightbox { display: none; position: fixed; z-index: 3000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); align-items: center; justify-content: center; cursor: zoom-out; }
        .lightbox img { max-width: 90%; max-height: 90%; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.5); animation: zoomIn 0.3s ease; }

        /* Registration Modal Style */
        .preview-container { text-align: center; margin-bottom: 15px; }
        #img_preview { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #0059b3; margin-bottom: 5px; }
        .btn-edit { color: #0059b3; cursor: pointer; font-size: 18px; }
        .btn-delete { color: #e74c3c; font-size: 18px; margin-left: 10px; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: #fff; margin: 30px auto; border-radius: 15px; width: 90%; max-width: 450px; overflow: hidden; animation: slideDown 0.3s ease; }
        .modal-header { background-color: #0059b3; padding: 20px; color: white; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 20px; max-height: 80vh; overflow-y: auto; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-save { background: #0059b3; color: white; width: 100%; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }

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
        <button class="btn-add" onclick="openAddModal()">+ Register New Driver</button>
    </div>

    <?php echo $status; ?>

    <div class="table-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Driver ID</th>
                        <th>Full Name</th>
                        <th>License No.</th>
                        <th>Contact</th>
                        <th>Actions</th>
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
                                    <td>{$row['full_name']}</td>
                                    <td>{$row['license_no']}</td>
                                    <td>{$row['contact_no']}</td>
                                    <td>
                                        <i class='fa-solid fa-pen-to-square btn-edit' onclick='openEditModal($json_data)'></i>
                                        <a href='drivers.php?delete={$row['driver_id']}' onclick='return confirmDelete()'>
                                            <i class='fa-solid fa-trash btn-delete'></i>
                                        </a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' align='center'>No drivers registered yet.</td></tr>";
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
            <form action="drivers.php" method="POST" id="driverForm" enctype="multipart/form-data">
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

    // Feature: Expand Image
    function expandImage(src) {
        expandedImg.src = src;
        lightbox.style.display = 'flex';
    }

    // Image Preview in Form
    fileInput.onchange = evt => {
        const [file] = fileInput.files;
        if (file) { imgPreview.src = URL.createObjectURL(file); }
    }

    function openAddModal() {
        driverForm.reset();
        imgPreview.src = "uploads/default-avatar.png";
        document.getElementById("modalTitle").innerText = "New Driver";
        document.getElementById("submitBtn").innerText = "Register Driver";
        document.getElementById("is_edit").value = "0";
        document.getElementById("form_driver_id").readOnly = false;
        modal.style.display = "block";
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
        modal.style.display = "block";
    }

    function closeModal() { modal.style.display = "none"; }
    function confirmDelete() { return confirm("Are you sure you want to delete this driver?"); }
    window.onclick = function(event) { if (event.target == modal) { closeModal(); } }
</script>

</body>
</html>