<?php
/**
 * AJAX Event Filter Handler
 * Online Event Booking System
 */

require_once '../config/config.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if request is POST and has valid data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get and sanitize input parameters
$filters = json_decode(file_get_contents('php://input'), true);

if (!$filters) {
    $filters = $_POST;
}

// Extract filter parameters
$category_id = isset($filters['category']) ? (int)$filters['category'] : 0;
$venue_id = isset($filters['venue']) ? (int)$filters['venue'] : 0;
$date_from = isset($filters['date_from']) ? sanitizeInput($filters['date_from']) : '';
$date_to = isset($filters['date_to']) ? sanitizeInput($filters['date_to']) : '';
$min_price = isset($filters['min_price']) ? (float)$filters['min_price'] : 0;
$max_price = isset($filters['max_price']) ? (float)$filters['max_price'] : 0;
$search_query = isset($filters['search']) ? sanitizeInput($filters['search']) : '';
$sort_by = isset($filters['sort_by']) ? sanitizeInput($filters['sort_by']) : 'event_date';
$sort_order = isset($filters['sort_order']) ? sanitizeInput($filters['sort_order']) : 'ASC';
$page = isset($filters['page']) ? (int)$filters['page'] : 1;
$limit = isset($filters['limit']) ? (int)$filters['limit'] : EVENTS_PER_PAGE;

// Validate inputs
$allowed_sort_fields = ['event_date', 'title', 'price', 'created_at'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'event_date';
}

$sort_order = strtoupper($sort_order);
if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'ASC';
}

$offset = ($page - 1) * $limit;

try {
    // Build the base query
    $sql = "SELECT e.*, c.name as category_name, v.name as venue_name, v.city as venue_city,
                   (SELECT COUNT(*) FROM booking_items bi 
                    JOIN bookings b ON bi.booking_id = b.id 
                    WHERE bi.event_id = e.id AND b.booking_status = 'confirmed') as booked_count
            FROM events e 
            LEFT JOIN categories c ON e.category_id = c.id 
            LEFT JOIN venues v ON e.venue_id = v.id 
            WHERE e.status = 'published' AND e.event_date >= CURDATE()";

    $count_sql = "SELECT COUNT(*) as total
                  FROM events e 
                  LEFT JOIN categories c ON e.category_id = c.id 
                  LEFT JOIN venues v ON e.venue_id = v.id 
                  WHERE e.status = 'published' AND e.event_date >= CURDATE()";

    $params = [];

    // Apply filters
    if (!empty($search_query)) {
        $search_condition = " AND (e.title LIKE ? OR e.description LIKE ? OR e.organizer_name LIKE ?)";
        $sql .= $search_condition;
        $count_sql .= $search_condition;
        $search_term = '%' . $search_query . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    if ($category_id > 0) {
        $category_condition = " AND e.category_id = ?";
        $sql .= $category_condition;
        $count_sql .= $category_condition;
        $params[] = $category_id;
    }

    if ($venue_id > 0) {
        $venue_condition = " AND e.venue_id = ?";
        $sql .= $venue_condition;
        $count_sql .= $venue_condition;
        $params[] = $venue_id;
    }

    if (!empty($date_from)) {
        $date_from_condition = " AND e.event_date >= ?";
        $sql .= $date_from_condition;
        $count_sql .= $date_from_condition;
        $params[] = $date_from;
    }

    if (!empty($date_to)) {
        $date_to_condition = " AND e.event_date <= ?";
        $sql .= $date_to_condition;
        $count_sql .= $date_to_condition;
        $params[] = $date_to;
    }

    if ($min_price > 0) {
        $min_price_condition = " AND e.price >= ?";
        $sql .= $min_price_condition;
        $count_sql .= $min_price_condition;
        $params[] = $min_price;
    }

    if ($max_price > 0) {
        $max_price_condition = " AND e.price <= ?";
        $sql .= $max_price_condition;
        $count_sql .= $max_price_condition;
        $params[] = $max_price;
    }

    // Get total count
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_events = $count_stmt->fetchColumn();

    // Add sorting and pagination to main query
    $sql .= " ORDER BY e.$sort_by $sort_order LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    // Execute main query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    // Calculate pagination info
    $total_pages = ceil($total_events / $limit);

    // Format events for JSON response
    $formatted_events = [];
    foreach ($events as $event) {
        $formatted_events[] = [
            'id' => (int)$event['id'],
            'title' => $event['title'],
            'description' => $event['description'],
            'short_description' => substr($event['description'], 0, 100) . (strlen($event['description']) > 100 ? '...' : ''),
            'category_name' => $event['category_name'],
            'venue_name' => $event['venue_name'],
            'venue_city' => $event['venue_city'],
            'venue_full' => $event['venue_name'] ? $event['venue_name'] . ', ' . $event['venue_city'] : '',
            'organizer_name' => $event['organizer_name'],
            'event_date' => $event['event_date'],
            'start_time' => $event['start_time'],
            'end_time' => $event['end_time'],
            'price' => (float)$event['price'],
            'formatted_price' => formatCurrency($event['price']),
            'total_tickets' => (int)$event['total_tickets'],
            'available_tickets' => (int)$event['available_tickets'],
            'booked_count' => (int)$event['booked_count'],
            'image' => $event['image'],
            'image_url' => $event['image'] ? '../assets/images/events/' . $event['image'] : null,
            'formatted_date' => formatDate($event['event_date']),
            'formatted_time' => formatTime($event['start_time']),
            'is_sold_out' => $event['available_tickets'] == 0,
            'is_almost_sold_out' => $event['available_tickets'] < 10 && $event['available_tickets'] > 0,
            'days_until_event' => floor((strtotime($event['event_date']) - time()) / (60 * 60 * 24)),
            'detail_url' => 'event-details.php?id=' . $event['id']
        ];
    }

    // Prepare response
    $response = [
        'success' => true,
        'events' => $formatted_events,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_events' => $total_events,
            'events_per_page' => $limit,
            'has_previous' => $page > 1,
            'has_next' => $page < $total_pages,
            'previous_page' => $page > 1 ? $page - 1 : null,
            'next_page' => $page < $total_pages ? $page + 1 : null
        ],
        'filters_applied' => [
            'category' => $category_id,
            'venue' => $venue_id,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'min_price' => $min_price,
            'max_price' => $max_price,
            'search' => $search_query,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order
        ]
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Filter Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred while filtering events',
        'message' => 'Please try again later'
    ]);

} catch (Exception $e) {
    error_log("General Filter Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An unexpected error occurred',
        'message' => 'Please try again later'
    ]);
}

/**
 * Get filter options for dropdowns
 */
function getFilterOptions() {
    global $pdo;
    
    try {
        // Get categories
        $categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
        $categories = $categories_stmt->fetchAll();
        
        // Get venues
        $venues_stmt = $pdo->query("SELECT * FROM venues ORDER BY name");
        $venues = $venues_stmt->fetchAll();
        
        // Get price range
        $price_stmt = $pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM events WHERE status = 'published'");
        $price_range = $price_stmt->fetch();
        
        return [
            'categories' => $categories,
            'venues' => $venues,
            'price_range' => [
                'min' => (float)$price_range['min_price'],
                'max' => (float)$price_range['max_price']
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Filter Options Error: " . $e->getMessage());
        return [
            'categories' => [],
            'venues' => [],
            'price_range' => ['min' => 0, 'max' => 1000]
        ];
    }
}

// Handle request for filter options
if (isset($_GET['action']) && $_GET['action'] === 'get_options') {
    $options = getFilterOptions();
    echo json_encode([
        'success' => true,
        'options' => $options
    ]);
    exit;
}
?>