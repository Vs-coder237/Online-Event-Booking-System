<?php
/**
 * Event Details Page
 * Online Event Booking System
 */

session_start();
require_once '../includes/functions.php';

// Get event ID from URL
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$event_id) {
    header("Location: events.php");
    exit();
}

// Get event details
$event = getEventById($event_id);

if (!$event) {
    $_SESSION['error'] = "Event not found.";
    header("Location: events.php");
    exit();
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "Please login to book tickets.";
        header("Location: ../auth/login.php");
        exit();
    }
    
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if ($quantity < 1) {
        $_SESSION['error'] = "Invalid quantity selected.";
    } elseif ($quantity > $event['available_tickets']) {
        $_SESSION['error'] = "Not enough tickets available.";
    } else {
        if (addToCart($_SESSION['user_id'], $event_id, $quantity)) {
            $_SESSION['success'] = "Tickets added to cart successfully!";
            header("Location: ../booking/cart.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to add tickets to cart. Please try again.";
        }
    }
}

$page_title = htmlspecialchars($event['title']);
include '../includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="events.php">Events</a></li>
            <li class="breadcrumb-item active" aria-current="page">
                <?php echo htmlspecialchars(substr($event['title'], 0, 30)) . '...'; ?>
            </li>
        </ol>
    </nav>

    <div class="row">
        <!-- Event Image and Gallery -->
        <div class="col-lg-8">
            <div class="event-image-section mb-4">
                <?php if ($event['image']): ?>
                    <img src="../assets/images/events/<?php echo htmlspecialchars($event['image']); ?>" 
                         class="img-fluid rounded shadow" 
                         alt="<?php echo htmlspecialchars($event['title']); ?>"
                         style="width: 100%; height: 400px; object-fit: cover;">
                <?php else: ?>
                    <div class="bg-light rounded d-flex align-items-center justify-content-center shadow" 
                         style="height: 400px;">
                        <div class="text-center">
                            <i class="fas fa-calendar-alt fa-5x text-muted mb-3"></i>
                            <h4 class="text-muted">Event Image</h4>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Event Details -->
            <div class="event-content">
                <!-- Category Badge -->
                <?php if ($event['category_name']): ?>
                    <span class="badge bg-primary fs-6 mb-3">
                        <?php echo htmlspecialchars($event['category_name']); ?>
                    </span>
                <?php endif; ?>

                <!-- Event Title -->
                <h1 class="display-5 mb-3"><?php echo htmlspecialchars($event['title']); ?></h1>

                <!-- Event Meta Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-calendar-alt text-primary me-3 fa-lg"></i>
                            <div>
                                <strong>Date</strong><br>
                                <span class="text-muted">
                                    <?php echo date('l, F j, Y', strtotime($event['event_date'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-clock text-primary me-3 fa-lg"></i>
                            <div>
                                <strong>Time</strong><br>
                                <span class="text-muted">
                                    <?php echo date('g:i A', strtotime($event['start_time'])); ?> - 
                                    <?php echo date('g:i A', strtotime($event['end_time'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-map-marker-alt text-primary me-3 fa-lg"></i>
                            <div>
                                <strong>Venue</strong><br>
                                <span class="text-muted">
                                    <?php echo htmlspecialchars($event['venue_name']); ?><br>
                                    <small><?php echo htmlspecialchars($event['venue_address'] . ', ' . $event['venue_city']); ?></small>
                                </span>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-user text-primary me-3 fa-lg"></i>
                            <div>
                                <strong>Organizer</strong><br>
                                <span class="text-muted">
                                    <?php echo htmlspecialchars($event['organizer_name']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Event Description -->
                <div class="event-description mb-4">
                    <h3 class="mb-3">About This Event</h3>
                    <div class="description-content">
                        <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                    </div>
                </div>

                <!-- Event Organizer Contact -->
                <div class="organizer-info bg-light p-4 rounded mb-4">
                    <h4 class="mb-3">Organizer Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Name:</strong> <?php echo htmlspecialchars($event['organizer_name']); ?>
                            </p>
                            <p class="mb-2">
                                <strong>Email:</strong> 
                                <a href="mailto:<?php echo htmlspecialchars($event['organizer_email']); ?>">
                                    <?php echo htmlspecialchars($event['organizer_email']); ?>
                                </a>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <?php if ($event['organizer_phone']): ?>
                                <p class="mb-2">
                                    <strong>Phone:</strong> 
                                    <a href="tel:<?php echo htmlspecialchars($event['organizer_phone']); ?>">
                                        <?php echo htmlspecialchars($event['organizer_phone']); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Sidebar -->
        <div class="col-lg-4">
            <div class="booking-card sticky-top" style="top: 2rem;">
                <div class="card shadow">
                    <div class="card-body">
                        <!-- Price -->
                        <div class="text-center mb-4">
                            <?php if ($event['price'] > 0): ?>
                                <h2 class="text-primary mb-0">$<?php echo number_format($event['price'], 2); ?></h2>
                                <small class="text-muted">per ticket</small>
                            <?php else: ?>
                                <h2 class="text-success mb-0">FREE</h2>
                                <small class="text-muted">no charge</small>
                            <?php endif; ?>
                        </div>

                        <!-- Availability Status -->
                        <div class="availability-status mb-4">
                            <?php if ($event['available_tickets'] > 0): ?>
                                <div class="alert alert-success" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong><?php echo $event['available_tickets']; ?> tickets available</strong>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-times-circle me-2"></i>
                                    <strong>Sold Out</strong>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Booking Form -->
                        <?php if ($event['available_tickets'] > 0): ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Number of Tickets</label>
                                    <select class="form-select" id="quantity" name="quantity" required>
                                        <?php 
                                        $max_quantity = min(10, $event['available_tickets']);
                                        for ($i = 1; $i <= $max_quantity; $i++): 
                                        ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?> Ticket<?php echo $i > 1 ? 's' : ''; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <!-- Total Price Display -->
                                <?php if ($event['price'] > 0): ?>
                                    <div class="total-price mb-3 p-3 bg-light rounded">
                                        <div class="d-flex justify-content-between">
                                            <span>Subtotal:</span>
                                            <span id="subtotal">$<?php echo number_format($event['price'], 2); ?></span>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between">
                                            <strong>Total:</strong>
                                            <strong id="total">$<?php echo number_format($event['price'], 2); ?></strong>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg w-100 mb-3">
                                    <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                                </button>
                            </form>
                            
                            <div class="text-center">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Secure booking process
                                </small>
                            </div>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-lg w-100" disabled>
                                <i class="fas fa-times me-2"></i>Sold Out
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Share Event -->
                <div class="card mt-4 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Share This Event</h5>
                        <div class="d-flex gap-2">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(getCurrentUrl()); ?>" 
                               target="_blank" class="btn btn-outline-primary btn-sm flex-fill">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(getCurrentUrl()); ?>&text=<?php echo urlencode($event['title']); ?>" 
                               target="_blank" class="btn btn-outline-info btn-sm flex-fill">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(getCurrentUrl()); ?>" 
                               target="_blank" class="btn btn-outline-primary btn-sm flex-fill">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <button class="btn btn-outline-secondary btn-sm flex-fill" onclick="copyToClipboard()">
                                <i class="fas fa-link"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Events -->
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="mb-4">Related Events</h3>
            <?php
            // Get related events from same category
            $related_events = [];
            if ($event['category_id']) {
                $related_events = getEvents(1, '', $event['category_id'], '', 3);
                // Remove current event from related events
                $related_events = array_filter($related_events, function($e) use ($event_id) {
                    return $e['id'] != $event_id;
                });
                $related_events = array_slice($related_events, 0, 3);
            }
            ?>
            
            <?php if (!empty($related_events)): ?>
                <div class="row">
                    <?php foreach ($related_events as $related): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div style="height: 200px; overflow: hidden;">
                                    <?php if ($related['image']): ?>
                                        <img src="../assets/images/events/<?php echo htmlspecialchars($related['image']); ?>" 
                                             class="card-img-top" alt="<?php echo htmlspecialchars($related['title']); ?>"
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center h-100">
                                            <i class="fas fa-calendar-alt fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="event-details.php?id=<?php echo $related['id']; ?>" 
                                           class="text-decoration-none">
                                            <?php echo htmlspecialchars($related['title']); ?>
                                        </a>
                                    </h6>
                                    <p class="card-text text-muted small">
                                        <?php echo date('M j, Y', strtotime($related['event_date'])); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-primary">
                                            <?php echo $related['price'] > 0 ? '$' . number_format($related['price'], 2) : 'FREE'; ?>
                                        </small>
                                        <a href="event-details.php?id=<?php echo $related['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">View</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">No related events found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Calculate total price based on quantity
document.getElementById('quantity')?.addEventListener('change', function() {
    const quantity = parseInt(this.value);
    const unitPrice = <?php echo $event['price']; ?>;
    const subtotal = (quantity * unitPrice).toFixed(2);
    const total = subtotal; // Add taxes/fees here if needed
    
    document.getElementById('subtotal').textContent = '$' + subtotal;
    document.getElementById('total').textContent = '$' + total;
});

// Copy URL to clipboard
function copyToClipboard() {
    navigator.clipboard.writeText(window.location.href).then(function() {
        alert('Link copied to clipboard!');
    });
}

// Get current URL for sharing
<?php
function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}
?>
</script>

<style>
.sticky-top {
    position: sticky;
    top: 2rem;
    z-index: 1020;
}

.event-image-section img {
    transition: transform 0.3s ease;
}

.event-image-section img:hover {
    transform: scale(1.02);
}

.description-content {
    line-height: 1.6;
    font-size: 1.1rem;
}

.booking-card .card {
    border: none;
    border-radius: 15px;
}

.total-price {
    border: 2px dashed #dee2e6;
}

@media (max-width: 991px) {
    .sticky-top {
        position: relative;
        top: auto;
    }
}
</style>

<?php include '../includes/footer.php'; ?>