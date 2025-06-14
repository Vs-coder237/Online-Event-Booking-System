<?php
/**
 * Common Functions
 * Online Event Booking System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Get all events with pagination and filters
 */
function getEvents($page = 1, $search = '', $category = '', $date = '', $limit = EVENTS_PER_PAGE) {
    global $pdo;
    
    $offset = ($page - 1) * $limit;
    $where_conditions = ["e.status = 'published'"];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(e.title LIKE ? OR e.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($category)) {
        $where_conditions[] = "e.category_id = ?";
        $params[] = $category;
    }
    
    if (!empty($date)) {
        $where_conditions[] = "e.event_date = ?";
        $params[] = $date;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $sql = "SELECT e.*, c.name as category_name, v.name as venue_name, v.city as venue_city
            FROM events e
            LEFT JOIN categories c ON e.category_id = c.id
            LEFT JOIN venues v ON e.venue_id = v.id
            WHERE $where_clause
            ORDER BY e.event_date ASC, e.start_time ASC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get total events count for pagination
 */
function getTotalEventsCount($search = '', $category = '', $date = '') {
    global $pdo;
    
    $where_conditions = ["status = 'published'"];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($category)) {
        $where_conditions[] = "category_id = ?";
        $params[] = $category;
    }
    
    if (!empty($date)) {
        $where_conditions[] = "event_date = ?";
        $params[] = $date;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $sql = "SELECT COUNT(*) FROM events WHERE $where_clause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

/**
 * Get event by ID
 */
function getEventById($id) {
    global $pdo;
    
    $sql = "SELECT e.*, c.name as category_name, v.name as venue_name, 
                   v.address as venue_address, v.city as venue_city,
                   v.latitude, v.longitude
            FROM events e
            LEFT JOIN categories c ON e.category_id = c.id
            LEFT JOIN venues v ON e.venue_id = v.id
            WHERE e.id = ? AND e.status = 'published'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get all categories
 */
function getCategories() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Get user's cart items
 */
function getCartItems($user_id) {
    global $pdo;
    
    $sql = "SELECT c.*, e.title, e.price, e.image, e.event_date, e.start_time,
                   v.name as venue_name, v.city as venue_city
            FROM cart c
            JOIN events e ON c.event_id = e.id
            LEFT JOIN venues v ON e.venue_id = v.id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Add item to cart
 */
function addToCart($user_id, $event_id, $quantity = 1) {
    global $pdo;
    
    // Check if event exists and has available tickets
    $event = getEventById($event_id);
    if (!$event || $event['available_tickets'] < $quantity) {
        return false;
    }
    
    // Check if item already in cart
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND event_id = ?");
    $stmt->execute([$user_id, $event_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update quantity
        $new_quantity = $existing['quantity'] + $quantity;
        if ($new_quantity > $event['available_tickets']) {
            return false;
        }
        
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND event_id = ?");
        return $stmt->execute([$new_quantity, $user_id, $event_id]);
    } else {
        // Add new item
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, event_id, quantity) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $event_id, $quantity]);
    }
}

/**
 * Update cart item quantity
 */
function updateCartItem($user_id, $event_id, $quantity) {
    global $pdo;
    
    if ($quantity <= 0) {
        return removeFromCart($user_id, $event_id);
    }
    
    // Check if event has enough tickets
    $event = getEventById($event_id);
    if (!$event || $event['available_tickets'] < $quantity) {
        return false;
    }
    
    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND event_id = ?");
    return $stmt->execute([$quantity, $user_id, $event_id]);
}

/**
 * Remove item from cart
 */
function removeFromCart($user_id, $event_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND event_id = ?");
    return $stmt->execute([$user_id, $event_id]);
}

/**
 * Clear user's cart
 */
function clearCart($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    return $stmt->execute([$user_id]);
}

/**
 * Get cart total
 */
function getCartTotal($user_id) {
    global $pdo;
    
    $sql = "SELECT SUM(c.quantity * e.price) as total
            FROM cart c
            JOIN events e ON c.event_id = e.id
            WHERE c.user_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

/**
 * Get user's bookings
 */
function getUserBookings($user_id) {
    global $pdo;
    
    $sql = "SELECT b.*, COUNT(bi.id) as total_items
            FROM bookings b
            LEFT JOIN booking_items bi ON b.id = bi.booking_id
            WHERE b.user_id = ?
            GROUP BY b.id
            ORDER BY b.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Get booking details
 */
function getBookingDetails($booking_id, $user_id = null) {
    global $pdo;
    
    $sql = "SELECT b.*, u.first_name, u.last_name, u.email
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.id = ?";
    
    $params = [$booking_id];
    
    if ($user_id) {
        $sql .= " AND b.user_id = ?";
        $params[] = $user_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Get booking items
 */
function getBookingItems($booking_id) {
    global $pdo;
    
    $sql = "SELECT bi.*, e.title, e.event_date, e.start_time, e.end_time,
                   v.name as venue_name, v.address as venue_address
            FROM booking_items bi
            JOIN events e ON bi.event_id = e.id
            LEFT JOIN venues v ON e.venue_id = v.id
            WHERE bi.booking_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$booking_id]);
    return $stmt->fetchAll();
}

/**
 * Create booking from cart
 */
function createBookingFromCart($user_id, $attendee_info = []) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get cart items
        $cart_items = getCartItems($user_id);
        if (empty($cart_items)) {
            throw new Exception("Cart is empty");
        }
        
        // Calculate total
        $total = 0;
        foreach ($cart_items as $item) {
            $total += $item['quantity'] * $item['price'];
        }
        
        // Create booking
        $booking_reference = generateBookingReference();
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, booking_reference, total_amount, attendee_info) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $booking_reference, $total, json_encode($attendee_info)]);
        $booking_id = $pdo->lastInsertId();
        
        // Create booking items and update ticket availability
        foreach ($cart_items as $item) {
            // Add booking item
            $stmt = $pdo->prepare("INSERT INTO booking_items (booking_id, event_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $booking_id,
                $item['event_id'],
                $item['quantity'],
                $item['price'],
                $item['quantity'] * $item['price']
            ]);
            
            // Update available tickets
            $stmt = $pdo->prepare("UPDATE events SET available_tickets = available_tickets - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['event_id']]);
        }
        
        // Clear cart
        clearCart($user_id);
        
        $pdo->commit();
        return $booking_reference;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Generate pagination links
 */
function generatePagination($current_page, $total_pages, $base_url, $params = []) {
    $pagination = '';
    
    if ($total_pages <= 1) {
        return $pagination;
    }
    
    // Build query string
    $query_params = array_merge($params, ['page' => '']);
    $query_string = http_build_query(array_filter($query_params));
    $query_string = $query_string ? '&' . $query_string : '';
    
    $pagination .= '<nav aria-label="Page navigation">';
    $pagination .= '<ul class="pagination justify-content-center">';
    
    // Previous button
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . $prev_page . $query_string . '">Previous</a></li>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $pagination .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $base_url . '?page=' . $i . $query_string . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . $next_page . $query_string . '">Next</a></li>';
    }
    
    $pagination .= '</ul>';
    $pagination .= '</nav>';
    
    return $pagination;
}

/**
 * Log activity (for admin tracking)
 */
function logActivity($action, $details = '', $user_type = 'user', $user_id = null) {
    global $pdo;
    
    if (!$user_id) {
        $user_id = $user_type === 'admin' ? ($_SESSION['admin_id'] ?? null) : ($_SESSION['user_id'] ?? null);
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_type, user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_type,
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        // Log errors silently
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

/**
 * Send email notification (placeholder for future implementation)
 */
function sendEmailNotification($to, $subject, $body, $is_html = true) {
    // Placeholder for email functionality
    // You can implement this using PHPMailer or similar library
    return true;
}

/**
 * Generate QR code for booking (placeholder)
 */
function generateBookingQR($booking_reference) {
    // Placeholder for QR code generation
    // You can implement this using libraries like phpqrcode
    return "qr_code_" . $booking_reference . ".png";
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number format
 */
function isValidPhone($phone) {
    return preg_match('/^[\+]?[1-9][\d]{0,15}$/', $phone);
}

/**
 * Generate secure password hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Get upcoming events (for homepage)
 */
function getUpcomingEvents($limit = 6) {
    global $pdo;
    
    $sql = "SELECT e.*, c.name as category_name, v.name as venue_name, v.city as venue_city
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

/**
 * Get popular events (based on bookings)
 */
function getPopularEvents($limit = 6) {
    global $pdo;
    
    $sql = "SELECT e.*, c.name as category_name, v.name as venue_name, v.city as venue_city,
                   COUNT(bi.id) as booking_count
            FROM events e
            LEFT JOIN categories c ON e.category_id = c.id
            LEFT JOIN venues v ON e.venue_id = v.id
            LEFT JOIN booking_items bi ON e.id = bi.event_id
            WHERE e.status = 'published' AND e.event_date >= CURDATE()
            GROUP BY e.id
            ORDER BY booking_count DESC, e.event_date ASC
            LIMIT ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function emailExists($email) {
    global $pdo;
    $sql = "SELECT id FROM users WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    return ($stmt->rowCount() > 0);
}



function registerUser($data) {
    global $pdo;

    $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO users (first_name, last_name, email, phone, password, created_at)
                VALUES (:first_name, :last_name, :email, :phone, :password, NOW())";
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':password', $passwordHash);

        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Failed to register user. Please try again.'];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function updateUserProfile($user_id, $data) {
    global $pdo;

    $query = "UPDATE users SET 
                first_name = :first_name,
                last_name = :last_name,
                phone = :phone
              WHERE id = :id";

    $stmt = $pdo->prepare($query);

    $success = $stmt->execute([
        ':first_name' => $data['first_name'],
        ':last_name' => $data['last_name'],
        ':phone' => $data['phone'],
        ':id' => $user_id
    ]);

    if ($success) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => 'Failed to update profile.'];
    }
}

function changeUserPassword($user_id, $current_password, $new_password) {
    global $pdo;

    // Fetch current hashed password from DB
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($current_password, $row['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
    $success = $stmt->execute([
        ':password' => $hashed_password,
        ':id' => $user_id
    ]);

    if ($success) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => 'Failed to change password.'];
    }
}


?>