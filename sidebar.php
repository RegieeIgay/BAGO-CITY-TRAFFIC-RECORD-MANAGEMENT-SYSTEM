<?php
// sidebar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);

// --- ADDED LOGIC TO PERSIST ROLE IN URL ---
$user_role = $_SESSION['role'] ?? 'User';
$role_query = "?role=" . urlencode($user_role);
?>
<style>
    /* ... (CSS remains exactly the same as your original) ... */
    :root {
        --sidebar-width: 260px;
        --sidebar-collapsed-width: 70px;
        --sidebar-bg: linear-gradient(to bottom, #003366, #0059b3);
    }

    .sidebar {
        height: 100vh;
        width: var(--sidebar-width); 
        position: fixed;
        top: 0;
        left: 0;
        background: var(--sidebar-bg);
        color: white;
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 2px 0 10px rgba(0,0,0,0.2);
        overflow-x: hidden;
        z-index: 1000;
        display: flex;
        flex-direction: column;
    }

    .sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }

    .main-content {
        margin-left: var(--sidebar-width);
        transition: margin-left 0.3s ease;
    }
    body.sidebar-is-collapsed .main-content {
        margin-left: var(--sidebar-collapsed-width);
    }

    .sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px;
        min-height: 70px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-branding {
        white-space: nowrap;
        overflow: hidden;
        transition: opacity 0.2s;
    }

    .sidebar.collapsed .sidebar-branding {
        opacity: 0;
        width: 0;
        padding: 0;
        pointer-events: none;
    }

    .sidebar-branding h3 { margin: 0; font-size: 16px; font-weight: 800; color: #fff; }
    .sidebar-branding h4 { margin: 0; font-size: 8px; font-weight: 600; color: #fff; opacity: 0.8; }

    #toggle-btn {
        background: rgba(255,255,255,0.1);
        border: none;
        color: white;
        width: 38px;
        height: 38px;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .menu-container {
        flex-grow: 1;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .sidebar .menu-label {
        font-size: 10px;
        text-transform: uppercase;
        color: rgba(255,255,255,0.4);
        padding: 20px 25px 5px;
        letter-spacing: 1px;
        white-space: nowrap;
        transition: opacity 0.2s;
    }
    .sidebar.collapsed .menu-label { opacity: 0; height: 0; padding: 0; overflow: hidden; }

    .sidebar a, .dropdown-btn, .nested-btn {
        height: 50px;
        text-decoration: none;
        font-size: 15px;
        color: #d1e3f3;
        display: flex;
        align-items: center;
        padding: 0 25px;
        transition: 0.3s;
        white-space: nowrap;
        cursor: pointer;
        position: relative;
    }

    .sidebar a i, .dropdown-btn i, .nested-btn i {
        min-width: 20px;
        font-size: 18px;
        margin-right: 15px;
        text-align: center;
        flex-shrink: 0;
        transition: margin 0.3s;
    }

    .sidebar.collapsed a i, 
    .sidebar.collapsed .dropdown-btn i {
        margin-right: 0;
        margin-left: -2px; 
    }

    .sidebar.collapsed span, 
    .sidebar.collapsed .chevron {
        display: none !important;
    }

    .sidebar a:hover, .dropdown-btn:hover, .nested-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .active-link {
        background: rgba(255, 255, 255, 0.15);
        color: #fff !important;
        box-shadow: inset 4px 0 0 #fff;
    }

    .dropdown-container, .nested-container {
        display: none;
        flex-direction: column;
        background: rgba(0, 0, 0, 0.1);
    }
    
    .dropdown-container .nested-btn, .dropdown-container a { padding-left: 55px; font-size: 14px; }
    .nested-container a { padding-left: 75px; font-size: 13px; background: rgba(0, 0, 0, 0.05); }

    .chevron {
        font-size: 11px !important;
        margin-left: auto;
        transition: transform 0.3s;
    }
    .rotate-chevron { transform: rotate(180deg); }
    .show-dropdown { display: flex !important; }

    .sidebar.collapsed .dropdown-container, 
    .sidebar.collapsed .nested-container { display: none !important; }

    .user-info {
        padding: 15px 25px;
        background: rgba(0,0,0,0.2);
        border-top: 1px solid rgba(255,255,255,0.1);
        white-space: nowrap;
        overflow: hidden;
    }
    .sidebar.collapsed .user-info { display: none; }
    .role-badge {
        display: inline-block;
        font-size: 10px;
        text-transform: uppercase;
        background: rgba(255, 255, 255, 0.2);
        padding: 2px 8px;
        border-radius: 4px;
        margin-top: 4px;
        font-weight: 500;
        letter-spacing: 0.5px;
        color: #fff;
    }
</style>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-branding">
            <h3>BAGO CITY</h3>
            <h4>TRAFFIC RECORD</h4>
            <h4>MANAGEMENT SYSTEM</h4>
        </div>
        <button id="toggle-btn" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
    
    <div class="menu-container">
        <div class="menu-label">Main</div>
        <a href="index.php<?php echo $role_query; ?>" class="<?php echo ($current_page == 'index.php') ? 'active-link' : ''; ?>">
            <i class="fa-solid fa-chart-line"></i> <span>Dashboard</span>
        </a>

        <div class="menu-label">Setup</div>
        <a href="drivers.php<?php echo $role_query; ?>" class="<?php echo ($current_page == 'drivers.php') ? 'active-link' : ''; ?>">
            <i class="fa-solid fa-id-card"></i> <span>Drivers</span>
        </a>
        <a href="vehicles.php<?php echo $role_query; ?>" class="<?php echo ($current_page == 'vehicles.php') ? 'active-link' : ''; ?>">
            <i class="fa-solid fa-car"></i> <span>Vehicles</span>
        </a>
        <a href="violation_types.php<?php echo $role_query; ?>" class="<?php echo ($current_page == 'violation_types.php') ? 'active-link' : ''; ?>">
            <i class="fa-solid fa-list-check"></i> <span>Violation Types</span>
        </a>
        
        <div class="menu-label">Records</div>
        <a href="violations.php<?php echo $role_query; ?>" class="<?php echo ($current_page == 'violations.php') ? 'active-link' : ''; ?>">
            <i class="fa-solid fa-file-invoice"></i> <span>Violations</span>
        </a>
        <a href="accidents.php<?php echo $role_query; ?>" class="<?php echo ($current_page == 'accidents.php') ? 'active-link' : ''; ?>">
            <i class="fa-solid fa-car-burst"></i> <span>Accidents</span>
        </a>
        <a href="offenders.php<?php echo $role_query; ?>" class="<?php echo ($current_page == 'offenders.php') ? 'active-link' : ''; ?>">
            <i class="fa-solid fa-user-slash"></i> <span>Repeat Offenders</span>
        </a>

        <div class="menu-label">System</div>
        <div class="dropdown-btn" onclick="toggleElement('report-dropdown', 'report-chevron')">
            <i class="fa-solid fa-print"></i> <span>Reports</span>
            <i class="fa-solid fa-chevron-down chevron" id="report-chevron"></i>
        </div>
        
        <div class="dropdown-container" id="report-dropdown">
            <div class="nested-btn" onclick="toggleElement('vio-nested', 'vio-chevron')">
                <i class="fa-solid fa-file-lines"></i> <span>Violations</span>
                <i class="fa-solid fa-chevron-down chevron" id="vio-chevron"></i>
            </div>
            <div class="nested-container" id="vio-nested">
                <a href="all_violation_reports.php<?php echo $role_query; ?>" class="<?php echo ($current_page == 'all_violation_reports.php') ? 'active-link' : ''; ?>">Main Reports</a>
                <a href="violation_individual.php<?php echo $role_query; ?>" class="<?php echo ($current_page == 'violation_individual.php') ? 'active-link' : ''; ?>">Individual Reports</a>
            </div>

            <div class="nested-btn" onclick="toggleElement('acc-nested', 'acc-chevron')">
                <i class="fa-solid fa-file-circle-exclamation"></i> <span>Accidents</span>
                <i class="fa-solid fa-chevron-down chevron" id="acc-chevron"></i>
            </div>
            <div class="nested-container" id="acc-nested">
                <a href="all_accident_reports.php<?php echo $role_query; ?>" class="<?php echo ($current_page == 'all_accident_reports.php') ? 'active-link' : ''; ?>">Main Reports</a>
                <a href="accident_individual.php<?php echo $role_query; ?>" class="<?php echo ($current_page == 'accident_individual.php') ? 'active-link' : ''; ?>">Individual Reports</a>
            </div>
        </div>
        
        <a href="login.php" style="color: #ff7675; margin-top: 10px;" onclick="return confirmLogout();">
            <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
        </a>
    </div>

    <div class="user-info">
        <div style="opacity: 0.7; font-size: 11px; margin-bottom: 4px; text-transform: uppercase;">Logged in as:</div>
        <div style="line-height: 1.2;">
            <strong style="display: block; font-size: 14px;">
                <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Personnel'); ?>
            </strong>
            <span class="role-badge">
                <?php echo htmlspecialchars($user_role); ?>
            </span>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
        document.body.classList.toggle('sidebar-is-collapsed');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    }

    function toggleElement(containerId, chevronId) {
        if(document.getElementById('sidebar').classList.contains('collapsed')) {
            toggleSidebar();
        }
        document.getElementById(containerId).classList.toggle('show-dropdown');
        document.getElementById(chevronId).classList.toggle('rotate-chevron');
    }

    window.onload = () => {
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.getElementById('sidebar').classList.add('collapsed');
            document.body.classList.add('sidebar-is-collapsed');
        }

        const currentPage = "<?php echo $current_page; ?>";
        const reportPages = ['all_violation_reports.php', 'violation_individual.php', 'all_accident_reports.php', 'accident_individual.php'];
        
        if (reportPages.includes(currentPage)) {
            document.getElementById('report-dropdown').classList.add('show-dropdown');
            document.getElementById('report-chevron').classList.add('rotate-chevron');

            if (currentPage.includes('violation')) {
                document.getElementById('vio-nested').classList.add('show-dropdown');
                document.getElementById('vio-chevron').classList.add('rotate-chevron');
            } else if (currentPage.includes('accident')) {
                document.getElementById('acc-nested').classList.add('show-dropdown');
                document.getElementById('acc-chevron').classList.add('rotate-chevron');
            }
        }
    }

    function confirmLogout() {
        return confirm("Are you sure you want to log out?");
    }
</script>