<?php
session_start();
require_once("db.php"); 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Data fetching logic remains the same
$total_drivers = $conn->query("SELECT COUNT(*) as count FROM drivers")->fetch_assoc()['count'];
$total_vehicles = $conn->query("SELECT COUNT(*) as count FROM vehicles")->fetch_assoc()['count'];
$total_accidents = $conn->query("SELECT COUNT(*) as count FROM accidents")->fetch_assoc()['count'];
$total_violations = $conn->query("SELECT COUNT(*) as count FROM violations")->fetch_assoc()['count'];

// Simplified Monthly Arrays (1-6 for Jan-Jun)
$v_monthly = array_fill(1, 6, 0);
$a_monthly = array_fill(1, 6, 0);

$v_res = $conn->query("SELECT MONTH(violation_date) as m, COUNT(*) as count FROM violations WHERE YEAR(violation_date) = YEAR(CURRENT_DATE) AND MONTH(violation_date) <= 6 GROUP BY MONTH(violation_date)");
while($row = $v_res->fetch_assoc()) { $v_monthly[$row['m']] = (int)$row['count']; }

$a_res = $conn->query("SELECT MONTH(accident_date) as m, COUNT(*) as count FROM accidents WHERE YEAR(accident_date) = YEAR(CURRENT_DATE) AND MONTH(accident_date) <= 6 GROUP BY MONTH(accident_date)");
while($row = $a_res->fetch_assoc()) { $a_monthly[$row['m']] = (int)$row['count']; }

$severity_counts = ['Minor' => 0, 'Major' => 0, 'Fatal' => 0];
$pie_res = $conn->query("SELECT severity, COUNT(*) as count FROM accidents GROUP BY severity");
while($row = $pie_res->fetch_assoc()) { $severity_counts[$row['severity']] = (int)$row['count']; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | BCTRMS</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-blue: #003366;
            --bg-gray: #f4f7f9;
            --sidebar-width: 250px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-gray); overflow-x: hidden; }

        /* Responsive Layout Container */
        .main-content { 
            margin-left: var(--sidebar-width); 
            padding: 20px; 
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        /* Mobile Header Adjustments */
        .header { 
            display: flex; 
            flex-direction: row; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 25px;
            gap: 10px;
        }
        .header h1 { color: var(--primary-blue); font-size: clamp(1.2rem, 5vw, 1.8rem); }

        /* Stats Grid - Mobile First */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); 
            gap: 15px; 
            margin-bottom: 25px; 
        }

        .stat-box { 
            background: #fff; 
            padding: 20px 15px; 
            border-radius: 12px; 
            text-align: center; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .stat-box i { font-size: 1.5rem; color: var(--primary-blue); margin-bottom: 10px; }
        .stat-box h3 { font-size: 1.5rem; }
        .stat-box p { font-size: 0.7rem; font-weight: 600; color: #888; text-transform: uppercase; }

        /* Charts Container - Stacks on Mobile */
        .charts-container { 
            display: flex; 
            flex-direction: column; 
            gap: 20px; 
        }

        .chart-card { 
            background: #fff; 
            padding: 20px; 
            border-radius: 15px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            width: 100%;
        }

        /* Desktop Adjustments */
        @media (min-width: 1024px) {
            .main-content { padding: 40px; }
            .charts-container { flex-direction: row; }
            .chart-card.large { flex: 2; }
            .chart-card.small { flex: 1; }
        }

        /* Mobile Adjustments (Hide Sidebar or adjust margin) */
        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 15px; }
            .sidebar { display: none; } /* Assuming sidebar is hidden on mobile or needs a toggle */
            .welcome-card h2 { font-size: 1.2rem; }
        }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <span style="font-size: 0.85rem; color: #666; text-align: right;">
                <i class="fa-regular fa-calendar-days"></i> <?php echo date('M j, Y'); ?>
            </span>
        </div>

        <div class="welcome-card" style="background: #fff; padding: 20px; border-radius: 12px; margin-bottom: 20px; border-left: 4px solid var(--primary-blue);">
            <h2>Hello, <?php echo explode(' ', $_SESSION['full_name'] ?? 'Admin')[0]; ?>!</h2>
            <p style="font-size: 0.9rem; color: #555;">BCTRMS System Overview</p>
        </div>

        <div class="stats-grid">
            <div class="stat-box">
                <i class="fa-solid fa-id-card"></i>
                <h3><?php echo $total_drivers; ?></h3>
                <p>Drivers</p>
            </div>
            <div class="stat-box">
                <i class="fa-solid fa-bus"></i>
                <h3><?php echo $total_vehicles; ?></h3>
                <p>Vehicles</p>
            </div>
            <div class="stat-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <h3><?php echo $total_violations; ?></h3>
                <p>Violations</p>
            </div>
            <div class="stat-box">
                <i class="fa-solid fa-car-burst"></i>
                <h3><?php echo $total_accidents; ?></h3>
                <p>Accidents</p>
            </div>
        </div>

        <div class="charts-container">
            <div class="chart-card large">
                <h2 style="font-size: 1rem; margin-bottom: 15px;"><i class="fa-solid fa-chart-line"></i> Incident Trends</h2>
                <div style="position: relative; height:300px; width:100%;">
                    <canvas id="barChart"></canvas>
                </div>
            </div>

            <div class="chart-card small">
                <h2 style="font-size: 1rem; margin-bottom: 15px;"><i class="fa-solid fa-chart-pie"></i> Severity</h2>
                <div style="position: relative; height:300px; width:100%;">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false, // Allows the chart to fill the container height
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } }
        };

        // Bar Chart
        new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [
                    { label: 'Violations', data: [<?php echo implode(',', $v_monthly); ?>], backgroundColor: '#003366', borderRadius: 4 },
                    { label: 'Accidents', data: [<?php echo implode(',', $a_monthly); ?>], backgroundColor: '#e74c3c', borderRadius: 4 }
                ]
            },
            options: commonOptions
        });

        // Pie Chart
        new Chart(document.getElementById('pieChart'), {
            type: 'pie',
            data: {
                labels: ['Minor', 'Major', 'Fatal'],
                datasets: [{
                    data: [<?php echo implode(',', $severity_counts); ?>],
                    backgroundColor: ['#1976d2', '#f39c12', '#c62828']
                }]
            },
            options: commonOptions
        });
    </script>
</body>
</html>