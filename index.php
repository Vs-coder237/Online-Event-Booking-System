<?php
/**
 * Homepage - Enhanced Animated Version
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

<!-- Custom Styles for Enhanced Animations -->
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        --dark-gradient: linear-gradient(135deg, #434343 0%, #000000 100%);
    }

    /* Global Animation Classes */
    .fade-in-up {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.8s ease-out;
    }

    .fade-in-up.animate {
        opacity: 1;
        transform: translateY(0);
    }

    .fade-in-left {
        opacity: 0;
        transform: translateX(-30px);
        transition: all 0.8s ease-out;
    }

    .fade-in-left.animate {
        opacity: 1;
        transform: translateX(0);
    }

    .fade-in-right {
        opacity: 0;
        transform: translateX(30px);
        transition: all 0.8s ease-out;
    }

    .fade-in-right.animate {
        opacity: 1;
        transform: translateX(0);
    }

    .scale-in {
        opacity: 0;
        transform: scale(0.8);
        transition: all 0.6s ease-out;
    }

    .scale-in.animate {
        opacity: 1;
        transform: scale(1);
    }

    /* Hero Section Enhancements */
    .hero-section {
        background: var(--primary-gradient);
        height: 500px;
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,0 1000,300 1000,1000 0,700"/></svg>');
        z-index: 1;
    }

    .hero-content {
        position: relative;
        z-index: 2;
    }

    .hero-title {
        font-size: 3.5rem;
        font-weight: 800;
        background: linear-gradient(45deg, #fff, #f8f9fa);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 1.5rem;
    }

    .hero-subtitle {
        font-size: 1.3rem;
        opacity: 0.9;
        margin-bottom: 2rem;
    }

    .hero-buttons .btn {
        padding: 12px 30px;
        font-weight: 600;
        border-radius: 50px;
        transition: all 0.3s ease;
        margin: 0 10px 10px 0;
    }

    .hero-buttons .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    .hero-image {
        animation: float 6s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }

    /* Floating Particles Animation */
    .particles {
        position: absolute;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: 1;
    }

    .particle {
        position: absolute;
        width: 4px;
        height: 4px;
        background: rgba(255,255,255,0.3);
        border-radius: 50%;
        animation: particle-float 8s infinite linear;
    }

    @keyframes particle-float {
        0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
        10% { opacity: 1; }
        90% { opacity: 1; }
        100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
    }

    /* Search Section */
    .search-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 3rem 0;
        position: relative;
    }

    .search-form {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .search-form:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 50px rgba(0,0,0,0.15);
    }

    .search-form .form-control, .search-form .form-select {
        border-radius: 15px;
        border: 2px solid #e9ecef;
        padding: 12px 20px;
        transition: all 0.3s ease;
    }

    .search-form .form-control:focus, .search-form .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        transform: scale(1.02);
    }

    .search-btn {
        background: var(--primary-gradient);
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .search-btn:hover {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    /* Card Enhancements */
    .event-card {
        border: none;
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        background: white;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        position: relative;
    }

    .event-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--primary-gradient);
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 1;
    }

    .event-card:hover {
        transform: translateY(-15px) scale(1.02);
        box-shadow: 0 25px 50px rgba(0,0,0,0.2);
    }

    .event-card:hover::before {
        opacity: 0.05;
    }

    .event-card .card-img-top {
        height: 250px;
        object-fit: cover;
        transition: all 0.4s ease;
    }

    .event-card:hover .card-img-top {
        transform: scale(1.1);
    }

    .event-card .card-body {
        position: relative;
        z-index: 2;
        padding: 1.5rem;
    }

    .event-badge {
        display: inline-block;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin: 2px;
        transition: all 0.3s ease;
    }

    .event-badge:hover {
        transform: scale(1.1);
    }

    .badge-primary { background: var(--primary-gradient); }
    .badge-success { background: var(--success-gradient); }
    .badge-warning { background: var(--warning-gradient); }

    /* Feature Icons */
    .feature-icon {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        transition: all 0.4s ease;
        position: relative;
        overflow: hidden;
    }

    .feature-icon::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255,255,255,0.3);
        transition: all 0.6s ease;
        transform: translate(-50%, -50%);
    }

    .feature-icon:hover::before {
        width: 100%;
        height: 100%;
    }

    .feature-icon:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 15px 30px rgba(0,0,0,0.2);
    }

    .feature-primary { background: var(--primary-gradient); }
    .feature-success { background: var(--success-gradient); }
    .feature-info { background: var(--warning-gradient); }
    .feature-warning { background: var(--secondary-gradient); }

    /* Newsletter Section */
    .newsletter-section {
        background: var(--dark-gradient);
        position: relative;
        overflow: hidden;
    }

    .newsletter-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><circle fill="rgba(255,255,255,0.03)" cx="200" cy="200" r="100"/><circle fill="rgba(255,255,255,0.03)" cx="800" cy="300" r="150"/><circle fill="rgba(255,255,255,0.03)" cx="400" cy="600" r="80"/></svg>');
    }

    .newsletter-form {
        position: relative;
        z-index: 2;
    }

    .newsletter-input {
        border-radius: 50px;
        border: 2px solid rgba(255,255,255,0.2);
        background: rgba(255,255,255,0.1);
        color: white;
        padding: 15px 25px;
        transition: all 0.3s ease;
    }

    .newsletter-input:focus {
        background: rgba(255,255,255,0.2);
        border-color: rgba(255,255,255,0.5);
        color: white;
        box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.25);
    }

    .newsletter-input::placeholder {
        color: rgba(255,255,255,0.7);
    }

    .newsletter-btn {
        background: white;
        color: #333;
        border: none;
        border-radius: 50px;
        padding: 15px 30px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .newsletter-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        background: #f8f9fa;
    }

    /* Section Spacing */
    .section-padding {
        padding: 5rem 0;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 2.5rem;
        }
        
        .hero-subtitle {
            font-size: 1.1rem;
        }
        
        .search-form {
            padding: 1.5rem;
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
        }
    }

    /* Loading Animation */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: var(--primary-gradient);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.5s ease;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid rgba(255,255,255,0.3);
        border-top: 4px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<!-- Loading Animation -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
