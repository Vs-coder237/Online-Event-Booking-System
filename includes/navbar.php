<?php
/**
 * Navigation Bar
 * Online Event Booking System
 */

// Get cart count for logged in users
$cart_count = 0;
if (isLoggedIn()) {
    $cart_items = getCartItems($_SESSION['user_id']);
    $cart_count = array_sum(array_column($cart_items, 'quantity'));
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand fw-bold" href="<?php echo SITE_URL; ?>">
            <i class="fas fa-calendar-alt me-2"></i>
            <?php echo SITE_NAME; ?>
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/events/events.php">
                        <i class="fas fa-calendar me-1"></i>Events
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-list me-1"></i>Categories
                    </a>
                    <ul class="dropdown-menu">
                        <?php
                        $categories = getCategories();
                        foreach ($categories as $category):
                        ?>
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/events/events.php?category=<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>
            
            <!-- Search Form -->
            <form class="d-flex me-3" action="<?php echo SITE_URL; ?>/events/search.php" method="GET">
                <div class="input-group">
                    <input class="form-control" type="search" name="q" placeholder="Search events..." 
                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            
            <!-- User Menu -->
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <!-- Cart -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/booking/cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cart_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cart_count; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars(getCurrentUser()['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/auth/profile.php">
                                    <i class="fas fa-user-edit me-2"></i>Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/booking/booking-history.php">
                                    <i class="fas fa-history me-2"></i>My Bookings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Login/Register -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/auth/register.php">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>