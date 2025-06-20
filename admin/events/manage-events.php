<?php
/**
 * Admin Manage Events Page
 * Online Event Booking System
 */

require_once '../../config/config.php';
require_once '../../includes/functions.php';
requireAdminLogin();

// Handle delete event
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $event_id = (int)$_GET['id'];
    
    try {
        // Check if event has bookings
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking_items WHERE event_id = ?");
        $stmt->execute([$event_id]);
        $booking_count = $stmt->fetchColumn();
        
        if ($booking_count > 0) {
            setFlashMessage('error', 'Cannot delete event with existing bookings. Cancel the event instead.');
        } else {
            // Delete event image if exists
            $stmt = $pdo->prepare("SELECT image FROM events WHERE id = ?");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch();
            
            if ($event && $event['image'] && file_exists('../../' . UPLOAD_PATH . $event['image'])) {
                unlink('../../' . UPLOAD_PATH . $event['image']);
            }
            
            // Delete event
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$event_id]);
            
            logActivity('event_deleted', "Event ID: $event_id deleted", 'admin');
            setFlashMessage('success', 'Event deleted successfully.');
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'Error deleting event: ' . $e->getMessage());
    }
    
    header('Location: manage-events.php');
    exit();
}

// Handle status change
if (isset($_POST['change_status'])) {
    $event_id = (int)$_POST['event_id'];
    $new_status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE events SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $event_id]);
        
        logActivity('event_status_changed', "Event ID: $event_id status changed to $new_status", 'admin');
        setFlashMessage('success', 'Event status updated successfully.');
    } catch (Exception $e) {
        setFlashMessage('error', 'Error updating status: ' . $e->getMessage());
    }
    
    header('Location: manage-events.php');
    exit();
}

// Pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : '';
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = ['1=1'];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(e.title LIKE ? OR e.description LIKE ? OR e.organizer_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "e.status = ?";
    $params[] = $status_filter;
}

if (!empty($category_filter)) {
    $where_conditions[] = "e.category_id = ?";
    $params[] = $category_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) FROM events e WHERE $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_events = $stmt->fetchColumn();
$total_pages = ceil($total_events / $limit);

// Get events
$sql = "SELECT e.*, c.name as category_name, v.name as venue_name, v.city as venue_city,
               (SELECT COUNT(*) FROM booking_items bi WHERE bi.event_id = e.id) as booking_count
        FROM events e
        LEFT JOIN categories c ON e.category_id = c.id
        LEFT JOIN venues v ON e.venue_id = v.id
        WHERE $where_clause
        ORDER BY e.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Get categories for filter
$categories = getCategories();

$flash_message = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Admin | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include '../../includes/admin-header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/admin-sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Events</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add-event.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Event
                        </a>
                    </div>
                </div>

                <?php if ($flash_message): ?>
                    <div class="alert alert-<?php echo $flash_message['type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $flash_message['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" placeholder="Search events..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Events Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($events)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5>No Events Found</h5>
                                <p class="text-muted">No events match your current filters.</p>
                                <a href="add-event.php" class="btn btn-primary">Add Your First Event</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Event</th>
                                            <th>Date & Time</th>
                                            <th>Category</th>
                                            <th>Venue</th>
                                            <th>Price</th>
                                            <th>Tickets</th>
                                            <th>Bookings</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($events as $event): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($event['image']): ?>
                                                            <img src="../../<?php echo UPLOAD_PATH . $event['image']; ?>" 
                                                                 alt="Event Image" class="rounded me-3" 
                                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                                                                 style="width: 50px; height: 50px;">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($event['title']); ?></h6>
                                                            <small class="text-muted">by <?php echo htmlspecialchars($event['organizer_name']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo formatDate($event['event_date']); ?></strong><br>
                                                        <small class="text-muted">
                                                            <?php echo formatTime($event['start_time']); ?> - 
                                                            <?php echo formatTime($event['end_time']); ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($event['category_name'] ?? 'Uncategorized'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($event['venue_name'] ?? 'TBD'); ?></strong>
                                                        <?php if ($event['venue_city']): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($event['venue_city']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?php echo formatCurrency($event['price']); ?></strong>
                                                </td>
                                                <td>
                                                    <div>
                                                        <span class="text-success"><?php echo $event['available_tickets']; ?></span> / 
                                                        <span class="text-muted"><?php echo $event['total_tickets']; ?></span>
                                                    </div>
                                                    <small class="text-muted">Available / Total</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $event['booking_count']; ?></span>
                                                </td>
                                                <td>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                        <select name="status" class="form-select form-select-sm" 
                                                                onchange="this.form.submit()">
                                                            <option value="draft" <?php echo $event['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                            <option value="published" <?php echo $event['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                                            <option value="cancelled" <?php echo $event['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                        </select>
                                                        <button type="submit" name="change_status" style="display: none;"></button>
                                                    </form>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="../../events/event-details.php?id=<?php echo $event['id']; ?>" 
                                                           class="btn btn-sm btn-outline-info" title="View" target="_blank">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit-event.php?id=<?php echo $event['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($event['booking_count'] == 0): ?>
                                                            <a href="?delete=1&id=<?php echo $event['id']; ?>" 
                                                               class="btn btn-sm btn-outline-danger" title="Delete"
                                                               onclick="return confirm('Are you sure you want to delete this event?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Events pagination">
                                    <ul class="pagination justify-content-center mt-4">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo $category_filter; ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo $category_filter; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo $category_filter; ?>">Next</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>