</div>

<?php include 'includes/navbar.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
    <!-- Floating Particles -->
    <div class="particles">
        <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="left: 20%; animation-delay: 1s;"></div>
        <div class="particle" style="left: 30%; animation-delay: 2s;"></div>
        <div class="particle" style="left: 40%; animation-delay: 3s;"></div>
        <div class="particle" style="left: 50%; animation-delay: 4s;"></div>
        <div class="particle" style="left: 60%; animation-delay: 5s;"></div>
        <div class="particle" style="left: 70%; animation-delay: 6s;"></div>
        <div class="particle" style="left: 80%; animation-delay: 7s;"></div>
        <div class="particle" style="left: 90%; animation-delay: 8s;"></div>
    </div>
    
    <div class="container hero-content">
        <div class="row align-items-center">
            <div class="col-lg-6 fade-in-left">
                <h1 class="hero-title">Discover Amazing Events</h1>
                <p class="hero-subtitle">Find and book tickets for the best concerts, sports events, conferences, and entertainment in your area. Experience unforgettable moments!</p>
                <div class="hero-buttons">
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
            <div class="col-lg-6 text-center mt-4 mt-lg-0 fade-in-right">
                <div class="hero-image">
                    <img src="assets/images/eventbooking.png" alt="Events" class="img-fluid" style="height: 300px; width: 600px;">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Search Section -->
