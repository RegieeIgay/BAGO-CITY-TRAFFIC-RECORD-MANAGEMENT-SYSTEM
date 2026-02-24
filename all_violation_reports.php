<?php
include('sidebar.php'); 
require_once("db.php");

// 1. Get Filter Values - Defaults to current month
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// 2. Build the Query based on date filters only
$where_sql = "v.violation_date BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Violation Reports | BCTRMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; }

        .main-content { flex: 1; margin-left: 260px; padding: 40px 20px; width: calc(100% - 260px); transition: 0.3s; }
        body.sidebar-is-collapsed .main-content { margin-left: 70px; width: calc(100% - 70px); }

        .header { margin-bottom: 25px; display: flex; justify-content: space-between; align-items: flex-end; }
        .header h1 { color: #003366; font-size: 24px; }
        .header p { color: #666; font-size: 14px; }

        .filter-card { 
            background: #fff; 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            margin-bottom: 30px; 
        }
        .filter-form { 
            display: flex; 
            gap: 20px; 
            align-items: flex-end; 
        }
        .form-group { flex: 1; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #555; margin-bottom: 8px; }
        .form-group input { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-size: 14px;
        }

        .btn-search { background: #003366; color: white; border: none; padding: 11px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; }
        .btn-search:hover { background: #0052a3; }
        .btn-print { background: #27ae60; color: white; border: none; padding: 11px 20px; border-radius: 8px; cursor: pointer; text-align: center; text-decoration: none; font-weight: 600; }

        .report-table-container { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 850px; }
        th { background: #f8f9fa; color: #666; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; color: #444; }
        
        .driver-info { font-weight: 600; color: #333; }
        .id-sub { display: block; font-size: 11px; color: #888; font-weight: normal; }
        .violation-tag { font-size: 12px; font-weight: 500; color: #c62828; background: #fff1f0; padding: 4px 10px; border-radius: 4px; display: inline-block; }
        .rate-tag { font-weight: 700; color: #27ae60; font-family: 'Courier New', monospace; }
        .plate-no { font-family: 'Courier New', monospace; font-weight: bold; background: #eee; padding: 2px 6px; border-radius: 4px; }

        @media print {
            .sidebar, .filter-card, .btn-print, #toggle-btn { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .report-table-container { box-shadow: none; border: 1px solid #eee; }
            body { background: white; }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <div>
            <h1><i class="fa-solid fa-file-lines"></i> General Violation Reports</h1>
            <p>Showing all records from <strong><?php echo date('M d, Y', strtotime($start_date)); ?></strong> to <strong><?php echo date('M d, Y', strtotime($end_date)); ?></strong></p>
        </div>
        <a href="javascript:window.print()" class="btn-print"><i class="fa-solid fa-print"></i> Print Report</a>
    </div>

    <div class="filter-card">
        <form class="filter-form" method="GET" action="all_violation_reports.php">
            <div class="form-group">
                <label>Date From</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="form-group">
                <label>Date To</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <button type="submit" class="btn-search"><i class="fa-solid fa-rotate"></i> Update Report</button>
        </form>
    </div>

    <div class="report-table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Date & Time</th>
                    <th style="width: 20%;">Driver Details</th>
                    <th style="width: 12%;">Plate Number</th>
                    <th style="width: 20%;">Violation Type</th>
                    <th style="width: 13%;">Rate (Fine)</th>
                    <th style="width: 20%;">Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // JOIN with violation_types to get the fine_amount
                $sql = "SELECT v.*, d.full_name, u.username, vt.fine_amount 
                        FROM violations v
                        LEFT JOIN drivers d ON v.driver_id = d.driver_id
                        LEFT JOIN users u ON v.recorded_by = u.user_id
                        LEFT JOIN violation_types vt ON v.violation_type = vt.violation_name
                        WHERE $where_sql
                        ORDER BY v.violation_date DESC";
                
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $formatted_date = date('M d, Y', strtotime($row['violation_date']));
                        $formatted_time = date('h:i A', strtotime($row['violation_date']));
                        // Format the rate/fine
                        $rate = !empty($row['fine_amount']) ? "₱" . number_format($row['fine_amount'], 2) : "N/A";
                        
                        echo "<tr>
                                <td>
                                    $formatted_date
                                    <span class='id-sub'>$formatted_time</span>
                                </td>
                                <td>
                                    <span class='driver-info'>" . ($row['full_name'] ?? 'N/A') . "</span>
                                    <span class='id-sub'>Driver ID: {$row['driver_id']}</span>
                                </td>
                                <td><span class='plate-no'>{$row['plate_no']}</span></td>
                                <td><span class='violation-tag'>{$row['violation_type']}</span></td>
                                <td><span class='rate-tag'>$rate</span></td>
                                <td><i class='fa-solid fa-user-check' style='font-size:10px; color:#27ae60;'></i> " . ($row['username'] ?? 'System') . "</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' align='center' style='padding:60px; color:#999;'>
                            <i class='fa-regular fa-calendar-xmark' style='font-size:40px; display:block; margin-bottom:15px; color:#ccc;'></i>
                            No violations recorded for this time period.
                          </td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>