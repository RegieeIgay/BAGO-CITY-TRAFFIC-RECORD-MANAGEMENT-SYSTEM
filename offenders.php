<?php
include('sidebar.php'); 
require_once("db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repeat Offenders | BCTRMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; }

        /* FIXED RESPONSIVE LAYOUT ENGINE */
        .main-content { 
            flex: 1;
            margin-left: 260px; /* Sidebar width */
            padding: 40px 20px; 
            min-height: 100vh; 
            transition: all 0.3s ease; 
            width: calc(100% - 260px);
        }

        /* Sidebar collapse adjustment */
        body.sidebar-is-collapsed .main-content {
            margin-left: 70px;
            width: calc(100% - 70px);
        }

        .header { 
            margin-bottom: 30px; 
        }
        .header h1 { font-size: 1.5rem; color: #003366; }
        .header p { color: #666; font-size: 14px; }
        
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
            -webkit-overflow-scrolling: touch;
        }

        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; color: #666; font-size: 12px; text-transform: uppercase; }

        /* Offender Specific Styles */
        .count-badge {
            background: #ffebee;
            color: #c62828;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .status-critical { color: #c62828; font-weight: 600; }
        .status-warning { color: #ef6c00; font-weight: 600; }

        .btn-view {
            background: #0059b3;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: 0.3s;
            display: inline-block;
        }
        .btn-view:hover { background: #003366; }

        /* Mobile Breakpoint Fixes */
        @media (max-width: 768px) {
            .main-content { 
                margin-left: 70px !important; 
                width: calc(100% - 70px);
                padding: 20px 10px; 
            }
            .header h1 { font-size: 1.2rem; }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <h1><i class="fa-solid fa-user-slash"></i> Repeat Offenders List</h1>
        <p>Drivers with 2 or more recorded traffic violations.</p>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Driver Details</th>
                        <th>License No.</th>
                        <th>Total Violations</th>
                        <th>Latest Offense</th>
                        <th>Status Risk</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT 
                                d.driver_id, 
                                d.full_name, 
                                d.license_no, 
                                COUNT(v.violation_id) AS v_count,
                                MAX(v.violation_date) AS last_date
                            FROM drivers d
                            JOIN violations v ON d.driver_id = v.driver_id
                            GROUP BY d.driver_id
                            HAVING v_count > 1
                            ORDER BY v_count DESC";
                    
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $is_critical = ($row['v_count'] >= 5);
                            $risk_level = $is_critical ? 'Critical (Revocation Risk)' : 'Warning (Suspension Risk)';
                            $risk_class = $is_critical ? 'status-critical' : 'status-warning';
                            
                            echo "<tr>
                                    <td>
                                        <strong>{$row['full_name']}</strong><br>
                                        <small style='color:#888'>ID: {$row['driver_id']}</small>
                                    </td>
                                    <td>{$row['license_no']}</td>
                                    <td><span class='count-badge'>{$row['v_count']} Offenses</span></td>
                                    <td>" . date('M d, Y', strtotime($row['last_date'])) . "</td>
                                    <td class='$risk_class'>$risk_level</td>
                                    <td>
                                        <a href='violations.php?search={$row['driver_id']}' class='btn-view'>
                                            <i class='fa-solid fa-clock-rotate-left'></i> View History
                                        </a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' align='center'>No repeat offenders found at this time.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>