<?php
/**
 * Add to Cart Handler
 * Online Event Booking System
 */

session_start();
require_once '../includes/functions.php';

// Set JSON response header
header('Content-Type: application/json');

// Initialize response
$response = ['success' => false, 'message' => ''];

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Please log in to add items to cart';
        $response['redirect'] = '../auth/login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? '../events/events.php');
        echo json_encode($response);
        exit();
    }

    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Invalid request method';
        echo json_encode($response);
        exit();
    }

    // Get and validate input
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Validate event ID
    if ($event_id <= 0) {
        $response['message'] = 'Invalid event ID';
        echo json_encode($response);
        exit();
    }

    // Validate quantity
    if ($quantity <= 0 || $quantity > 10) {
        $response['message'] = 'Invalid quantity. Please select between 1 and 10 tickets';
        echo json_encode($response);
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Get event details to verify it exists and is available
    $event = getEventById($event_id);
    if (!$event) {
        $response['message'] = 'Event not found or not available';
        echo json_encode($response);
        exit();
    }

    // Check if event is in the future
    $event_datetime = $event['event_date'] . ' ' . $event['start_time'];
    if (strtotime($event_datetime) <= time()) {
        $response['message'] = 'This event has already started or ended';
        echo json_encode($response);
        exit();
    }

    // Check if enough tickets are available
    if ($event['available_tickets'] < $quantity) {
        $response['message'] = 'Not enough tickets available. Only ' . $event['available_tickets'] . ' tickets remaining';
        echo json_encode($response);
        exit();
    }

    // Check if user already has this event in cart
    $existing_cart_items = getCartItems($user_id);
    $existing_quantity = 0;
    
    foreach ($existing_cart_items as $item) {
        if ($item['event_id'] == $event_id) {
            $existing_quantity = $item['quantity'];
            break;
        }
    }

    // Check if adding this quantity would exceed available tickets
    $total_quantity = $existing_quantity + $quantity;
    if ($total_quantity > $event['available_tickets']) {
        $response['message'] = 'Cannot add ' . $quantity . ' tickets. You already have ' . $existing_quantity . ' in cart. Only ' . ($event['available_tickets'] - $existing_quantity) . ' more available';
        echo json_encode($response);
        exit();
    }

    // Check if total quantity exceeds per-user limit (if any)
    $max_per_user = 10; // You can make this configurable per event
    if ($total_quantity > $max_per_user) {
        $response['message'] = 'Maximum ' . $max_per_user . ' tickets allowed per user';
        echo json_encode($response);
        exit();
    }

    // Add to cart
    if (addToCart($user_id, $event_id, $quantity)) {
        $response['success'] = true;
        $response['message'] = $quantity . ' ticket(s) added to cart successfully';
        
        // Get updated cart count for UI
        $cart_items = getCartItems($user_id);
        $total_items = 0;
        foreach ($cart_items as $item) {
            $total_items += $item['quantity'];
        }
        $response['cart_count'] = $total_items;
        $response['cart_total'] = getCartTotal($user_id);
        
        // Log activity
        logActivity('add_to_cart', "Added {$quantity} tickets for event: {$event['title']}", 'user', $user_id);
    } else {
        $response['message'] = 'Failed to add item to cart. Please try again';
    }

} catch (Exception $e) {
    $response['message'] = 'An error occurred. Please try again';
    error_log("Add to cart error: " . $e->getMessage());
}

echo json_encode($response);
?>