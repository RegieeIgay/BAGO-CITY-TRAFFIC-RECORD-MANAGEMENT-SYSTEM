<?php
include('sidebar.php'); 
require_once("db.php");

// Get role from URL for access control
$user_role = $_GET['role'] ?? 'User';
$role_param = "?role=" . urlencode($user_role);

// Check if user is Admin to restrict actions
$is_admin = (strtolower($user_role) === 'admin');

$status_msg = "";

// --- LOGIC 1: Handle Status Toggle (Enable/Disable) ---
if (isset($_GET['toggle_id']) && $is_admin) {
    $id = $_GET['toggle_id'];
    $current_status = $_GET['current'];
    $new_status = ($current_status == 'Active') ? 'Disabled' : 'Active';

    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ? AND role = 'Traffic Enforcer'");
    $stmt->bind_param("si", $new_status, $id);
    
    if ($stmt->execute()) {
        $status_msg = "<div class='alert success'>Enforcer account set to $new_status!</div>";
    }
}

// --- LOGIC 2: Handle Delete Request ---
if (isset($_GET['delete']) && $is_admin) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'Traffic Enforcer'");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $status_msg = "<div class='alert success'>Enforcer removed successfully!</div>";
    } else {
        $status_msg = "<div class='alert error'>Error: Could not remove enforcer.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enforcers Management | BCTRMS</title>
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

        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            flex-wrap: wrap; 
            gap: 15px;
        }

        .header h1 { font-size: 1.5rem; color: #003366; }

        .search-container { position: relative; min-width: 250px; }
        .search-container input {
            width: 100%;
            padding: 10px 15px 10px 35px;
            border-radius: 8px;
            border: 1px solid #ddd;
            outline: none;
            font-size: 14px;
        }
        .search-container i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #888; }

        .table-card { background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; color: #666; font-size: 12px; text-transform: uppercase; cursor: pointer; }
        
        /* Status Badge Styles */
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }
        .status-active { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-active:hover { background: #c3e6cb; }
        .status-disabled { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-disabled:hover { background: #f5c6cb; }

        .btn-delete { color: #e74c3c; font-size: 18px; transition: 0.2s; text-decoration: none; }
        .btn-delete:hover { transform: scale(1.2); }

        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        @media (max-width: 768px) {
            .main-content { margin-left: 70px !important; width: calc(100% - 70px); }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <h1>Traffic Enforcers Management</h1>
        <div class="search-container">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="Search by name or username..." onkeyup="filterTable()">
        </div>
    </div>

    <?php echo $status_msg; ?>

    <div class="table-card">
        <div class="table-responsive">
            <table id="enforcersTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)">Full Name <i class="fa-solid fa-sort"></i></th>
                        <th onclick="sortTable(1)">Username <i class="fa-solid fa-sort"></i></th>
                        <th>Account Status (Click to Toggle)</th>
                        <th onclick="sortTable(3)">Created At <i class="fa-solid fa-sort"></i></th>
                        <?php if ($is_admin): ?>
                            <th style="text-align: center;">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM users WHERE role = 'Traffic Enforcer' ORDER BY full_name ASC";
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $u_id = $row['user_id'];
                            $current_st = $row['status'] ?? 'Active'; 
                            $badge_class = ($current_st == 'Active') ? 'status-active' : 'status-disabled';
                            $icon = ($current_st == 'Active') ? 'fa-check-circle' : 'fa-ban';

                            echo "<tr>
                                    <td class='name-cell'><strong>{$row['full_name']}</strong></td>
                                    <td>{$row['username']}</td>
                                    <td>
                                        <a href='enforcers.php{$role_param}&toggle_id=$u_id&current=$current_st' 
                                           class='status-badge $badge_class' 
                                           onclick='return confirmToggle(\"$current_st\")'>
                                           <i class='fa-solid $icon'></i> $current_st
                                        </a>
                                    </td>
                                    <td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                            
                            if ($is_admin) {
                                echo "<td style='text-align: center;'>
                                        <a href='enforcers.php{$role_param}&delete=$u_id' onclick='return confirmDelete()' class='btn-delete'>
                                            <i class='fa-solid fa-trash'></i>
                                        </a>
                                    </td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        $colspan = $is_admin ? 5 : 4;
                        echo "<tr class='no-data'><td colspan='$colspan' align='center'>No Traffic Enforcers found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function filterTable() {
        const input = document.getElementById("searchInput");
        const filter = input.value.toLowerCase();
        const table = document.getElementById("enforcersTable");
        const tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            if(tr[i].classList.contains('no-data')) continue;
            const nameCell = tr[i].getElementsByClassName("name-cell")[0];
            const usernameCell = tr[i].getElementsByTagName("td")[1];
            
            if (nameCell || usernameCell) {
                const nameTxt = nameCell.textContent || nameCell.innerText;
                const userTxt = usernameCell.textContent || usernameCell.innerText;
                tr[i].style.display = (nameTxt.toLowerCase().indexOf(filter) > -1 || userTxt.toLowerCase().indexOf(filter) > -1) ? "" : "none";
            }
        }
    }

    function sortTable(n) {
        var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
        table = document.getElementById("enforcersTable");
        switching = true; dir = "asc";
        while (switching) {
            switching = false; rows = table.rows;
            for (i = 1; i < (rows.length - 1); i++) {
                if(rows[i].classList.contains('no-data')) continue;
                shouldSwitch = false;
                x = rows[i].getElementsByTagName("TD")[n];
                y = rows[i + 1].getElementsByTagName("TD")[n];
                if (dir == "asc") {
                    if (x.innerText.toLowerCase() > y.innerText.toLowerCase()) { shouldSwitch = true; break; }
                } else if (dir == "desc") {
                    if (x.innerText.toLowerCase() < y.innerText.toLowerCase()) { shouldSwitch = true; break; }
                }
            }
            if (shouldSwitch) {
                rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                switching = true; switchcount++;
            } else {
                if (switchcount == 0 && dir == "asc") { dir = "desc"; switching = true; }
            }
        }
    }

    function confirmDelete() { return confirm("Are you sure you want to PERMANENTLY delete this enforcer?"); }
    function confirmToggle(status) { 
        const action = (status === 'Active') ? 'DISABLE' : 'ENABLE';
        return confirm(`Are you sure you want to ${action} this account?`); 
    }
</script>

</body>
</html>