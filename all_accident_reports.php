<?php
include('sidebar.php'); 
require_once("db.php");

// 1. Get Filter Values - Defaults to current month
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$severity_filter = $_GET['severity'] ?? '';

// 2. Build the Query based on date and severity filters
$where_clauses = [];
$where_clauses[] = "a.accident_date BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";

if (!empty($severity_filter)) {
    $where_clauses[] = "a.severity = '" . $conn->real_escape_string($severity_filter) . "'";
}

$where_sql = implode(" AND ", $where_clauses);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Accident Reports | BCTRMS</title>
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
            gap: 15px; 
            align-items: flex-end; 
            flex-wrap: wrap;
        }
        .form-group { flex: 1; min-width: 150px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #555; margin-bottom: 8px; }
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-size: 14px;
            outline: none;
        }

        .btn-search { background: #003366; color: white; border: none; padding: 11px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; height: 41px; }
        .btn-search:hover { background: #0052a3; }
        .btn-print { background: #27ae60; color: white; border: none; padding: 11px 20px; border-radius: 8px; cursor: pointer; text-align: center; text-decoration: none; font-weight: 600; }

        .report-table-container { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1000px; }
        th { background: #f8f9fa; color: #666; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; color: #444; vertical-align: top; }
        
        .driver-info { font-weight: 600; color: #333; }
        .id-sub { display: block; font-size: 11px; color: #888; font-weight: normal; }
        
        /* Severity Tags using the violation-tag style but color-coded */
        .severity-tag { font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 4px; display: inline-block; text-transform: uppercase; }
        .sev-minor { color: #1976d2; background: #e3f2fd; }
        .sev-major { color: #f57c00; background: #fff3e0; }
        .sev-fatal { color: #c62828; background: #fff1f0; }

        .plate-no { font-family: 'Courier New', monospace; font-weight: bold; background: #eee; padding: 2px 6px; border-radius: 4px; }
        .desc-text { font-size: 12px; color: #666; line-height: 1.4; }

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
            <h1><i class="fa-solid fa-file-circle-exclamation"></i> General Accident Reports</h1>
            <p>Showing <strong><?php echo $severity_filter ?: 'All'; ?></strong> records from <strong><?php echo date('M d, Y', strtotime($start_date)); ?></strong> to <strong><?php echo date('M d, Y', strtotime($end_date)); ?></strong></p>
        </div>
        <a href="javascript:window.print()" class="btn-print"><i class="fa-solid fa-print"></i> Print Report</a>
    </div>

    <div class="filter-card">
        <form class="filter-form" method="GET" action="all_accident_reports.php">
            <div class="form-group">
                <label>Date From</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="form-group">
                <label>Date To</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="form-group">
                <label>Severity</label>
                <select name="severity">
                    <option value="">All Severities</option>
                    <option value="Minor" <?php echo ($severity_filter == 'Minor') ? 'selected' : ''; ?>>Minor</option>
                    <option value="Major" <?php echo ($severity_filter == 'Major') ? 'selected' : ''; ?>>Major</option>
                    <option value="Fatal" <?php echo ($severity_filter == 'Fatal') ? 'selected' : ''; ?>>Fatal</option>
                </select>
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
                    <th style="width: 10%;">Plate Number</th>
                    <th style="width: 15%;">Location</th>
                    <th style="width: 12%;">Severity</th>
                    <th style="width: 15%;">Description</th>
                    <th style="width: 13%;">Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT a.*, d.full_name, u.username 
                        FROM accidents a
                        LEFT JOIN drivers d ON a.driver_id = d.driver_id
                        LEFT JOIN users u ON a.recorded_by = u.user_id
                        WHERE $where_sql
                        ORDER BY a.accident_date DESC";
                
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $formatted_date = date('M d, Y', strtotime($row['accident_date']));
                        $formatted_time = date('h:i A', strtotime($row['accident_date']));
                        $sev = $row['severity'];
                        $sev_class = 'sev-' . strtolower($sev);
                        
                        echo "<tr>
                                <td>
                                    $formatted_date
                                    <span class='id-sub'>$formatted_time</span>
                                </td>
                                <td>
                                    <span class='driver-info'>" . ($row['full_name'] ?? 'Unknown Driver') . "</span>
                                    <span class='id-sub'>Driver ID: {$row['driver_id']}</span>
                                </td>
                                <td><span class='plate-no'>{$row['plate_no']}</span></td>
                                <td>" . htmlspecialchars($row['location']) . "</td>
                                <td><span class='severity-tag $sev_class'>$sev</span></td>
                                <td class='desc-text'>" . htmlspecialchars($row['description']) . "</td>
                                <td><i class='fa-solid fa-user-pen' style='font-size:10px; color:#27ae60;'></i> " . ($row['username'] ?? 'System') . "</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' align='center' style='padding:60px; color:#999;'>
                            <i class='fa-solid fa-car-burst' style='font-size:40px; display:block; margin-bottom:15px; color:#ccc;'></i>
                            No accidents found for the selected criteria.
                          </td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>