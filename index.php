<?php
/**
 * Homepage
 * Online Event Booking System
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = 'Welcome to ' . SITE_NAME;

// Get featured events
$upcoming_events = getUpcomingEvents(6);
$popular_events = getPopularEvents(6);

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Discover Amazing Events</h1>
                <p class="lead mb-4">Find and book tickets for the best concerts, sports events, conferences, and entertainment in your area.</p>
                <div class="d-flex gap-3">
                    <a href="events/events.php" class="btn btn-light btn-lg">
                        <i class="fas fa-calendar me-2"></i>Browse Events
                    </a>
                    <?php if (!isLoggedIn()): ?>
                    <a href="auth/register.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Join Now
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 text-center mt-4 mt-lg-0">
                <img src="assets/images/hero-events.svg" alt="Events" class="img-fluid" style="max-height: 400px;">
            </div>
        </div>
    </div>
</section>

<!-- Search Section -->
<section class="search-section py-4 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <form action="events/search.php" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="q" placeholder="Search events...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach (getCategories() as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Upcoming Events Section -->
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-8">
                <h2 class="h3 mb-0">Upcoming Events</h2>
                <p class="text-muted">Don't miss out on these amazing upcoming events</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="events/events.php" class="btn btn-outline-primary">
                    View All Events <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
        
        <div class="row">
            <?php if (!empty($upcoming_events)): ?>
                <?php foreach ($upcoming_events as $event): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <?php if ($event['image']): ?>
                        <img src="assets/images/events/<?php echo htmlspecialchars($event['image']); ?>" 
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
                                <span class="badge bg-primary"><?php echo htmlspecialchars($event['category_name']); ?></span>
                                <span class="badge bg-success"><?php echo formatCurrency($event['price']); ?></span>
                            </div>
                            
                            <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                            
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo formatDate($event['event_date']); ?>
                                </small><br>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo formatTime($event['start_time']); ?>
                                </small><br>
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($event['venue_name'] . ', ' . $event['venue_city']); ?>
                                </small>
                            </div>
                            
                            <p class="card-text text-muted small">
                                <?php echo htmlspecialchars(substr($event['description'], 0, 100)); ?>...
                            </p>
                            
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <?php echo $event['available_tickets']; ?> tickets left
                                    </small>
                                    <a href="events/event-details.php?id=<?php echo $event['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No upcoming events found</h4>
                    <p class="text-muted">Check back later for new events!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Popular Events Section -->
<?php if (!empty($popular_events)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-8">
                <h2 class="h3 mb-0">Popular Events</h2>
                <p class="text-muted">Most booked events this month</p>
            </div>
        </div>
        
        <div class="row">
            <?php foreach ($popular_events as $event): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    <?php if ($event['image']): ?>
                    <img src="assets/images/events/<?php echo htmlspecialchars($event['image']); ?>" 
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
                            <span class="badge bg-primary"><?php echo htmlspecialchars($event['category_name']); ?></span>
                            <span class="badge bg-success"><?php echo formatCurrency($event['price']); ?></span>
                            <span class="badge bg-warning">Popular</span>
                        </div>
                        
                        <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo formatDate($event['event_date']); ?>
                            </small><br>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo formatTime($event['start_time']); ?>
                            </small><br>
                            <small class="text-muted">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?php echo htmlspecialchars($event['venue_name'] . ', ' . $event['venue_city']); ?>
                            </small>
                        </div>
                        
                        <p class="card-text text-muted small">
                            <?php echo htmlspecialchars(substr($event['description'], 0, 100)); ?>...
                        </p>
                        
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-success">
                                    <i class="fas fa-fire me-1"></i><?php echo $event['booking_count']; ?> bookings
                                </small>
                                <a href="events/event-details.php?id=<?php echo $event['id']; ?>" 
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
    </div>
</section>
<?php endif; ?>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-12">
                <h2 class="h3 mb-3">Why Choose Our Platform?</h2>
                <p class="text-muted">Experience seamless event booking with our feature-rich platform</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="text-center">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-search fa-2x"></i>
                    </div>
                    <h5>Easy Search</h5>
                    <p class="text-muted">Find events quickly with our powerful search and filter options</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="text-center">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-lock fa-2x"></i>
                    </div>
                    <h5>Secure Booking</h5>
                    <p class="text-muted">Your payments and personal information are always protected</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="text-center">
                    <div class="bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-mobile-alt fa-2x"></i>
                    </div>
                    <h5>Mobile Friendly</h5>
                    <p class="text-muted">Book events on the go with our responsive mobile design</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="text-center">
                    <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-headset fa-2x"></i>
                    </div>
                    <h5>24/7 Support</h5>
                    <p class="text-muted">Get help whenever you need it with our dedicated support team</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h3 class="mb-2">Stay Updated</h3>
                <p class="mb-0">Subscribe to get notified about new events and exclusive offers</p>
            </div>
            <div class="col-lg-6">
                <form class="row g-3" action="newsletter/subscribe.php" method="POST">
                    <div class="col-md-8">
                        <input type="email" class="form-control" name="email" placeholder="Enter your email" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-light w-100">Subscribe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>