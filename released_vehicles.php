<?php
include('sidebar.php'); 
require_once("db.php");

// Get current role for URL persistence
$user_role = $_GET['role'] ?? 'User';
$role_param = "?role=" . urlencode($user_role);
$status_msg = "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Released Vehicles | BCTRMS</title>
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

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { color: #333; font-size: 24px; }
        
        .table-card { 
            background: #fff; 
            padding: 20px; 
            border-radius: 15px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            overflow-x: auto; 
        }
        
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; color: #666; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        
        .badge { 
            padding: 5px 12px; 
            border-radius: 20px; 
            font-weight: 600; 
            font-size: 11px; 
            display: inline-block;
        }
        .badge-rel { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        
        .plate-box {
            background: #333;
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            letter-spacing: 1px;
            border: 2px solid #000;
        }

        .empty-state { text-align: center; padding: 40px; color: #999; }
        
        .info-text { color: #666; font-size: 13px; }
        .info-text i { margin-right: 5px; color: #0059b3; width: 15px; }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <h1><i class="fa-solid fa-calendar-check" style="color: #16a34a;"></i> Released Vehicles History</h1>
        <div class="info-text">
            <span>Showing all vehicles cleared from impoundment.</span>
        </div>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Plate No.</th>
                    <th>Driver Name</th>
                    <th>Violation</th>
                    <th>Release Status</th>
                    <th>Impound Date</th>
                    <th>Release Date</th>
                    <th>Cleared By</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Filtered query: Only where status = 'Released'
                $sql = "SELECT i.*, d.full_name as driver_name, u.full_name as added_by, vt.violation_name 
                        FROM impounded_vehicles i 
                        LEFT JOIN drivers d ON i.driver_id = d.driver_id 
                        LEFT JOIN users u ON i.enforcer_id = u.user_id 
                        LEFT JOIN violation_types vt ON i.violation_id = vt.type_id
                        WHERE i.status = 'Released'
                        ORDER BY i.release_date DESC";
                
                $result = $conn->query($sql);
                
                if($result && $result->num_rows > 0){
                    while($row = $result->fetch_assoc()) {
                        $release_display = ($row['release_date']) ? date('M d, Y', strtotime($row['release_date'])) : '---';

                        echo "<tr>
                                <td><span class='plate-box'>{$row['plate_no']}</span></td>
                                <td>
                                    <div style='font-weight: 600; color: #333;'>" . ($row['driver_name'] ?? 'Walk-in Driver') . "</div>
                                </td>
                                <td>
                                    <div style='font-size: 13px; color: #555;'>" . ($row['violation_name'] ?? 'General Impound') . "</div>
                                </td>
                                <td><span class='badge badge-rel'>CLEARED</span></td>
                                <td>
                                    <div class='info-text'><i class='fa-regular fa-calendar'></i>" . date('M d, Y', strtotime($row['impound_date'])) . "</div>
                                </td>
                                <td>
                                    <div class='info-text' style='color:#16a34a;'><i class='fa-solid fa-calendar-check'></i>" . $release_display . "</div>
                                </td>
                                <td>
                                    <div class='info-text'><i class='fa-solid fa-user-check'></i>" . ($row['added_by'] ?? 'System Admin') . "</div>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='empty-state'>
                            <i class='fa-solid fa-folder-open' style='font-size: 40px; display: block; margin-bottom: 10px; opacity: 0.3;'></i>
                            No released vehicles found in the records.
                          </td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // LocalStorage Sidebar persistence check
    window.addEventListener('DOMContentLoaded', (event) => {
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.body.classList.add('sidebar-is-collapsed');
            const sidebar = document.getElementById('sidebar');
            if(sidebar) sidebar.classList.add('collapsed');
        }
    });
</script>

</body>
</html>