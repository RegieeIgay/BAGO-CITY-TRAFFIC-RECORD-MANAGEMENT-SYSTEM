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
        body { background-color: #f4f7f6; display: flex; overflow-x: hidden; }

        /* FIXED RESPONSIVE LAYOUT ENGINE */
        .main-content { 
            flex: 1;
            margin-left: 260px; 
            padding: 40px 20px; 
            min-height: 100vh; 
            transition: all 0.3s ease; 
            width: calc(100% - 260px);
        }

        /* Sidebar Collapse Logic */
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
        .header h1 { font-size: 1.5rem; color: #003366; font-weight: 700; }
        .header p { color: #666; font-size: 14px; }

        /* Filter Card - Responsive Grid */
        .filter-card { 
            background: #fff; 
            padding: 20px; 
            border-radius: 15px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            margin-bottom: 30px; 
        }
        .filter-form { 
            display: flex; 
            gap: 15px; 
            align-items: flex-end; 
            flex-wrap: wrap;
        }
        .form-group { flex: 1; min-width: 180px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #555; margin-bottom: 8px; }
        .form-group input { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-size: 14px;
            outline: none;
        }

        .btn-search { 
            background: #003366; 
            color: white; 
            border: none; 
            padding: 11px 25px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600; 
            transition: 0.3s;
            height: 42px;
        }
        .btn-search:hover { background: #0052a3; transform: translateY(-1px); }

        .btn-print { 
            background: #27ae60; 
            color: white; 
            padding: 11px 20px; 
            border-radius: 8px; 
            cursor: pointer; 
            text-decoration: none; 
            font-weight: 600; 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }
        .btn-print:hover { background: #219150; }

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
            -webkit-overflow-scrolling: touch; /* Smooth scroll on iOS */
        }

        table { width: 100%; border-collapse: collapse; min-width: 850px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; color: #666; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }

        /* Row Styling */
        .driver-info { font-weight: 600; color: #333; display: block; }
        .id-sub { display: block; font-size: 11px; color: #888; font-weight: normal; }
        .violation-tag { font-size: 12px; font-weight: 500; color: #c62828; background: #fff1f0; padding: 4px 10px; border-radius: 4px; border: 1px solid #ffa39e; }
        .rate-tag { font-weight: 700; color: #27ae60; font-family: 'Courier New', monospace; }
        .plate-no { font-family: 'Courier New', monospace; font-weight: bold; background: #eee; padding: 2px 6px; border-radius: 4px; border: 1px solid #ddd; }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .main-content { 
                margin-left: 70px !important; 
                width: calc(100% - 70px); 
                padding: 20px 10px; 
            }
            .header h1 { font-size: 1.2rem; }
            .filter-form { flex-direction: column; align-items: stretch; }
            .btn-search, .btn-print { width: 100%; justify-content: center; }
        }

        /* Print Design */
        @media print {
            .sidebar, .filter-card, .btn-print, .btn-search { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .table-card { box-shadow: none; padding: 0; }
            table { min-width: 100%; }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <div>
            <h1><i class="fa-solid fa-file-lines"></i> Violation Reports</h1>
            <p>Period: <strong><?php echo date('M d, Y', strtotime($start_date)); ?></strong> — <strong><?php echo date('M d, Y', strtotime($end_date)); ?></strong></p>
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
            <button type="submit" class="btn-search"><i class="fa-solid fa-rotate"></i> Filter</button>
        </form>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Driver Details</th>
                        <th>Plate No.</th>
                        <th>Violation Type</th>
                        <th>Fine Amount</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
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
                            $f_date = date('M d, Y', strtotime($row['violation_date']));
                            $f_time = date('h:i A', strtotime($row['violation_date']));
                            $rate = !empty($row['fine_amount']) ? "₱" . number_format($row['fine_amount'], 2) : "N/A";
                            
                            echo "<tr>
                                    <td>
                                        <span class='driver-info'>$f_date</span>
                                        <span class='id-sub'>$f_time</span>
                                    </td>
                                    <td>
                                        <span class='driver-info'>" . ($row['full_name'] ?? 'N/A') . "</span>
                                        <span class='id-sub'>ID: {$row['driver_id']}</span>
                                    </td>
                                    <td><span class='plate-no'>{$row['plate_no']}</span></td>
                                    <td><span class='violation-tag'>{$row['violation_type']}</span></td>
                                    <td><span class='rate-tag'>$rate</span></td>
                                    <td>
                                        <div style='display:flex; align-items:center; gap:5px;'>
                                            <i class='fa-solid fa-user-check' style='font-size:10px; color:#27ae60;'></i>
                                            <span>" . ($row['username'] ?? 'System') . "</span>
                                        </div>
                                    </td>
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
</div>

</body>
</html>