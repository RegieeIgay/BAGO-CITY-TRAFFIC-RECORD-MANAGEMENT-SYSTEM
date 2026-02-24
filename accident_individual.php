<?php
include('sidebar.php'); 
require_once("db.php");

// 1. Fetch ONLY drivers who actually appear in the accidents table for the dropdown
$all_drivers = [];
$drivers_list_query = "SELECT DISTINCT d.driver_id, d.full_name, d.license_no 
                       FROM drivers d
                       INNER JOIN accidents a ON d.driver_id = a.driver_id";
$drivers_list_res = $conn->query($drivers_list_query);
if ($drivers_list_res) {
    while ($d_row = $drivers_list_res->fetch_assoc()) {
        $all_drivers[] = $d_row;
    }
}

// 2. Get Filter Values
$driver_id = $_GET['driver_id'] ?? '';
$driver_data = null;
$driver_id_clean = '';

// 3. Fetch Driver Details if ID is provided
if (!empty($driver_id)) {
    $driver_id_input = $conn->real_escape_string($driver_id);
    
    if (preg_match('/\((.*?)\)/', $driver_id_input, $matches)) {
        $driver_id_clean = $matches[1];
    } else {
        $driver_id_clean = $driver_id_input;
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
    <title>Individual Accident Report | BCTRMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; min-height: 100vh; }
        
        /* RESPONSIVE LAYOUT ENGINE */
        .main-content { 
            flex: 1; 
            margin-left: 260px; 
            padding: 40px 20px; 
            width: calc(100% - 260px); 
            transition: all 0.3s ease; 
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
        .search-wrapper { flex: 1; position: relative; min-width: 280px; }
        .search-form input { 
            width: 100%;
            padding: 12px 12px 12px 40px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-size: 14px; 
            outline: none;
        }
        .search-form input:focus { border-color: #003366; }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888; }
        .btn-view { background: #003366; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; }
        .btn-view:hover { background: #005299; transform: translateY(-1px); }

        /* Profile Card */
        .profile-section { 
            background: #fff; 
            border-radius: 15px; 
            padding: 25px; 
            margin-bottom: 25px; 
            display: flex; 
            gap: 30px; 
            border-left: 5px solid #003366; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            flex-wrap: wrap;
        }
        .driver-avatar { 
            width: 120px; 
            height: 120px; 
            background: #f8f9fa; 
            border-radius: 12px; 
            overflow: hidden; 
            border: 2px solid #eee; 
            cursor: zoom-in; 
            flex-shrink: 0;
        }
        .driver-avatar img { width: 100%; height: 100%; object-fit: cover; }
        
        .profile-info { flex: 1; min-width: 250px; }
        .profile-info h2 { color: #333; margin-bottom: 10px; font-size: 1.4rem; }
        
        .profile-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); 
            gap: 15px; 
            margin-top: 15px; 
        }
        .info-item label { display: block; font-size: 11px; text-transform: uppercase; color: #888; font-weight: 700; margin-bottom: 2px; }
        .info-item span { font-size: 14px; color: #444; font-weight: 600; }

        /* Table Responsive Container */
        .table-card { 
            background: #fff; 
            padding: 20px; 
            border-radius: 15px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
        }
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f9f9f9; color: #666; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        
        /* Badges */
        .severity-tag { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 4px; display: inline-block; text-transform: uppercase; }
        .sev-minor { color: #1976d2; background: #e3f2fd; border: 1px solid #bbdefb; }
        .sev-major { color: #f57c00; background: #fff3e0; border: 1px solid #ffe0b2; }
        .sev-fatal { color: #c62828; background: #fff1f0; border: 1px solid #ffa39e; }
        .plate-no { font-family: 'Courier New', monospace; font-weight: bold; background: #eee; padding: 2px 6px; border-radius: 4px; border: 1px solid #ddd; }

        /* Image Modal */
        #img-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); align-items: center; justify-content: center; cursor: zoom-out; }
        #img-modal img { max-width: 90%; max-height: 90%; border-radius: 8px; box-shadow: 0 0 20px rgba(0,0,0,0.5); transition: 0.3s; }

        /* Empty State */
        .empty-state { text-align: center; padding: 60px 20px; color: #999; background: #fff; border-radius: 15px; }
        .empty-state i { font-size: 50px; color: #ddd; margin-bottom: 15px; }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .main-content { margin-left: 70px !important; width: calc(100% - 70px); padding: 20px 10px; }
            .header h1 { font-size: 1.2rem; }
            .profile-section { flex-direction: column; align-items: center; text-align: center; }
            .btn-view { width: 100%; }
            .search-wrapper { min-width: 100%; }
        }

        @media print { 
            .sidebar, .search-card, .btn-print, #toggle-btn, #img-modal { display: none !important; } 
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; } 
            .profile-section { box-shadow: none; border: 1px solid #eee; break-inside: avoid; }
            body { background: white; }
        }
    </style>
</head>
<body>

<div id="img-modal" onclick="this.style.display='none'">
    <img id="modal-img" src="">
</div>

<div class="main-content">
    <div class="header">
        <h1><i class="fa-solid fa-file-invoice"></i> Individual Accident History</h1>
        <?php if ($driver_data): ?>
            <a href="javascript:window.print()" class="btn-print" style="background: #27ae60; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-print"></i> Print Records
            </a>
        <?php endif; ?>
    </div>

    <div class="search-card">
        <form class="search-form" method="GET">
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" name="driver_id" id="driver_search" 
                       list="drivers_list" 
                       placeholder="Enter Name or ID of driver with accidents..." 
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
            <button type="submit" class="btn-view">Search Records</button>
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
                    <div class="info-item"><label>System ID</label><span>#<?php echo $driver_data['driver_id']; ?></span></div>
                    <div class="info-item"><label>License Number</label><span><?php echo $driver_data['license_no']; ?></span></div>
                    <div class="info-item"><label>Phone Number</label><span><?php echo $driver_data['contact_no'] ?: 'Not Provided'; ?></span></div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 15%;">Date & Time</th>
                            <th style="width: 12%;">Plate No.</th>
                            <th style="width: 18%;">Location</th>
                            <th style="width: 12%;">Severity</th>
                            <th style="width: 28%;">Incident Description</th>
                            <th style="width: 15%;">Encoded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $a_sql = "SELECT a.*, u.username 
                                  FROM accidents a
                                  LEFT JOIN users u ON a.recorded_by = u.user_id
                                  WHERE a.driver_id = '$driver_id_clean'
                                  ORDER BY a.accident_date DESC";
                        $a_result = $conn->query($a_sql);

                        if ($a_result && $a_result->num_rows > 0) {
                            while($row = $a_result->fetch_assoc()) {
                                $dt = strtotime($row['accident_date']);
                                $sev = $row['severity'];
                                $sev_class = 'sev-' . strtolower($sev);
                                
                                echo "<tr>
                                        <td>
                                            <strong>" . date('M d, Y', $dt) . "</strong>
                                            <span style='font-size:11px; color:#888; display:block;'>" . date('h:i A', $dt) . "</span>
                                        </td>
                                        <td><span class='plate-no'>{$row['plate_no']}</span></td>
                                        <td>" . htmlspecialchars($row['location']) . "</td>
                                        <td><span class='severity-tag $sev_class'>$sev</span></td>
                                        <td style='font-size:12px; line-height:1.5; color:#555;'>" . htmlspecialchars($row['description']) . "</td>
                                        <td>
                                            <div style='display:flex; align-items:center; gap:5px; font-size:13px;'>
                                                <i class='fa-solid fa-user-check' style='color:#27ae60;'></i>
                                                <span>" . ($row['username'] ?? 'Admin') . "</span>
                                            </div>
                                        </td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' align='center' style='padding:50px; color:#999;'>No verified accident records found for this driver.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif (!empty($driver_id)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-triangle-exclamation" style="color:#e74c3c;"></i>
            <h3>No Records Found</h3>
            <p>We couldn't find any accident history for <strong><?php echo htmlspecialchars($driver_id); ?></strong>.</p>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-solid fa-magnifying-glass"></i>
            <h3>Search for a Driver</h3>
            <p>Enter a driver's name or license number above to generate their accident history report.</p>
        </div>
    <?php endif; ?>
</div>

<script>
    function expandImage(src) {
        if(src.includes('default-avatar.png')) return; // Don't expand default avatar
        document.getElementById('modal-img').src = src;
        document.getElementById('img-modal').style.display = 'flex';
    }
</script>

</body>
</html>