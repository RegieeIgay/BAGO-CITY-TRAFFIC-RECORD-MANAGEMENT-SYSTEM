<?php
include('sidebar.php'); 
require_once("db.php");

// 1. Fetch ALL drivers for the search dropdown/autocomplete
$all_drivers = [];
$drivers_list_query = "SELECT driver_id, full_name, license_no FROM drivers";
$drivers_list_res = $conn->query($drivers_list_query);
if ($drivers_list_res) {
    while ($d_row = $drivers_list_res->fetch_assoc()) {
        $all_drivers[] = $d_row;
    }
}

// 2. Get Filter Values
$driver_id = $_GET['driver_id'] ?? '';
$driver_data = null;

// 3. Fetch Driver Details if ID is provided
if (!empty($driver_id)) {
    $driver_id_clean = $conn->real_escape_string($driver_id);
    if (preg_match('/\((.*?)\)/', $driver_id_clean, $matches)) {
        $driver_id_clean = $matches[1];
    }

    $driver_query = "SELECT * FROM drivers WHERE driver_id = '$driver_id_clean' OR license_no = '$driver_id_clean' LIMIT 1";
    $driver_res = $conn->query($driver_query);
    if ($driver_res && $driver_res->num_rows > 0) {
        $driver_data = $driver_res->fetch_assoc();
        $driver_id_clean = $driver_data['driver_id']; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Individual Violation Report | BCTRMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; }
        .main-content { flex: 1; margin-left: 260px; padding: 40px 20px; width: calc(100% - 260px); transition: 0.3s; }
        .header { margin-bottom: 25px; display: flex; justify-content: space-between; align-items: flex-end; }
        .header h1 { color: #003366; font-size: 24px; }

        .search-card { 
            background: #fff; 
            padding: 20px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            margin-bottom: 25px; 
        }
        .search-form { display: flex; gap: 15px; }
        
        .search-wrapper { flex: 1; position: relative; }
        .search-form input { 
            width: 100%;
            padding: 12px 12px 12px 40px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-size: 14px; 
        }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888; }

        .btn-view { background: #003366; color: white; border: none; padding: 0 25px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; }
        .btn-view:hover { background: #005299; }

        .profile-section { background: #fff; border-radius: 12px; padding: 25px; margin-bottom: 25px; display: flex; gap: 30px; border-left: 5px solid #003366; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        /* Driver Avatar & Click Effect */
        .driver-avatar { width: 100px; height: 100px; background: #eef2f7; border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #ddd; cursor: zoom-in; transition: transform 0.2s; }
        .driver-avatar:hover { transform: scale(1.05); }
        .driver-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .profile-grid { display: grid; grid-template-columns: auto auto auto; gap: 40px; margin-top: 10px; }
        .info-item label { display: block; font-size: 11px; text-transform: uppercase; color: #888; font-weight: 600; }
        .info-item span { font-size: 15px; color: #444; font-weight: 500; }
        
        .report-table-container { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th { background: #f8f9fa; color: #666; font-size: 11px; text-transform: uppercase; padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; color: #444; }
        .violation-tag { font-size: 12px; font-weight: 500; color: #c62828; background: #fff1f0; padding: 4px 10px; border-radius: 4px; display: inline-block; }
        .rate-tag { font-weight: 700; color: #27ae60; font-family: 'Courier New', monospace; }
        .plate-no { font-family: 'Courier New', monospace; font-weight: bold; background: #eee; padding: 2px 6px; border-radius: 4px; }

        /* Modal / Expand Styles */
        #img-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); align-items: center; justify-content: center; cursor: zoom-out; }
        #img-modal img { max-width: 90%; max-height: 90%; border-radius: 8px; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        
        @media print { .sidebar, .search-card, .btn-print, #toggle-btn, #img-modal { display: none !important; } .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; } }
    </style>
</head>
<body>

<div id="img-modal" onclick="this.style.display='none'">
    <img id="modal-img" src="" alt="Expanded View">
</div>

<div class="main-content">
    <div class="header">
        <h1><i class="fa-solid fa-user-tag"></i> Individual Violation History</h1>
        <?php if ($driver_data): ?>
            <a href="javascript:window.print()" class="btn-print" style="background: #27ae60; color: white; border: none; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;"><i class="fa-solid fa-print"></i> Print Report</a>
        <?php endif; ?>
    </div>

    <div class="search-card">
        <form class="search-form" method="GET">
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" name="driver_id" id="driver_search" 
                       list="drivers_list" 
                       placeholder="Type Driver Name, ID, or License Number..." 
                       value="<?php echo htmlspecialchars($driver_id); ?>" 
                       autocomplete="off" required>
                
                <datalist id="drivers_list">
                    <?php foreach ($all_drivers as $driver): ?>
                        <option value="<?php echo $driver['full_name'] . ' (' . $driver['driver_id'] . ')'; ?>">
                            License: <?php echo $driver['license_no']; ?>
                        </option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <button type="submit" class="btn-view">Generate Report</button>
        </form>
    </div>

    <?php if ($driver_data): ?>
        <?php 
            $upload_dir = "uploads/";
            $img_filename = $driver_data['profile_image'];
            $display_img = (!empty($img_filename) && file_exists($upload_dir . $img_filename)) 
                           ? $upload_dir . $img_filename 
                           : $upload_dir . 'default-avatar.png';
        ?>
        <div class="profile-section">
            <div class="driver-avatar" onclick="expandImage('<?php echo $display_img; ?>')">
                <img src="<?php echo $display_img; ?>" alt="Driver Photo">
            </div>
            <div class="profile-info">
                <h2><?php echo $driver_data['full_name']; ?></h2>
                <div class="profile-grid">
                    <div class="info-item"><label>Driver ID</label><span><?php echo $driver_data['driver_id']; ?></span></div>
                    <div class="info-item"><label>License No.</label><span><?php echo $driver_data['license_no']; ?></span></div>
                    <div class="info-item"><label>Contact Number</label><span><?php echo $driver_data['contact_no'] ?? 'N/A'; ?></span></div>
                </div>
            </div>
        </div>

        <div class="report-table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 20%;">Date & Time</th>
                        <th style="width: 15%;">Plate Number</th>
                        <th style="width: 25%;">Violation Type</th>
                        <th style="width: 15%;">Rate (Fine)</th>
                        <th style="width: 25%;">Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $v_sql = "SELECT v.*, u.username, vt.fine_amount 
                              FROM violations v
                              LEFT JOIN users u ON v.recorded_by = u.user_id
                              LEFT JOIN violation_types vt ON v.violation_type = vt.violation_name
                              WHERE v.driver_id = '$driver_id_clean'
                              ORDER BY v.violation_date DESC";
                    $v_result = $conn->query($v_sql);

                    if ($v_result && $v_result->num_rows > 0) {
                        while($row = $v_result->fetch_assoc()) {
                            $date = date('M d, Y', strtotime($row['violation_date']));
                            $time = date('h:i A', strtotime($row['violation_date']));
                            $rate = !empty($row['fine_amount']) ? "₱" . number_format($row['fine_amount'], 2) : "N/A";
                            echo "<tr>
                                    <td>$date <span style='font-size:11px; color:#888; display:block;'>$time</span></td>
                                    <td><span class='plate-no'>{$row['plate_no']}</span></td>
                                    <td><span class='violation-tag'>{$row['violation_type']}</span></td>
                                    <td><span class='rate-tag'>$rate</span></td>
                                    <td><i class='fa-solid fa-user-check' style='font-size:10px; color:#27ae60;'></i> {$row['username']}</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' align='center' style='padding:40px;'>No violations found for this driver.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php elseif (!empty($driver_id)): ?>
        <div class="search-card" style="text-align: center; color: #e74c3c;">
            <i class="fa-solid fa-circle-exclamation"></i> No record found for: <strong><?php echo htmlspecialchars($driver_id); ?></strong>
        </div>
    <?php endif; ?>
</div>

<script>
    function expandImage(src) {
        document.getElementById('modal-img').src = src;
        document.getElementById('img-modal').style.display = 'flex';
    }
</script>

</body>
</html>