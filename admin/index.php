<?php
/**
 * Admin Dashboard
 * Online Event Booking System
 */

require_once __DIR__ . '/../config/config.php';

// Check if admin is logged in
requireAdminLogin();

$current_admin = getCurrentAdmin();

// Get dashboard statistics
$stats = getDashboardStats();

/**
 * Get Dashboard Statistics
 */
function getDashboardStats() {
    global $pdo;
    
    $stats = [];
    
    // Total events
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM events");
    $stats['total_events'] = $stmt->fetch()['total'];
    
    // Published events
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM events WHERE status = 'published'");
    $stats['published_events'] = $stmt->fetch()['total'];
    
    // Total bookings
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
    $stats['total_bookings'] = $stmt->fetch()['total'];
    
    // Total revenue
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'paid'");
    $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $stmt->fetch()['total'];
    
    // Recent bookings (last 30 days)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['recent_bookings'] = $stmt->fetch()['total'];
    
    // Upcoming events
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM events WHERE status = 'published' AND event_date >= CURDATE()");
    $stats['upcoming_events'] = $stmt->fetch()['total'];
    
    // This month's revenue
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'paid' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
    $stats['monthly_revenue'] = $stmt->fetch()['total'] ?? 0;
    
    return $stats;
}

// Get recent bookings
function getRecentBookings($limit = 5) {
    global $pdo;
    
    $sql = "SELECT b.*, u.first_name, u.last_name, u.email
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            ORDER BY b.created_at DESC
            LIMIT ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Get upcoming events
function getUpcomingEventsAdmin($limit = 5) {
    global $pdo;
    
    $sql = "SELECT e.*, c.name as category_name, v.name as venue_name
            FROM events e
            LEFT JOIN categories c ON e.category_id = c.id
            LEFT JOIN venues v ON e.venue_id = v.id
            WHERE e.status = 'published' AND e.event_date >= CURDATE()
            ORDER BY e.event_date ASC, e.start_time ASC
            LIMIT ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

$recent_bookings = getRecentBookings();
$upcoming_events = getUpcomingEventsAdmin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #6366f1;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
        }
        
        body {
            background-color: var(--light-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 2px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateX(5px);
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }
        
        .card-modern {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .card-modern .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 25px;
        }
        
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .table-modern {
            border: none;
        }
        
        .table-modern th {
            border: none;
            background: var(--light-color);
            color: var(--dark-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            padding: 15px;
        }
        
        .table-modern td {
            border: none;
            padding: 15px;
            vertical-align: middle;
        }
        
        .table-modern tbody tr {
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .table-modern tbody tr:hover {
            background: rgba(79, 70, 229, 0.02);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-4 text-center border-bottom border-light border-opacity-25">
                        <h5 class="text-white mb-0"><?php echo SITE_NAME; ?></h5>
                        <small class="text-white-50">Admin Panel</small>
                    </div>
                    
                    <div class="p-3">
                        <div class="text-white-50 mb-2 px-3">
                            <small>Welcome back,</small><br>
                            <strong class="text-white"><?php echo htmlspecialchars($current_admin['full_name']); ?></strong>
                        </div>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="events/manage-events.php">
                            <i class="fas fa-calendar-alt me-2"></i> Events
                        </a>
                        <a class="nav-link" href="bookings/view-bookings.php">
                            <i class="fas fa-ticket-alt me-2"></i> Bookings
                        </a>
                        <a class="nav-link" href="reports/reports.php">
                            <i class="fas fa-chart-bar me-2"></i> Reports
                        </a>
                        <div class="dropdown-divider mx-3 my-2"></div>
                        <a class="nav-link" href="<?php echo SITE_URL; ?>" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i> View Site
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="fw-bold text-dark mb-1">Dashboard Overview</h2>
                            <p class="text-muted mb-0">Welcome to your admin dashboard</p>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">Last updated</small>
                            <strong><?php echo date('M d, Y g:i A'); ?></strong>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <h3 class="fw-bold mb-1"><?php echo number_format($stats['total_events']); ?></h3>
                                        <p class="text-muted mb-0 small">Total Events</p>
                                    </div>
                                    <span class="badge bg-primary"><?php echo $stats['published_events']; ?> Published</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, var(--success-color), #059669);">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <h3 class="fw-bold mb-1"><?php echo number_format($stats['total_bookings']); ?></h3>
                                        <p class="text-muted mb-0 small">Total Bookings</p>
                                    </div>
                                    <span class="badge bg-success"><?php echo $stats['recent_bookings']; ?> This Month</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning-color), #d97706);">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <h3 class="fw-bold mb-1"><?php echo formatCurrency($stats['total_revenue']); ?></h3>
                                        <p class="text-muted mb-0 small">Total Revenue</p>
                                    </div>
                                    <span class="badge bg-warning"><?php echo formatCurrency($stats['monthly_revenue']); ?> This Month</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, var(--info-color), #1d4ed8);">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <h3 class="fw-bold mb-1"><?php echo number_format($stats['total_users']); ?></h3>
                                        <p class="text-muted mb-0 small">Registered Users</p>
                                    </div>
                                    <span class="badge bg-info"><?php echo $stats['upcoming_events']; ?> Upcoming Events</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="row g-4">
                        <!-- Recent Bookings -->
                        <div class="col-lg-8">
                            <div class="card card-modern">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0 fw-bold">Recent Bookings</h5>
                                        <a href="bookings/view-bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-modern mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Booking Reference</th>
                                                    <th>Customer</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($recent_bookings)): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted py-4">
                                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                                            No recent bookings found
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($recent_bookings as $booking): ?>
                                                        <tr>
                                                            <td>
                                                                <span class="fw-bold"><?php echo htmlspecialchars($booking['booking_reference']); ?></span>
                                                            </td>
                                                            <td>
                                                                <div>
                                                                    <div class="fw-medium"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></div>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span class="fw-bold"><?php echo formatCurrency($booking['total_amount']); ?></span>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                $status_class = '';
                                                                switch ($booking['booking_status']) {
                                                                    case 'confirmed':
                                                                        $status_class = 'bg-success';
                                                                        break;
                                                                    case 'pending':
                                                                        $status_class = 'bg-warning';
                                                                        break;
                                                                    case 'cancelled':
                                                                        $status_class = 'bg-danger';
                                                                        break;
                                                                }
                                                                ?>
                                                                <span class="badge badge-status <?php echo $status_class; ?>">
                                                                    <?php echo ucfirst($booking['booking_status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="text-muted"><?php echo formatDate($booking['created_at']); ?></span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Upcoming Events -->
                        <div class="col-lg-4">
                            <div class="card card-modern">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0 fw-bold">Upcoming Events</h5>
                                        <a href="events/manage-events.php" class="btn btn-sm btn-outline-primary">Manage</a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($upcoming_events)): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-calendar-plus fa-2x mb-2 d-block"></i>
                                            <p class="mb-2">No upcoming events</p>
                                            <a href="events/add-event.php" class="btn btn-sm btn-primary">Create Event</a>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($upcoming_events as $event): ?>
                                            <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                                                <div class="flex-grow-1">
                                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($event['title']); ?></h6>
                                                    <div class="text-muted small mb-1">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?php echo formatDate($event['event_date']); ?>
                                                    </div>
                                                    <div class="text-muted small mb-1">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo formatTime($event['start_time']); ?>
                                                    </div>
                                                    <?php if ($event['venue_name']): ?>
                                                        <div class="text-muted small">
                                                            <i class="fas fa-map-marker-alt me-1"></i>
                                                            <?php echo htmlspecialchars($event['venue_name']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fw-bold text-primary"><?php echo formatCurrency($event['price']); ?></div>
                                                    <small class="text-muted"><?php echo $event['available_tickets']; ?> tickets left</small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>