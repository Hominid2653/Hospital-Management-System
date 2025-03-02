<?php
require_once __DIR__ . '/../config/paths.php';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-hospital fa-2x"></i>
        <h1>Sian Flowers</h1>
    </div>
    <div class="sidebar-nav-wrapper">
        <nav class="sidebar-nav">
            <ul id="menuItems">
                <li data-menu-item="dashboard home">
                    <a href="<?php echo url(path: 'index.php'); ?>">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li data-menu-item="search find workers employees">
                    <a href="<?php echo url(path: 'workers/search.php'); ?>">
                        <i class="fas fa-search"></i>
                        <span>Find Employee</span>
                    </a>
                </li>
                <li data-menu-item="workers employees list">
                    <a href="<?php echo url(path: 'workers/list.php'); ?>">
                        <i class="fas fa-users"></i>
                        <span>Employee List</span>
                    </a>
                </li>
                <li data-menu-item="management staff admin">
                    <a href="<?php echo url(path: 'workers/management.php'); ?>">
                        <i class="fas fa-user-tie"></i>
                        <span>Management Staff</span>
                    </a>
                </li>
                <li data-menu-item="farm workers employees">
                    <a href="<?php echo url(path: 'workers/farm.php'); ?>">
                        <i class="fas fa-tractor"></i>
                        <span>General Workers</span>
                    </a>
                </li>
                <li data-menu-item="drugs medicines inventory">
                    <a href="<?php echo url(path: 'drugs/list.php'); ?>">
                        <i class="fas fa-pills"></i>
                        <span>Medical Supplies</span>
                    </a>
                </li>
                <li data-menu-item="drug stock inventory levels">
                    <a href="<?php echo url('drugs/stock.php'); ?>">
                        <i class="fas fa-boxes"></i>
                        <span>Medical Inventory</span>
                    </a>
                </li>
                
                <li data-menu-item="add new create employee">
                    <a href="<?php echo url('workers/add.php'); ?>" class="sidebar-link">
                        <i class="fas fa-user-plus"></i>
                        <span>Register New Employee</span>
                    </a>
                </li>
                <li data-menu-item="reports">
                    <a href="<?php echo url('reports/'); ?>" class="sidebar-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="logout" data-menu-item="logout signout exit">
                    <a href="<?php echo url('logout.php'); ?>">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <div class="sidebar-footer">
        <a href="<?php echo url('logout.php'); ?>" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>
</aside>

<style>
    /* Update Sidebar Styles */
    .sidebar {
        width: 260px;
        background: rgba(44, 97, 79, 0.2);
        backdrop-filter: blur(8px);
        color: white;
        position: fixed; 
        height: 100vh;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        z-index: 100;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: inset -10px 0 15px -10px rgba(0, 0, 0, 0.2);
    }

    /* Add left side shadow overlay */
    .sidebar::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 50px;
        height: 100%;
        background: linear-gradient(
            to right,
            rgba(0, 0, 0, 0.2),
            transparent
        );
        pointer-events: none;
        z-index: -1;
    }

    /* Update Navigation Styles */
    .sidebar-nav a {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
        margin-bottom: 0.25rem;
    }

    .sidebar-nav a:hover,
    .sidebar-nav a.active {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        transform: translateX(5px);
    }

    /* Update header styles */
    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar-header h1 {
        font-size: 1.5rem;
        font-weight: 500;
    }

    /* Update footer styles */
    .sidebar-footer {
        margin-top: auto;
        padding: 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Scrollbar Styling */
    .sidebar-nav-wrapper {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
    }

    .sidebar-nav-wrapper::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar-nav-wrapper::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar-nav-wrapper::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 20px;
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
        .sidebar {
            width: 80px;
        }
        
        .sidebar-header h1,
        .sidebar-nav a span,
        .logout-btn span {
            display: none;
        }
        
        .sidebar-nav a {
            justify-content: center;
            padding: 1rem;
        }
        
        .sidebar-nav a:hover {
            transform: scale(1.1);
        }
        
        .logout-btn {
            justify-content: center;
        }
    }

    .logout-btn {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        padding: 0.75rem 1rem;
        border-radius: 25px;
        transition: all 0.3s ease;
    }

    .logout-btn:hover {
        background: rgba(231, 84, 128, 0.15); /* Primary color with transparency */
        color: white;
    }

    .logout-btn i {
        font-size: 1.1rem;
    }

    /* Update icon styles */
    .sidebar-nav a i {
        font-size: 1.1rem;
        width: 20px;
        text-align: center;
    }
</style> 