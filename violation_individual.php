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
        body { background-color: #f4f7f6; display: flex; overflow-x: hidden; }

        /* FIXED RESPONSIVE ENGINE */
        .main-content { 
            flex: 1; 
            margin-left: 260px; 
            padding: 40px 20px; 
            width: calc(100% - 260px); 
            transition: all 0.3s ease; 
            min-height: 100vh;
        }

        body.sidebar-is-collapsed .main-content { 
            margin-left: 70px; 
            width: calc(100% - 70px); 
        }

        .header { 
            margin-bottom: 25px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap;
            gap: 15px;
        }
        .header h1 { color: #003366; font-size: 1.5rem; font-weight: 700; }

        /* Search Section */
        .search-card { 
            background: #fff; 
            padding: 20px; 
            border-radius: 15px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            margin-bottom: 25px; 
        }
        .search-form { display: flex; gap: 15px; flex-wrap: wrap; }
        .search-wrapper { flex: 1; position: relative; min-width: 250px; }
        .search-form input { 
            width: 100%;
            padding: 12px 12px 12px 40px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-size: 14px; 
            outline: none;
        }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888; }
        .btn-view { background: #003366; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; }

        /* Profile Section */
        .profile-section { 
            background: #fff; 
            border-radius: 15px; 
            padding: 25px; 
            margin-bottom: 25px; 
            display: flex; 
            gap: 25px; 
            border-left: 5px solid #003366; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            flex-wrap: wrap;
        }
        .driver-avatar { 
            width: 120px; 
            height: 120px; 
            background: #eef2f7; 
            border-radius: 12px; 
            overflow: hidden; 
            border: 1px solid #ddd; 
            cursor: zoom-in; 
            flex-shrink: 0;
        }
        .driver-avatar img { width: 100%; height: 100%; object-fit: cover; }
        
        .profile-info { flex: 1; min-width: 200px; }
        .profile-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); 
            gap: 20px; 
            margin-top: 15px; 
        }
        .info-item label { display: block; font-size: 11px; text-transform: uppercase; color: #888; font-weight: 600; }
        .info-item span { font-size: 14px; color: #444; font-weight: 600; }

        /* Table Card & Responsive UI */
        .table-card { 
            background: #fff; 
            padding: 20px; 
            border-radius: 15px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
        }
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; color: #666; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }

        /* Tags and Styling */
        .violation-tag { font-size: 12px; font-weight: 500; color: #c62828; background: #fff1f0; padding: 4px 10px; border-radius: 4px; border: 1px solid #ffa39e; }
        .rate-tag { font-weight: 700; color: #27ae60; font-family: 'Courier New', monospace; }
        .plate-no { font-family: 'Courier New', monospace; font-weight: bold; background: #eee; padding: 2px 6px; border-radius: 4px; border: 1px solid #ddd; }

        /* Modal / Lightbox */
        #img-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); align-items: center; justify-content: center; cursor: zoom-out; }
        #img-modal img { max-width: 90%; max-height: 90%; border-radius: 10px; animation: zoomIn 0.3s ease; }

        @keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        /* Mobile Breakpoints */
        @media (max-width: 768px) {
            .main-content { margin-left: 70px !important; width: calc(100% - 70px); padding: 20px 10px; }
            .profile-section { flex-direction: column; align-items: center; text-align: center; }
            .btn-view { width: 100%; }
            .header h1 { font-size: 1.2rem; }
        }

        /* Print Mode */
        @media print {
            .sidebar, .search-card, .btn-print, #toggle-btn, #img-modal { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .profile-section { border: 1px solid #eee; box-shadow: none; }
        }
    </style>
</head>
<body>

<div id="img-modal" onclick="this.style.display='none'">
    <img id="modal-img" src="">
</div>

<div class="main-content">
    <div class="header">
        <h1><i class="fa-solid fa-user-tag"></i> Individual History</h1>
        <?php if ($driver_data): ?>
            <a href="javascript:window.print()" class="btn-print" style="background: #27ae60; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-print"></i> Print Report
            </a>
        <?php endif; ?>
    </div>

    <div class="search-card">
        <form class="search-form" method="GET">
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" name="driver_id" id="driver_search" 
                       list="drivers_list" 
                       placeholder="Enter Driver Name or ID..." 
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
            <button type="submit" class="btn-view">Search Record</button>
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
                <h2 style="color:#003366;"><?php echo $driver_data['full_name']; ?></h2>
                <div class="profile-grid">
                    <div class="info-item"><label>Driver ID</label><span><?php echo $driver_data['driver_id']; ?></span></div>
                    <div class="info-item"><label>License No.</label><span><?php echo $driver_data['license_no']; ?></span></div>
                    <div class="info-item"><label>Contact</label><span><?php echo $driver_data['contact_no'] ?: 'N/A'; ?></span></div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Plate Number</th>
                            <th>Violation Type</th>
                            <th>Fine</th>
                            <th>Recorded By</th>
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

                        $total_fines = 0;
                        $violation_count = 0;

                        if ($v_result && $v_result->num_rows > 0) {
                            while($row = $v_result->fetch_assoc()) {
                                $violation_count++;
                                $total_fines += (float)$row['fine_amount'];
                                $dt = strtotime($row['violation_date']);
                                $fine = !empty($row['fine_amount']) ? "₱" . number_format($row['fine_amount'], 2) : "N/A";
                                echo "<tr>
                                        <td>
                                            <strong>" . date('M d, Y', $dt) . "</strong>
                                            <span style='font-size:11px; color:#888; display:block;'>" . date('h:i A', $dt) . "</span>
                                        </td>
                                        <td><span class='plate-no'>{$row['plate_no']}</span></td>
                                        <td><span class='violation-tag'>{$row['violation_type']}</span></td>
                                        <td><span class='rate-tag'>$fine</span></td>
                                        <td><i class='fa-solid fa-user-check' style='color:#27ae60; font-size:10px;'></i> {$row['username']}</td>
                                      </tr>";
                            }
                            // Footer Summary
                            echo "<tr style='background: #fdfdfd; border-top: 2px solid #eee;'>
                                    <td colspan='2' style='font-weight:700; color:#003366;'>TOTAL RECORDS: $violation_count</td>
                                    <td style='text-align:right; font-weight:700;'>Total Fines:</td>
                                    <td colspan='2'><span class='rate-tag' style='font-size:16px;'>₱" . number_format($total_fines, 2) . "</span></td>
                                  </tr>";
                        } else {
                            echo "<tr><td colspan='5' align='center' style='padding:50px; color:#999;'>No violations found for this driver.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif (!empty($driver_id)): ?>
        <div class="search-card" style="text-align: center; border-left: 5px solid #e74c3c;">
            <i class="fa-solid fa-circle-exclamation" style="color:#e74c3c; font-size: 24px; margin-bottom: 10px; display:block;"></i>
            No record found for: <strong><?php echo htmlspecialchars($driver_id); ?></strong>
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