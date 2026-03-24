<?php
include('sidebar.php'); 
require_once("db.php");

// 1. Get Filter Values - Defaults to current month
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$search_plate = $_GET['plate_no'] ?? '';

// 2. Build the Query
// We filter strictly for 'Released' status and use the release_date column
$where_clauses = [];
$where_clauses[] = "status = 'Released'";
$where_clauses[] = "release_date BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";

if (!empty($search_plate)) {
    $where_clauses[] = "plate_no LIKE '%" . $conn->real_escape_string($search_plate) . "%'";
}

$where_sql = implode(" AND ", $where_clauses);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Released Vehicle Reports | BCTRMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; min-height: 100vh; overflow-x: hidden; }

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
        .header h1 { color: #27ae60; font-size: 1.5rem; font-weight: 700; }
        .header p { color: #666; font-size: 14px; margin-top: 5px; }

        /* Filter Section */
        .filter-card { 
            background: #fff; 
            padding: 20px; 
            border-radius: 15px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            margin-bottom: 30px; 
        }
        .filter-form { 
            display: flex; 
            gap: 15px; 
            align-items: flex-end; 
            flex-wrap: wrap;
        }
        .form-group { flex: 1; min-width: 180px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 8px; text-transform: uppercase; }
        .form-group input { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-size: 14px;
            outline: none;
        }

        .btn-search { 
            background: #27ae60; 
            color: white; 
            border: none; 
            padding: 11px 25px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600; 
            transition: 0.3s; 
            height: 42px; 
        }
        .btn-search:hover { background: #219150; transform: translateY(-1px); }
        
        .btn-print { 
            background: #34495e; 
            color: white; 
            padding: 11px 20px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: 600; 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .report-table-container { 
            background: #fff; 
            padding: 20px; 
            border-radius: 15px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
        }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1000px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; color: #666; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        
        .status-released { 
            background: #f6ffed; 
            color: #389e0d; 
            border: 1px solid #b7eb8f; 
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .plate-no { font-family: 'Courier New', monospace; font-weight: bold; background: #eee; padding: 2px 6px; border-radius: 4px; border: 1px solid #ddd; }

        @media print {
            .sidebar, .filter-card, .btn-print, #toggle-btn { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            body { background: white; }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <div>
            <h1><i class="fa-solid fa-circle-check"></i> Released Vehicle Reports</h1>
            <p>Released from <strong><?php echo date('M d, Y', strtotime($start_date)); ?></strong> to <strong><?php echo date('M d, Y', strtotime($end_date)); ?></strong></p>
        </div>
        <a href="javascript:window.print()" class="btn-search"><i class="fa-solid fa-print"></i> Print Report</a>
    </div>

    <div class="filter-card">
        <form class="filter-form" method="GET">
            <div class="form-group">
                <label>Release Date From</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="form-group">
                <label>Release Date To</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="form-group">
                <label>Search Plate No.</label>
                <input type="text" name="plate_no" placeholder="Enter Plate Number..." value="<?php echo $search_plate; ?>">
            </div>
            <button type="submit" class="btn-print"><i class="fa-solid fa-rotate"></i> Update Report</button>
        </form>
    </div>

    <div class="report-table-container">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">Date Released</th>
                        <th style="width: 15%;">Plate No.</th>
                        <th style="width: 15%;">Impound Date</th>
                        <th style="width: 20%;">Location</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 25%;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM impounded_vehicles 
                            WHERE $where_sql 
                            ORDER BY release_date DESC";
                    
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>
                                        <span style='font-weight:600; color:#27ae60;'>" . date('M d, Y', strtotime($row['release_date'])) . "</span>
                                        <br><small style='color:#888;'>" . date('h:i A', strtotime($row['release_date'])) . "</small>
                                    </td>
                                    <td><span class='plate-no'>{$row['plate_no']}</span></td>
                                    <td>
                                        <span style='font-size:13px;'>" . date('M d, Y', strtotime($row['impound_date'])) . "</span>
                                    </td>
                                    <td>" . htmlspecialchars($row['location']) . "</td>
                                    <td><span class='status-released'>{$row['status']}</span></td>
                                    <td style='font-size:12px; color:#666;'>" . htmlspecialchars($row['notes'] ?: 'No notes available') . "</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' align='center' style='padding:60px; color:#999;'>
                                <i class='fa-solid fa-box-open' style='font-size:40px; display:block; margin-bottom:15px; color:#ccc;'></i>
                                No released records found for this period.
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