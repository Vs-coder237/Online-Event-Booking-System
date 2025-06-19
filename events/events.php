<?php
/**
 * Events Listing Page
 * Online Event Booking System
 */

session_start();
require_once '../includes/functions.php';

// Get filter parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_asc';

// Validate page number
if ($page < 1) $page = 1;

// Get events with filters
$events = getEvents($page, $search, $category, $date);
$total_events = getTotalEventsCount($search, $category, $date);
$total_pages = ceil($total_events / EVENTS_PER_PAGE);

// Get categories for filter dropdown
$categories = getCategories();

// Build current URL for pagination
$current_params = array_filter([
    'search' => $search,
    'category' => $category,
    'date' => $date,
    'sort' => $sort
]);

$page_title = "Browse Events";
include '../includes/header.php';
?>

<div class="container mt-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="display-4 mb-2">Browse Events</h1>
            <p class="lead text-muted">Discover amazing events happening near you</p>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <!-- Search -->
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Events</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search by title or description..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="col-md-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo ($category == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Date Filter -->
                        <div class="col-md-3">
                            <label for="date" class="form-label">Event Date</label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="<?php echo htmlspecialchars($date); ?>" 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <!-- Search Button -->
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Header -->
    <div class="row mb-3">
        <div class="col-md-8">
            <h5 class="mb-0">
                <?php if ($total_events > 0): ?>
                    Showing <?php echo (($page - 1) * EVENTS_PER_PAGE) + 1; ?> - 
                    <?php echo min($page * EVENTS_PER_PAGE, $total_events); ?> of 
                    <?php echo $total_events; ?> events
                    <?php if ($search || $category || $date): ?>
                        <small class="text-muted">(filtered)</small>
                    <?php endif; ?>
                <?php else: ?>
                    No events found
                <?php endif; ?>
            </h5>
        </div>
        <div class="col-md-4 text-end">
            <?php if ($search || $category || $date): ?>
                <a href="events.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Events Grid -->
    <?php if (!empty($events)): ?>
        <div class="row">
            <?php foreach ($events as $event): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 event-card">
                        <!-- Event Image -->
                        <div class="card-img-top-wrapper" style="height: 200px; overflow: hidden;">
                            <?php if ($event['image']): ?>
                                <img src="../assets/images/events/<?php echo htmlspecialchars($event['image']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($event['title']); ?>"
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center h-100">
                                    <i class="fas fa-calendar-alt fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body d-flex flex-column">
                            <!-- Event Category -->
                            <?php if ($event['category_name']): ?>
                                <span class="badge bg-primary mb-2 align-self-start">
                                    <?php echo htmlspecialchars($event['category_name']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <!-- Event Title -->
                            <h5 class="card-title">
                                <a href="event-details.php?id=<?php echo $event['id']; ?>" 
                                   class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($event['title']); ?>
                                </a>
                            </h5>
                            
                            <!-- Event Description -->
                            <p class="card-text text-muted flex-grow-1">
                                <?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?>
                            </p>
                            
                            <!-- Event Details -->
                            <div class="event-details mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-calendar text-primary me-2"></i>
                                    <small class="text-muted">
                                        <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
                                    </small>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-clock text-primary me-2"></i>
                                    <small class="text-muted">
                                        <?php echo date('g:i A', strtotime($event['start_time'])); ?>
                                    </small>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($event['venue_name'] . ', ' . $event['venue_city']); ?>
                                    </small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-ticket-alt text-primary me-2"></i>
                                    <small class="text-muted">
                                        <?php echo $event['available_tickets']; ?> tickets available
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Price and Action -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="price">
                                    <?php if ($event['price'] > 0): ?>
                                        <h5 class="text-primary mb-0">$<?php echo number_format($event['price'], 2); ?></h5>
                                    <?php else: ?>
                                        <h5 class="text-success mb-0">FREE</h5>
                                    <?php endif; ?>
                                </div>
                                <div class="action-buttons">
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
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <?php echo generatePagination($page, $total_pages, 'events.php', $current_params); ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- No Events Found -->
        <div class="row">
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No Events Found</h4>
                    <p class="text-muted">
                        <?php if ($search || $category || $date): ?>
                            We couldn't find any events matching your criteria. Try adjusting your filters.
                        <?php else: ?>
                            There are no events available at the moment. Please check back later.
                        <?php endif; ?>
                    </p>
                    <?php if ($search || $category || $date): ?>
                        <a href="events.php" class="btn btn-primary">Browse All Events</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.event-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.event-card .card-title a:hover {
    color: #007bff !important;
}

.card-img-top-wrapper {
    position: relative;
    background: #f8f9fa;
}

.badge {
    font-size: 0.75em;
}

.event-details .fas {
    width: 16px;
}

@media (max-width: 768px) {
    .display-4 {
        font-size: 2rem;
    }
    
    .event-card {
        margin-bottom: 1rem;
    }
}
</style>

<?php include '../includes/footer.php'; ?>