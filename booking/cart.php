<?php
/**
 * Shopping Cart Page
 * Online Event Booking System
 */

session_start();
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle cart updates via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_quantity':
                $event_id = (int)$_POST['event_id'];
                $quantity = (int)$_POST['quantity'];
                
                if ($quantity > 0) {
                    if (updateCartItem($user_id, $event_id, $quantity)) {
                        $response['success'] = true;
                        $response['message'] = 'Cart updated successfully';
                    } else {
                        $response['message'] = 'Failed to update cart. Please check ticket availability.';
                    }
                } else {
                    if (removeFromCart($user_id, $event_id)) {
                        $response['success'] = true;
                        $response['message'] = 'Item removed from cart';
                    } else {
                        $response['message'] = 'Failed to remove item from cart';
                    }
                }
                break;
                
            case 'remove_item':
                $event_id = (int)$_POST['event_id'];
                if (removeFromCart($user_id, $event_id)) {
                    $response['success'] = true;
                    $response['message'] = 'Item removed from cart';
                } else {
                    $response['message'] = 'Failed to remove item from cart';
                }
                break;
                
            case 'clear_cart':
                if (clearCart($user_id)) {
                    $response['success'] = true;
                    $response['message'] = 'Cart cleared successfully';
                } else {
                    $response['message'] = 'Failed to clear cart';
                }
                break;
        }
    }
    
    // If AJAX request, return JSON response
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    // For regular form submission, redirect with message
    if ($response['success']) {
        $_SESSION['success_message'] = $response['message'];
    } else {
        $_SESSION['error_message'] = $response['message'];
    }
    header('Location: cart.php');
    exit();
}

// Get cart items
$cart_items = getCartItems($user_id);
$cart_total = getCartTotal($user_id);

$page_title = "Shopping Cart";
include '../includes/header.php';
?>

<div class="container mt-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="display-4 mb-2">Shopping Cart</h1>
            <p class="lead text-muted">Review your selected events before checkout</p>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if (!empty($cart_items)): ?>
        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Cart Items (<?php echo count($cart_items); ?>)</h5>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($cart_items as $index => $item): ?>
                            <div class="cart-item border-bottom p-3" data-event-id="<?php echo $item['event_id']; ?>">
                                <div class="row align-items-center">
                                    <!-- Event Image -->
                                    <div class="col-md-2">
                                        <div class="event-image-wrapper" style="height: 80px; overflow: hidden; border-radius: 8px;">
                                            <?php if ($item['image']): ?>
                                                <img src="../assets/images/events/<?php echo htmlspecialchars($item['image']); ?>" 
                                                     class="img-fluid" alt="<?php echo htmlspecialchars($item['title']); ?>"
                                                     style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center h-100">
                                                    <i class="fas fa-calendar-alt text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Event Details -->
                                    <div class="col-md-4">
                                        <h6 class="mb-1">
                                            <a href="../events/event-details.php?id=<?php echo $item['event_id']; ?>" 
                                               class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('M j, Y', strtotime($item['event_date'])); ?>
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('g:i A', strtotime($item['start_time'])); ?>
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($item['venue_name'] . ', ' . $item['venue_city']); ?>
                                        </small>
                                    </div>
                                    
                                    <!-- Price -->
                                    <div class="col-md-2 text-center">
                                        <div class="price">
                                            <?php if ($item['price'] > 0): ?>
                                                <span class="fw-bold">$<?php echo number_format($item['price'], 2); ?></span>
                                                <small class="text-muted d-block">per ticket</small>
                                            <?php else: ?>
                                                <span class="fw-bold text-success">FREE</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Quantity Controls -->
                                    <div class="col-md-2">
                                        <div class="quantity-controls d-flex align-items-center justify-content-center">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                    onclick="updateQuantity(<?php echo $item['event_id']; ?>, <?php echo $item['quantity'] - 1; ?>)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control form-control-sm mx-2 text-center" 
                                                   style="width: 60px;" value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="10" 
                                                   onchange="updateQuantity(<?php echo $item['event_id']; ?>, this.value)">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                    onclick="updateQuantity(<?php echo $item['event_id']; ?>, <?php echo $item['quantity'] + 1; ?>)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Subtotal and Remove -->
                                    <div class="col-md-2 text-end">
                                        <div class="subtotal mb-2">
                                            <span class="fw-bold">$<?php echo number_format($item['quantity'] * $item['price'], 2); ?></span>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                onclick="removeItem(<?php echo $item['event_id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Cart Summary -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Processing Fee:</span>
                            <span>$0.00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="text-primary">$<?php echo number_format($cart_total, 2); ?></strong>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="checkout.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-credit-card me-2"></i>
                                Proceed to Checkout
                            </a>
                            <a href="../events/events.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Cart Tips -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            Tips
                        </h6>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-1">• Tickets are reserved for 15 minutes during checkout</li>
                            <li class="mb-1">• You'll receive confirmation via email</li>
                            <li class="mb-1">• Check our refund policy before purchase</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Empty Cart -->
        <div class="row">
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">Your cart is empty</h4>
                    <p class="text-muted mb-4">Looks like you haven't added any events to your cart yet.</p>
                    <a href="../events/events.php" class="btn btn-primary">
                        <i class="fas fa-calendar me-2"></i>
                        Browse Events
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0">Updating cart...</p>
            </div>
        </div>
    </div>
</div>

<script>
// Update item quantity
function updateQuantity(eventId, quantity) {
    if (quantity < 1) {
        if (confirm('Remove this item from cart?')) {
            removeItem(eventId);
        }
        return;
    }
    
    showLoading();
    
    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=update_quantity&event_id=${eventId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update cart');
        }
    })
    .catch(error => {
        hideLoading();
        alert('An error occurred. Please try again.');
        console.error('Error:', error);
    });
}

// Remove item from cart
function removeItem(eventId) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }
    
    showLoading();
    
    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=remove_item&event_id=${eventId}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to remove item');
        }
    })
    .catch(error => {
        hideLoading();
        alert('An error occurred. Please try again.');
        console.error('Error:', error);
    });
}

// Clear entire cart
function clearCart() {
    if (!confirm('Are you sure you want to clear your entire cart?')) {
        return;
    }
    
    showLoading();
    
    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=clear_cart'
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to clear cart');
        }
    })
    .catch(error => {
        hideLoading();
        alert('An error occurred. Please try again.');
        console.error('Error:', error);
    });
}

// Show loading modal
function showLoading() {
    const modal = new bootstrap.Modal(document.getElementById('loadingModal'));
    modal.show();
}

// Hide loading modal
function hideLoading() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
    if (modal) {
        modal.hide();
    }
}
</script>

<style>
.cart-item {
    transition: background-color 0.3s ease;
}

.cart-item:hover {
    background-color: #f8f9fa;
}

.quantity-controls .btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.event-image-wrapper {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

@media (max-width: 768px) {
    .cart-item .row > div {
        margin-bottom: 1rem;
    }
    
    .cart-item .col-md-2:last-child {
        text-align: center !important;
    }
    
    .quantity-controls {
        justify-content: center !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>