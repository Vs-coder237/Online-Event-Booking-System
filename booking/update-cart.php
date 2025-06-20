<?php
/**
 * Update Cart Handler
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
        $response['message'] = 'Please log in to update cart';
        echo json_encode($response);
        exit();
    }

    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Invalid request method';
        echo json_encode($response);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_quantity':
            $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

            if ($event_id <= 0) {
                $response['message'] = 'Invalid event ID';
                break;
            }

            if ($quantity <= 0) {
                if (removeFromCart($user_id, $event_id)) {
                    $response['success'] = true;
                    $response['message'] = 'Item removed from cart';
                    logActivity('remove_from_cart', "Removed event ID: {$event_id} from cart", 'user', $user_id);
                } else {
                    $response['message'] = 'Failed to remove item from cart';
                }
                break;
            }

            if ($quantity > 10) {
                $response['message'] = 'Maximum 10 tickets allowed per event';
                break;
            }

            $event = getEventById($event_id);
            if (!$event) {
                $response['message'] = 'Event not found';
                break;
            }

            if ($event['available_tickets'] < $quantity) {
                $response['message'] = 'Not enough tickets available. Only ' . $event['available_tickets'] . ' tickets remaining';
                break;
            }

            if (updateCartItem($user_id, $event_id, $quantity)) {
                $response['success'] = true;
                $response['message'] = 'Cart updated successfully';
                logActivity('update_cart', "Updated quantity to {$quantity} for event: {$event['title']}", 'user', $user_id);
            } else {
                $response['message'] = 'Failed to update cart';
            }
            break;

        case 'remove_item':
            $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

            if ($event_id <= 0) {
                $response['message'] = 'Invalid event ID';
                break;
            }

            $event = getEventById($event_id);

            if (removeFromCart($user_id, $event_id)) {
                $response['success'] = true;
                $response['message'] = 'Item removed from cart';
                if ($event) {
                    logActivity('remove_from_cart', "Removed event: {$event['title']} from cart", 'user', $user_id);
                }
            } else {
                $response['message'] = 'Failed to remove item from cart';
            }
            break;

        case 'clear_cart':
            if (clearCart($user_id)) {
                $response['success'] = true;
                $response['message'] = 'Cart cleared successfully';
                logActivity('clear_cart', "Cleared entire cart", 'user', $user_id);
            } else {
                $response['message'] = 'Failed to clear cart';
            }
            break;

        case 'get_cart_summary':
            $cart_items = getCartItems($user_id);
            $cart_total = getCartTotal($user_id);

            $total_items = 0;
            foreach ($cart_items as $item) {
                $total_items += $item['quantity'];
            }

            $response['success'] = true;
            $response['data'] = [
                'cart_count' => $total_items,
                'cart_total' => $cart_total,
                'formatted_total' => '$' . number_format($cart_total, 2)
            ];
            break;

        case 'validate_cart':
            $cart_items = getCartItems($user_id);
            $invalid_items = [];

            foreach ($cart_items as $item) {
                $current_event = getEventById($item['event_id']);

                if (!$current_event) {
                    $invalid_items[] = [
                        'event_id' => $item['event_id'],
                        'title' => $item['title'],
                        'reason' => 'Event no longer available'
                    ];
                    continue;
                }

                $event_datetime = $current_event['event_date'] . ' ' . $current_event['start_time'];
                if (strtotime($event_datetime) <= time()) {
                    $invalid_items[] = [
                        'event_id' => $item['event_id'],
                        'title' => $item['title'],
                        'reason' => 'Event has already started'
                    ];
                    continue;
                }

                if ($current_event['available_tickets'] < $item['quantity']) {
                    $invalid_items[] = [
                        'event_id' => $item['event_id'],
                        'title' => $item['title'],
                        'reason' => 'Not enough tickets available',
                        'available' => $current_event['available_tickets'],
                        'requested' => $item['quantity']
                    ];
                }
            }

            if (empty($invalid_items)) {
                $response['success'] = true;
                $response['message'] = 'Cart is valid';
            } else {
                $response['success'] = false;
                $response['message'] = 'Some items in your cart are no longer available';
                $response['invalid_items'] = $invalid_items;
            }
            break;

        default:
            $response['message'] = 'Invalid action';
            break;
    }

    // Include updated cart info for certain actions
    if ($response['success'] && in_array($action, ['update_quantity', 'remove_item', 'clear_cart'])) {
        $cart_items = getCartItems($user_id);
        $total_items = 0;
        foreach ($cart_items as $item) {
            $total_items += $item['quantity'];
        }
        $response['cart_count'] = $total_items;
        $response['cart_total'] = getCartTotal($user_id);
    }

} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
}

// Always return JSON response
echo json_encode($response);
exit();
