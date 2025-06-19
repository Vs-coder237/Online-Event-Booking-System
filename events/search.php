<?php
/**
 * Event Search Page
 * Online Event Booking System
 */

require_once '../config/config.php';

// Get search parameters
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$venue_id = isset($_GET['venue']) ? (int)$_GET['venue'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sort_by = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'event_date';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * EVENTS_PER_PAGE;

// Build search query
$sql = "SELECT e.*, c.name as category_name, v.name as venue_name, v.city as venue_city 
        FROM events e 
        LEFT JOIN categories c ON e.category_id = c.id 
        LEFT JOIN venues v ON e.venue_id = v.id 
        WHERE e.status = 'published' AND e.event_date >= CURDATE()";

$params = [];

// Add search conditions
if (!empty($search_query)) {
    $sql .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.organizer_name LIKE ?)";
    $search_term = '%' . $search_query . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($category_id > 0) {
    $sql .= " AND e.category_id = ?";
    $params[] = $category_id;
}

if ($venue_id > 0) {
    $sql .= " AND e.venue_id = ?";
    $params[] = $venue_id;
}

if (!empty($date_from)) {
    $sql .= " AND e.event_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND e.event_date <= ?";
    $params[] = $date_to;
}

if ($min_price > 0) {
    $sql .= " AND e.price >= ?";
    $params[] = $min_price;
}

if ($max_price > 0) {
    $sql .= " AND e.price <= ?";
    $params[] = $max_price;
}

// Add sorting
$allowed_sort = ['event_date', 'title', 'price', 'created_at'];
if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'event_date';
}

$sort_order = (isset($_GET['order']) && $_GET['order'] === 'desc') ? 'DESC' : 'ASC';
$sql .= " ORDER BY e.$sort_by $sort_order";

// Get total count for pagination
$count_sql = str_replace(
    "SELECT e.*, c.name as category_name, v.name as venue_name, v.city as venue_city",
    "SELECT COUNT(*)",
    $sql
);
$count_sql = str_replace(" ORDER BY e.$sort_by $sort_order", "", $count_sql);

try {
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_events = $count_stmt->fetchColumn();
    
    $total_pages = ceil($total_events / EVENTS_PER_PAGE);
    
    // Add pagination to main query
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = EVENTS_PER_PAGE;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Search Error: " . $e->getMessage());
    $events = [];
    $total_events = 0;
    $total_pages = 0;
}