<section class="search-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="search-form fade-in-up">
                    <h3 class="text-center mb-4" style="color: #333; font-weight: 700;">Find Your Perfect Event</h3>
                    <form action="events/search.php" method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label text-muted fw-semibold">What are you looking for?</label>
                            <input type="text" class="form-control" name="q" placeholder="Search events...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted fw-semibold">Category</label>
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
                            <label class="form-label text-muted fw-semibold">Date</label>
                            <input type="date" class="form-control" name="date" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn search-btn w-100">
                                <i class="fas fa-search me-2"></i>Search Events
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Upcoming Events Section -->
<section class="section-padding">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 fade-in-left">
                <h2 class="display-5 fw-bold mb-3" style="color: #333;">Upcoming Events</h2>
                <p class="lead text-muted">Don't miss out on these amazing upcoming events that will create lasting memories</p>
            </div>
            <div class="col-lg-4 text-lg-end fade-in-right">
                <a href="events/events.php" class="btn btn-outline-primary btn-lg" style="border-radius: 50px;">
                    View All Events <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
        
        <div class="row">
            <?php if (!empty($upcoming_events)): ?>
                <?php foreach ($upcoming_events as $index => $event): ?>
                <div class="col-lg-4 col-md-6 mb-4 fade-in-up" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                    <div class="card event-card h-100">
                        <?php if ($event['image']): ?>
                        <img src="assets/images/events/<?php echo htmlspecialchars($event['image']); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($event['title']); ?>">
                        <?php else: ?>
                        <div class="card-img-top d-flex align-items-center justify-content-center" 
                             style="height: 250px; background: var(--primary-gradient);">
                            <i class="fas fa-calendar-alt fa-4x text-white"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body d-flex flex-column">
                            <div class="mb-3">
                                <span class="event-badge badge-primary"><?php echo htmlspecialchars($event['category_name']); ?></span>
                                <span class="event-badge badge-success"><?php echo formatCurrency($event['price']); ?></span>
                            </div>
                            
                            <h5 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($event['title']); ?></h5>
                            
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-calendar text-primary me-2"></i>
                                    <small class="text-muted fw-semibold">
                                        <?php echo formatDate($event['event_date']); ?>
                                    </small>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-clock text-success me-2"></i>
                                    <small class="text-muted fw-semibold">
                                        <?php echo formatTime($event['start_time']); ?>
                                    </small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                    <small class="text-muted fw-semibold">
                                        <?php echo htmlspecialchars($event['venue_name'] . ', ' . $event['venue_city']); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars(substr($event['description'], 0, 100)); ?>...
                            </p>
                            
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-success fw-semibold">
                                        <i class="fas fa-ticket-alt me-1"></i>
                                        <?php echo $event['available_tickets']; ?> tickets left
                                    </small>
                                    <a href="events/event-details.php?id=<?php echo $event['id']; ?>" 
                                       class="btn btn-primary" style="border-radius: 25px;">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5 fade-in-up">
                    <div style="font-size: 4rem; color: #e9ecef; margin-bottom: 1rem;">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h4 class="text-muted mb-3">No upcoming events found</h4>
                    <p class="text-muted">Check back later for exciting new events!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Popular Events Section -->
