<header class="header">
    <nav class="navbar">
        <a href="<?php echo isVendor() ? '../vendor/dashboard.php' : '../stakeholder/dashboard.php'; ?>" class="logo">♻️ Smart Scrap</a>
        <ul class="nav-links">
            <?php if(isVendor()): ?>
                <li><a href="../vendor/dashboard.php">Dashboard</a></li>
                <li><a href="../vendor/my_requests.php">My Requests</a></li>
                <li><a href="../vendor/payments.php">Payments</a></li>
                <li><a href="../vendor/logout.php">Logout</a></li>
            <?php elseif(isStakeholder()): ?>
                <li><a href="../stakeholder/dashboard.php">Dashboard</a></li>
                <li><a href="../stakeholder/category_management.php">Categories</a></li>
                <li><a href="../stakeholder/availability_management.php">Availability</a></li>
                <li><a href="../stakeholder/payments.php">Payments</a></li>
                <li><a href="../stakeholder/logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="../index.php">Home</a></li>
                <li><a href="../vendor/login.php">Vendor Login</a></li>
                <li><a href="../stakeholder/login.php">Stakeholder Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>