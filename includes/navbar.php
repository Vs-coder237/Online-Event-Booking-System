<?php
/**
 * Navigation Bar
 * Online Event Booking System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Get current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Get cart count for logged in users
$cart_count = 0;
if (isLoggedIn()) {
    $cart_items = getCartItems($_SESSION['user_id']);
    $cart_count = array_sum(array_column($cart_items, 'quantity'));
}

// Helper function to check if current page matches
function isActivePage($page) {
    global $current_page, $current_dir;
    if (is_array($page)) {
        return in_array($current_page, $page) || in_array($current_dir, $page);
    }
    return $current_page == $page || $current_dir == $page;
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand fw-bold" href="<?php echo SITE_URL; ?>/index.php">
            <i class="fas fa-calendar-alt me-2"></i>
            <?php echo SITE_NAME; ?>
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo isActivePage(['index.php', '']) ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/index.php">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActivePage(['events.php', 'events']) ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/events/events.php">
                        <i class="fas fa-calendar me-1"></i>Events
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActivePage(['about.php', 'about']) ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/about.php">
                        <i class="fas fa-info-circle me-1"></i>About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActivePage(['contact.php', 'contact']) ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/contact.php">
                        <i class="fas fa-envelope me-1"></i>Contact
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-list me-1"></i>Categories
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                        <?php
                        $categories = getCategories();
                        if ($categories && count($categories) > 0):
                            foreach ($categories as $category):
                        ?>
                        <li>
                            <a class="dropdown-item" 
                               href="<?php echo SITE_URL; ?>/events/events.php?category=<?php echo urlencode($category['id']); ?>">
                                <i class="fas fa-tag me-2"></i>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </li>
                        <?php 
                            endforeach; 
                        else:
                        ?>
                        <li><span class="dropdown-item-text text-muted">No categories available</span></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/events/events.php">
                                <i class="fas fa-eye me-2"></i>View All Events
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
            
            <!-- Search Form -->
            <form class="d-flex me-3" action="<?php echo SITE_URL; ?>/events/search.php" method="GET" role="search">
                <div class="input-group">
                    <input class="form-control" type="search" name="q" placeholder="Search events..." 
                           aria-label="Search events"
                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button class="btn btn-outline-light" type="submit" aria-label="Search">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            
            <!-- User Menu -->
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <!-- Cart -->
                    <li class="nav-item">
                        <a class="nav-link position-relative <?php echo isActivePage(['cart.php', 'booking']) ? 'active' : ''; ?>" 
                           href="<?php echo SITE_URL; ?>/booking/cart.php" title="Shopping Cart">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cart_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cart_count; ?>
                                <span class="visually-hidden">items in cart</span>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <!-- Notifications (Optional) -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/notifications.php" title="Notifications">
                            <i class="fas fa-bell"></i>
                            <!-- Add notification count if needed -->
                        </a>
                    </li>
                    
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i>
                            <span class="d-none d-md-inline">
                                <?php echo htmlspecialchars(getCurrentUser()['first_name']); ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <h6 class="dropdown-header">
                                    <i class="fas fa-user me-2"></i>
                                    <?php 
                                    $user = getCurrentUser();
                                    echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); 
                                    ?>
                                </h6>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/user/profile.php">
                                    <i class="fas fa-user-edit me-2"></i>My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/user/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/booking/my-bookings.php">
                                    <i class="fas fa-history me-2"></i>My Bookings
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/user/settings.php">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/auth/logout.php" 
                                   onclick="return confirm('Are you sure you want to logout?')">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Login/Register for non-logged in users -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActivePage('login.php') ? 'active' : ''; ?>" 
                           href="<?php echo SITE_URL; ?>/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>
                            <span class="d-none d-md-inline">Login</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActivePage('register.php') ? 'active' : ''; ?>" 
                           href="<?php echo SITE_URL; ?>/auth/register.php">
                            <i class="fas fa-user-plus me-1"></i>
                            <span class="d-none d-md-inline">Register</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Add some custom CSS for better navigation experience -->
<style>
.navbar-nav .nav-link.active {
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 0.375rem;
}

.navbar-nav .nav-link:hover {
    background-color: rgba(255, 255, 255, 0.05);
    border-radius: 0.375rem;
    transition: all 0.3s ease;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border-radius: 0.5rem;
}

.dropdown-item:hover {
    background-color: var(--bs-primary);
    color: white;
}

.dropdown-item.text-danger:hover {
    background-color: var(--bs-danger);
    color: white;
}

@media (max-width: 991.98px) {
    .navbar-nav {
        padding-top: 1rem;
    }
    
    .navbar-nav .nav-link {
        padding: 0.5rem 1rem;
        margin: 0.25rem 0;
        border-radius: 0.375rem;
    }
}
</style>