<?php if (!empty($popular_events)): ?>
<section class="section-padding" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center fade-in-up">
                <h2 class="display-5 fw-bold mb-3" style="color: #333;">🔥 Popular Events</h2>
                <p class="lead text-muted">Most booked events this month - Join the excitement!</p>
            </div>
        </div>
        
        <div class="row">
            <?php foreach ($popular_events as $index => $event): ?>
            <div class="col-lg-4 col-md-6 mb-4 fade-in-up" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                <div class="card event-card h-100">
                    <?php if ($event['image']): ?>
                    <img src="assets/images/events/<?php echo htmlspecialchars($event['image']); ?>" 
                         class="card-img-top" alt="<?php echo htmlspecialchars($event['title']); ?>">
                    <?php else: ?>
                    <div class="card-img-top d-flex align-items-center justify-content-center" 
                         style="height: 250px; background: var(--secondary-gradient);">
                        <i class="fas fa-fire fa-4x text-white"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-body d-flex flex-column">
                        <div class="mb-3">
                            <span class="event-badge badge-primary"><?php echo htmlspecialchars($event['category_name']); ?></span>
                            <span class="event-badge badge-success"><?php echo formatCurrency($event['price']); ?></span>
                            <span class="event-badge badge-warning">🔥 Popular</span>
                        </div>
                        
                        <h5 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($event['title']); ?></h5>
                        
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-calendar text-primary me-2"></i>
                                <small class="text-muted fw-semibold">
                                    <?php echo formatDate($event['event_date']); ?>
                                </small>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-clock text-success me-2"></i>
                                <small class="text-muted fw-semibold">
                                    <?php echo formatTime($event['start_time']); ?>
                                </small>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                <small class="text-muted fw-semibold">
                                    <?php echo htmlspecialchars($event['venue_name'] . ', ' . $event['venue_city']); ?>
                                </small>
                            </div>
                        </div>
                        
                        <p class="card-text text-muted">
                            <?php echo htmlspecialchars(substr($event['description'], 0, 100)); ?>...
                        </p>
                        
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-danger fw-semibold">
                                    <i class="fas fa-fire me-1"></i><?php echo $event['booking_count']; ?> bookings
                                </small>
                                <a href="events/event-details.php?id=<?php echo $event['id']; ?>" 
                                   class="btn btn-primary" style="border-radius: 25px;">
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
<section class="section-padding">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12 fade-in-up">
                <h2 class="display-5 fw-bold mb-4" style="color: #333;">Why Choose Our Platform?</h2>
                <p class="lead text-muted">Experience seamless event booking with our feature-rich platform designed for your convenience</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-5 text-center fade-in-up" style="animation-delay: 0.1s;">
                <div class="feature-icon feature-primary">
                    <i class="fas fa-search fa-2x text-white"></i>
                </div>
                <h5 class="fw-bold mb-3">Smart Search</h5>
                <p class="text-muted">Find events quickly with our AI-powered search and intelligent filter options</p>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-5 text-center fade-in-up" style="animation-delay: 0.2s;">
                <div class="feature-icon feature-success">
                    <i class="fas fa-shield-alt fa-2x text-white"></i>
                </div>
                <h5 class="fw-bold mb-3">Secure Booking</h5>
                <p class="text-muted">Your payments and personal information are always protected with bank-level security</p>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-5 text-center fade-in-up" style="animation-delay: 0.3s;">
                <div class="feature-icon feature-info">
                    <i class="fas fa-mobile-alt fa-2x text-white"></i>
                </div>
                <h5 class="fw-bold mb-3">Mobile First</h5>
                <p class="text-muted">Book events on the go with our lightning-fast responsive mobile design</p>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-5 text-center fade-in-up" style="animation-delay: 0.4s;">
                <div class="feature-icon feature-warning">
                    <i class="fas fa-headset fa-2x text-white"></i>
                </div>
                <h5 class="fw-bold mb-3">24/7 Support</h5>
                <p class="text-muted">Get instant help whenever you need it with our dedicated support team</p>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="newsletter-section section-padding">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 fade-in-left">
                <h3 class="display-6 fw-bold mb-3 text-white">Stay in the Loop! 📧</h3>
                <p class="lead text-white opacity-75 mb-4">Subscribe to get notified about new events, exclusive offers, and early bird discounts</p>
                <div class="d-flex align-items-center text-white opacity-75">
                    <i class="fas fa-check-circle me-2"></i>
                    <span class="me-4">Weekly Updates</span>
                    <i class="fas fa-check-circle me-2"></i>
                    <span class="me-4">Exclusive Deals</span>
                    <i class="fas fa-check-circle me-2"></i>
                    <span>Early Access</span>
                </div>
            </div>
            <div class="col-lg-6 fade-in-right">
                <div class="newsletter-form">
                    <form class="row g-3" action="newsletter/subscribe.php" method="POST">
                        <div class="col-md-8">
                            <input type="email" class="form-control newsletter-input" name="email" 
                                   placeholder="Enter your email address" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn newsletter-btn w-100">
                                <i class="fas fa-paper-plane me-2"></i>Subscribe
                            </button>
                        </div>
                    </form>
                    <small class="text-white opacity-50 mt-2 d-block">
                        <i class="fas fa-lock me-1"></i>We respect your privacy. Unsubscribe anytime.
                    </small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom JavaScript for Animations -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Remove loading overlay
    setTimeout(() => {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.opacity = '0';
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
            }, 500);
        }
    }, 1000);

    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, observerOptions);

    // Observe all animation elements
    document.querySelectorAll('.fade-in-up, .fade-in-left, .fade-in-right, .scale-in').forEach(el => {
        observer.observe(el);
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Dynamic particles creation
    function createParticle() {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 8 + 's';
        particle.style.animationDuration = (Math.random() * 3 + 5) + 's';
        
        const particlesContainer = document.querySelector('.particles');
        if (particlesContainer) {
            particlesContainer.appendChild(particle);
            
            // Remove particle after animation
            setTimeout(() => {
                particle.remove();
            }, 8000);
        }
    }

    // Create particles periodically
    setInterval(createParticle, 300);

    // Enhanced form interactions
    const inputs = document.querySelectorAll('.form-control, .form-select');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });

    // Card hover effects
    const eventCards = document.querySelectorAll('.event-card');
    eventCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-15px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Button ripple effect
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

    // Parallax effect for hero section
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const parallaxElements = document.querySelectorAll('.hero-image');
        
        parallaxElements.forEach(element => {
            const speed = 0.3;
            element.style.transform = `translateY(${scrolled * speed}px)`;
        });
    });

    // Newsletter form enhancement
    const newsletterForm = document.querySelector('.newsletter-form form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = this.querySelector('input[name="email"]').value;
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            
            // Show loading state
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Subscribing...';
            button.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-check me-2"></i>Subscribed!';
                button.classList.add('btn-success');
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                    button.classList.remove('btn-success');
                    this.reset();
                }, 2000);
            }, 1500);
        });
    }

    // Search form enhancements
    const searchForm = document.querySelector('.search-form form');
    if (searchForm) {
        const searchInputs = searchForm.querySelectorAll('input, select');
        
        searchInputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.value) {
                    this.style.borderColor = '#28a745';
                    this.style.boxShadow = '0 0 0 0.2rem rgba(40, 167, 69, 0.25)';
                } else {
                    this.style.borderColor = '#e9ecef';
                    this.style.boxShadow = 'none';
                }
            });
        });
    }

    // Counter animation for statistics
    function animateCounter(element, target) {
        let count = 0;
        const increment = target / 100;
        
        const timer = setInterval(() => {
            count += increment;
            element.textContent = Math.floor(count);
            
            if (count >= target) {
                element.textContent = target;
                clearInterval(timer);
            }
        }, 20);
    }

    // Feature icons animation enhancement
    const featureIcons = document.querySelectorAll('.feature-icon');
    featureIcons.forEach((icon, index) => {
        setTimeout(() => {
            icon.style.animation = `bounce 0.6s ease ${index * 0.1}s`;
        }, 500);
    });

    // Add custom CSS for ripple effect
    const style = document.createElement('style');
    style.textContent = `
        .btn {
            position: relative;
            overflow: hidden;
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                transform: translate3d(0,0,0);
            }
            40%, 43% {
                transform: translate3d(0, -20px, 0);
            }
            70% {
                transform: translate3d(0, -10px, 0);
            }
            90% {
                transform: translate3d(0, -4px, 0);
            }
        }
    `;
    document.head.appendChild(style);
});
</script>

<?php include 'includes/footer.php'; ?>