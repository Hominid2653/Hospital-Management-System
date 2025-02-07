<aside class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-hospital fa-2x"></i>
        <h1>Sian Roses</h1>
    </div>
    <div class="menu-search">
        <div class="search-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" id="menuSearch" placeholder="Search menu...">
        </div>
    </div>
    <div class="sidebar-nav-wrapper">
        <nav class="sidebar-nav">
            <ul id="menuItems">
                <li data-menu-item="dashboard home">
                    <a href="<?php echo $base_url; ?>index.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li data-menu-item="workers employees list">
                    <a href="<?php echo $base_url; ?>workers/list.php">
                        <i class="fas fa-users"></i>
                        <span>Workers List</span>
                    </a>
                </li>
                <li data-menu-item="management staff admin">
                    <a href="<?php echo $base_url; ?>workers/management.php">
                        <i class="fas fa-user-tie"></i>
                        <span>Management Staff</span>
                    </a>
                </li>
                <li data-menu-item="farm workers employees">
                    <a href="<?php echo $base_url; ?>workers/farm.php">
                        <i class="fas fa-tractor"></i>
                        <span>Farm Workers</span>
                    </a>
                </li>
                <li data-menu-item="drugs medicines inventory">
                    <a href="<?php echo $base_url; ?>drugs/list.php">
                        <i class="fas fa-pills"></i>
                        <span>Drugs List</span>
                    </a>
                </li>
                <li data-menu-item="drug stock inventory levels">
                    <a href="<?php echo $base_url; ?>drugs/stock.php">
                        <i class="fas fa-boxes"></i>
                        <span>Drug Stock</span>
                    </a>
                </li>
                <li data-menu-item="search find workers employees">
                    <a href="<?php echo $base_url; ?>workers/search.php">
                        <i class="fas fa-search"></i>
                        <span>Search Workers</span>
                    </a>
                </li>
                <li data-menu-item="add new create worker employee">
                    <a href="<?php echo $base_url; ?>workers/add.php" class="sidebar-link">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Worker</span>
                    </a>
                </li>
                <li data-menu-item="reports">
                    <a href="<?php echo $base_url; ?>reports/" class="sidebar-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="logout" data-menu-item="logout signout exit">
                    <a href="<?php echo $base_url; ?>logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <div class="sidebar-footer">
        <a href="<?php echo $base_url; ?>logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>
</aside>

<style>
.sidebar {
    display: flex;
    flex-direction: column;
    height: 100vh;
    position: fixed;
    width: 250px;
    background: #2c3e50;
    color: white;
}

.sidebar-header {
    padding: 1rem;
    flex-shrink: 0;
}

.menu-search {
    padding: 0.75rem 1rem;
    flex-shrink: 0;
}

.sidebar-nav-wrapper {
    flex: 1;
    overflow-y: auto;
    /* Smooth scrolling */
    scroll-behavior: smooth;
    /* Hide scrollbar for Chrome/Safari/Opera */
    &::-webkit-scrollbar {
        width: 6px;
    }
    &::-webkit-scrollbar-track {
        background: transparent;
    }
    &::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }
}

.sidebar-nav {
    padding: 0.5rem 0;
}

.sidebar-footer {
    padding: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
}

/* Add new search styles */
.search-wrapper {
    position: relative;
    width: 100%;
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    padding: 0.5rem;
}

.search-wrapper i {
    color: rgba(255, 255, 255, 0.6);
    margin-right: 0.5rem;
    font-size: 0.9rem;
}

.search-wrapper input {
    width: 100%;
    background: transparent;
    border: none;
    color: white;
    font-size: 0.9rem;
    padding: 0;
}

.search-wrapper input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.search-wrapper input:focus {
    outline: none;
}

/* Add subtle hover and focus effects */
.search-wrapper:hover {
    background: rgba(255, 255, 255, 0.15);
}

.search-wrapper:focus-within {
    background: rgba(255, 255, 255, 0.2);
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1);
}

/* Keep existing styles for menu items */
</style> 