// Get all categories for filter dropdown
try {
    $categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $categories_stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Get all venues for filter dropdown
try {
    $venues_stmt = $pdo->query("SELECT * FROM venues ORDER BY name");
    $venues = $venues_stmt->fetchAll();
} catch (PDOException $e) {
    $venues = [];
}

$page_title = 'Search Events';
if (!empty($search_query)) {
    $page_title .= ' - "' . htmlspecialchars($search_query) . '"';
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container my-5">
    <!-- Search Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h3 mb-3">
                <?php if (!empty($search_query)): ?>
                    Search Results for "<?php echo htmlspecialchars($search_query); ?>"
                <?php else: ?>
                    Search Events
                <?php endif; ?>
            </h2>
            <p class="text-muted">
                <?php echo $total_events; ?> event<?php echo $total_events !== 1 ? 's' : ''; ?> found
            </p>
        </div>
    </div>

    <!-- Advanced Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <button class="btn btn-link text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#searchFilters" aria-expanded="false">
                            <i class="fas fa-filter me-2"></i>Advanced Search & Filters
                        </button>
                    </h5>
                </div>
                <div class="collapse <?php echo (!empty($search_query) || $category_id || $venue_id || $date_from || $date_to || $min_price || $max_price) ? 'show' : ''; ?>" id="searchFilters">
                    <div class="card-body">
                        <form method="GET" action="search.php">
                            <div class="row g-3">
                                <!-- Search Query -->
                                <div class="col-md-12">
                                    <label for="search_query" class="form-label">Search Keywords</label>
                                    <input type="text" class="form-control" id="search_query" name="q" 
                                           value="<?php echo htmlspecialchars($search_query); ?>" 
                                           placeholder="Event title, description, or organizer name...">
                                </div>

                                <!-- Category Filter -->
                                <div class="col-md-6">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Venue Filter -->
                                <div class="col-md-6">
                                    <label for="venue" class="form-label">Venue</label>
                                    <select class="form-select" id="venue" name="venue">
                                        <option value="">All Venues</option>
                                        <?php foreach ($venues as $venue): ?>
                                            <option value="<?php echo $venue['id']; ?>" 
                                                    <?php echo $venue_id == $venue['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($venue['name'] . ' - ' . $venue['city']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Date Range -->
                                <div class="col-md-6">
                                    <label for="date_from" class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" 
                                           value="<?php echo $date_from; ?>" min="<?php echo date('Y-m-d'); ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="date_to" class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" 
                                           value="<?php echo $date_to; ?>" min="<?php echo date('Y-m-d'); ?>">
                                </div>

                                <!-- Price Range -->
                                <div class="col-md-6">
                                    <label for="min_price" class="form-label">Min Price (<?php echo CURRENCY_SYMBOL; ?>)</label>
                                    <input type="number" class="form-control" id="min_price" name="min_price" 
                                           value="<?php echo $min_price > 0 ? $min_price : ''; ?>" min="0" step="0.01">
                                </div>

                                <div class="col-md-6">
                                    <label for="max_price" class="form-label">Max Price (<?php echo CURRENCY_SYMBOL; ?>)</label>
                                    <input type="number" class="form-control" id="max_price" name="max_price" 
                                           value="<?php echo $max_price > 0 ? $max_price : ''; ?>" min="0" step="0.01">
                                </div>

                                <!-- Sort Options -->
                                <div class="col-md-6">
                                    <label for="sort" class="form-label">Sort By</label>
                                    <select class="form-select" id="sort" name="sort">
                                        <option value="event_date" <?php echo $sort_by === 'event_date' ? 'selected' : ''; ?>>Event Date</option>
                                        <option value="title" <?php echo $sort_by === 'title' ? 'selected' : ''; ?>>Title</option>
                                        <option value="price" <?php echo $sort_by === 'price' ? 'selected' : ''; ?>>Price</option>
                                        <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Newest First</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="order" class="form-label">Order</label>
                                    <select class="form-select" id="order" name="order">
                                        <option value="asc" <?php echo (!isset($_GET['order']) || $_GET['order'] === 'asc') ? 'selected' : ''; ?>>Ascending</option>
                                        <option value="desc" <?php echo (isset($_GET['order']) && $_GET['order'] === 'desc') ? 'selected' : ''; ?>>Descending</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-search me-1"></i>Search
                                    </button>
                                    <a href="search.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Clear All
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Results -->
    <div class="row">
        <?php if (empty($events)): ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4>No Events Found</h4>
                    <p class="text-muted">Try adjusting your search criteria or browse all events.</p>
                    <a href="events.php" class="btn btn-primary">Browse All Events</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($event['image'])): ?>
                            <img src="../assets/images/events/<?php echo htmlspecialchars($event['image']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($event['title']); ?>" 
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                 style="height: 200px;">
                                <i class="fas fa-calendar-alt fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <?php if ($event['category_name']): ?>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($event['category_name']); ?></span>
                                <?php endif; ?>
                                <?php if ($event['available_tickets'] < 10 && $event['available_tickets'] > 0): ?>
                                    <span class="badge bg-warning">Few Tickets Left</span>
                                <?php elseif ($event['available_tickets'] == 0): ?>
                                    <span class="badge bg-danger">Sold Out</span>
                                <?php endif; ?>
                            </div>
                            
                            <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                            <p class="card-text text-muted small">
                                <?php echo htmlspecialchars(substr($event['description'], 0, 100)) . (strlen($event['description']) > 100 ? '...' : ''); ?>
                            </p>
                            
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo formatDate($event['event_date']); ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo formatTime($event['start_time']); ?>
                                    </small>
                                </div>
                                
                                <?php if ($event['venue_name']): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($event['venue_name'] . ', ' . $event['venue_city']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 mb-0 text-primary">
                                        <?php echo formatCurrency($event['price']); ?>
                                    </span>
                                    <a href="event-details.php?id=<?php echo $event['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="row mt-4">
            <div class="col-12">
                <nav aria-label="Search Results Pagination">
                    <ul class="pagination justify-content-center">
                        <?php
                        // Build query string for pagination links
                        $query_params = $_GET;
                        unset($query_params['page']);
                        $query_string = http_build_query($query_params);
                        $query_string = $query_string ? '&' . $query_string : '';
                        ?>
                        
                        <!-- Previous Page -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $query_string; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $query_string; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Next Page -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $query_string; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Auto-submit form when sort or order changes
document.getElementById('sort').addEventListener('change', function() {
    this.form.submit();
});

document.getElementById('order').addEventListener('change', function() {
    this.form.submit();
});

// Date validation
document.getElementById('date_from').addEventListener('change', function() {
    const dateFrom = this.value;
    const dateTo = document.getElementById('date_to');
    
    if (dateFrom) {
        dateTo.min = dateFrom;
    }
});

document.getElementById('date_to').addEventListener('change', function() {
    const dateTo = this.value;
    const dateFrom = document.getElementById('date_from');
    
    if (dateTo && dateFrom.value) {
        if (dateTo < dateFrom.value) {
            dateFrom.value = dateTo;
        }
    }
});

// Price validation
document.getElementById('min_price').addEventListener('change', function() {
    const minPrice = parseFloat(this.value) || 0;
    const maxPrice = document.getElementById('max_price');
    
    if (maxPrice.value && minPrice > parseFloat(maxPrice.value)) {
        maxPrice.value = minPrice;
    }
});

document.getElementById('max_price').addEventListener('change', function() {
    const maxPrice = parseFloat(this.value) || 0;
    const minPrice = document.getElementById('min_price');
    
    if (minPrice.value && maxPrice < parseFloat(minPrice.value)) {
        minPrice.value = maxPrice;
    }
});
</script>

<?php include '../includes/footer.php'; ?>