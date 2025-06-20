<?php
/**
 * Admin Edit Event Page
 * Online Event Booking System
 */

require_once '../../config/config.php';
require_once '../../includes/functions.php';
requireAdminLogin();

$errors = [];
$success = false;

// Get event ID from URL
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch event data
$event = null;
try {
    $stmt = $pdo->prepare("
        SELECT e.*, c.name as category_name, v.name as venue_name 
        FROM events e
        LEFT JOIN categories c ON e.category_id = c.id
        LEFT JOIN venues v ON e.venue_id = v.id
        WHERE e.id = ?
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        setFlashMessage('error', 'Event not found.');
        header('Location: manage-events.php');
        exit();
    }
} catch (Exception $e) {
    setFlashMessage('error', 'Database error: ' . $e->getMessage());
    header('Location: manage-events.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize input data
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $category_id = (int)$_POST['category_id'];
        $venue_id = (int)$_POST['venue_id'];
        $organizer_name = sanitizeInput($_POST['organizer_name']);
        $organizer_email = sanitizeInput($_POST['organizer_email']);
        $organizer_phone = sanitizeInput($_POST['organizer_phone']);
        $event_date = $_POST['event_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $price = (float)$_POST['price'];
        $total_tickets = (int)$_POST['total_tickets'];
        $status = $_POST['status'];
        $current_image = $event['image'];
        $image_removed = isset($_POST['remove_image']) && $_POST['remove_image'] == '1';

        // Validation
        if (empty($title)) $errors[] = 'Event title is required.';
        if (empty($description)) $errors[] = 'Event description is required.';
        if (empty($category_id)) $errors[] = 'Please select a category.';
        if (empty($venue_id)) $errors[] = 'Please select a venue.';
        if (empty($organizer_name)) $errors[] = 'Organizer name is required.';
        if (empty($organizer_email) || !isValidEmail($organizer_email)) $errors[] = 'Valid organizer email is required.';
        if (empty($event_date)) $errors[] = 'Event date is required.';
        if (empty($start_time)) $errors[] = 'Start time is required.';
        if (empty($end_time)) $errors[] = 'End time is required.';
        if ($price < 0) $errors[] = 'Price cannot be negative.';
        if ($total_tickets <= 0) $errors[] = 'Total tickets must be greater than 0.';
        
        // Validate date is not in the past (except for already past events)
        if (strtotime($event_date) < strtotime(date('Y-m-d')) && 
            strtotime($event['event_date']) >= strtotime(date('Y-m-d'))) {
            $errors[] = 'Event date cannot be changed to a past date.';
        }
        
        // Validate times
        if (strtotime($end_time) <= strtotime($start_time)) {
            $errors[] = 'End time must be after start time.';
        }

        // Handle image upload/removal
        $image_filename = $current_image;
        if ($image_removed) {
            // Remove existing image
            if ($current_image && file_exists('../../' . UPLOAD_PATH . $current_image)) {
                unlink('../../' . UPLOAD_PATH . $current_image);
            }
            $image_filename = null;
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            try {
                // Remove old image if exists
                if ($current_image && file_exists('../../' . UPLOAD_PATH . $current_image)) {
                    unlink('../../' . UPLOAD_PATH . $current_image);
                }
                $image_filename = uploadFile($_FILES['image']);
            } catch (Exception $e) {
                $errors[] = 'Image upload error: ' . $e->getMessage();
            }
        }

        // Calculate available tickets
        $booked_tickets = $event['total_tickets'] - $event['available_tickets'];
        $new_available_tickets = max(0, $total_tickets - $booked_tickets);
        
        if ($total_tickets < $booked_tickets) {
            $errors[] = "Cannot reduce total tickets below $booked_tickets (already booked tickets).";
        }

        // If no errors, update database
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE events SET
                        title = ?, description = ?, category_id = ?, venue_id = ?, 
                        organizer_name = ?, organizer_email = ?, organizer_phone = ?, 
                        event_date = ?, start_time = ?, end_time = ?, 
                        price = ?, total_tickets = ?, available_tickets = ?, 
                        image = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $title, $description, $category_id, $venue_id, $organizer_name,
                    $organizer_email, $organizer_phone, $event_date, $start_time, $end_time,
                    $price, $total_tickets, $new_available_tickets, $image_filename, $status,
                    $event_id
                ]);

                logActivity('event_updated', "Event updated: $title (ID: $event_id)", 'admin');
                setFlashMessage('success', 'Event updated successfully!');
                
                header('Location: manage-events.php');
                exit();
                
            } catch (Exception $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
                // Delete uploaded image if database update failed
                if (isset($image_filename) && $image_filename != $current_image && 
                    file_exists('../../' . UPLOAD_PATH . $image_filename)) {
                    unlink('../../' . UPLOAD_PATH . $image_filename);
                }
            }
        }
    }
}

// Get categories and venues for form
$categories = getCategories();
$stmt = $pdo->query("SELECT * FROM venues ORDER BY name");
$venues = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Admin | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include '../../includes/admin-header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/admin-sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit Event: <?php echo htmlspecialchars($event['title']); ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="manage-events.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Events
                        </a>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h6>Please fix the following errors:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-8">
                                            <label for="title" class="form-label">Event Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="<?php echo htmlspecialchars($_POST['title'] ?? $event['title']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="draft" <?php echo ($_POST['status'] ?? $event['status']) === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                <option value="published" <?php echo ($_POST['status'] ?? $event['status']) === 'published' ? 'selected' : ''; ?>>Published</option>
                                                <option value="cancelled" <?php echo ($_POST['status'] ?? $event['status']) === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Event Description <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($_POST['description'] ?? $event['description']); ?></textarea>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                            <select class="form-select" id="category_id" name="category_id" required>
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>" 
                                                            <?php echo ($_POST['category_id'] ?? $event['category_id']) == $category['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="venue_id" class="form-label">Venue <span class="text-danger">*</span></label>
                                            <select class="form-select" id="venue_id" name="venue_id" required>
                                                <option value="">Select Venue</option>
                                                <?php foreach ($venues as $venue): ?>
                                                    <option value="<?php echo $venue['id']; ?>" 
                                                            <?php echo ($_POST['venue_id'] ?? $event['venue_id']) == $venue['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($venue['name'] . ' - ' . $venue['city']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="event_date" class="form-label">Event Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="event_date" name="event_date" 
                                                   value="<?php echo $_POST['event_date'] ?? $event['event_date']; ?>" 
                                                   min="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control" id="start_time" name="start_time" 
                                                   value="<?php echo $_POST['start_time'] ?? $event['start_time']; ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control" id="end_time" name="end_time" 
                                                   value="<?php echo $_POST['end_time'] ?? $event['end_time']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="price" class="form-label">Ticket Price (<?php echo CURRENCY_SYMBOL ?? '$'; ?>) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="price" name="price" 
                                                   value="<?php echo $_POST['price'] ?? $event['price']; ?>" 
                                                   min="0" step="0.01" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="total_tickets" class="form-label">Total Tickets <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="total_tickets" name="total_tickets" 
                                                   value="<?php echo $_POST['total_tickets'] ?? $event['total_tickets']; ?>" 
                                                   min="<?php echo max(1, $event['total_tickets'] - $event['available_tickets']); ?>" required>
                                            <div class="form-text">
                                                <?php $booked = $event['total_tickets'] - $event['available_tickets']; ?>
                                                Currently booked: <?php echo $booked; ?> tickets
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="image" class="form-label">Event Image</label>
                                        <?php if ($event['image']): ?>
                                            <div class="mb-2">
                                                <img src="../../<?php echo UPLOAD_PATH . $event['image']; ?>" class="img-thumbnail" style="max-height: 150px;">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                                                    <label class="form-check-label" for="remove_image">
                                                        Remove current image
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" id="image" name="image" 
                                               accept="image/*">
                                        <div class="form-text">Upload an image for the event (optional). Supported formats: JPG, PNG, GIF. Max size: 5MB.</div>
                                    </div>

                                    <h5 class="mb-3">Organizer Information</h5>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="organizer_name" class="form-label">Organizer Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="organizer_name" name="organizer_name" 
                                                   value="<?php echo htmlspecialchars($_POST['organizer_name'] ?? $event['organizer_name']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="organizer_email" class="form-label">Organizer Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="organizer_email" name="organizer_email" 
                                                   value="<?php echo htmlspecialchars($_POST['organizer_email'] ?? $event['organizer_email']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="organizer_phone" class="form-label">Organizer Phone</label>
                                            <input type="tel" class="form-control" id="organizer_phone" name="organizer_phone" 
                                                   value="<?php echo htmlspecialchars($_POST['organizer_phone'] ?? $event['organizer_phone']); ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <a href="manage-events.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update Event
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-info-circle"></i> Event Statistics
                                </h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">
                                        <i class="fas fa-calendar me-2"></i>
                                        <strong>Created:</strong> <?php echo date('M j, Y', strtotime($event['created_at'])); ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-sync-alt me-2"></i>
                                        <strong>Last Updated:</strong> <?php echo date('M j, Y', strtotime($event['updated_at'])); ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-ticket-alt me-2"></i>
                                        <strong>Booked Tickets:</strong> <?php echo $event['total_tickets'] - $event['available_tickets']; ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-money-bill-wave me-2"></i>
                                        <strong>Total Revenue:</strong> 
                                        <?php 
                                            try {
                                                $stmt = $pdo->prepare("
                                                    SELECT SUM(bi.total_price) as total_revenue
                                                    FROM booking_items bi
                                                    JOIN bookings b ON bi.booking_id = b.id
                                                    WHERE bi.event_id = ? AND b.payment_status = 'paid'
                                                ");
                                                $stmt->execute([$event_id]);
                                                $revenue = $stmt->fetch()['total_revenue'];
                                                echo CURRENCY_SYMBOL . number_format($revenue ?? 0, 2);
                                            } catch (Exception $e) {
                                                echo 'N/A';
                                            }
                                        ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-exclamation-triangle"></i> Important Notes
                                </h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled small mb-0">
                                    <li class="mb-2 text-danger">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Changing event date/time after tickets are sold may require notifying attendees.
                                    </li>
                                    <li class="mb-2 text-danger">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Reducing total tickets below currently booked amount is not allowed.
                                    </li>
                                    <li class="mb-0 text-danger">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Cancelling an event will automatically notify all ticket holders.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation and enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const startTimeInput = document.getElementById('start_time');
            const endTimeInput = document.getElementById('end_time');
            const totalTicketsInput = document.getElementById('total_tickets');
            const bookedTickets = <?php echo $event['total_tickets'] - $event['available_tickets']; ?>;
            
            // Validate end time is after start time
            function validateTimes() {
                if (startTimeInput.value && endTimeInput.value) {
                    if (endTimeInput.value <= startTimeInput.value) {
                        endTimeInput.setCustomValidity('End time must be after start time');
                    } else {
                        endTimeInput.setCustomValidity('');
                    }
                }
            }
            
            // Validate tickets not below booked count
            function validateTickets() {
                if (parseInt(totalTicketsInput.value) < bookedTickets) {
                    totalTicketsInput.setCustomValidity(`Cannot be less than ${bookedTickets} (already booked tickets)`);
                } else {
                    totalTicketsInput.setCustomValidity('');
                }
            }
            
            startTimeInput.addEventListener('change', validateTimes);
            endTimeInput.addEventListener('change', validateTimes);
            totalTicketsInput.addEventListener('change', validateTickets);
            
            // Image preview and validation
            const imageInput = document.getElementById('image');
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file size (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File size must be less than 5MB');
                        this.value = '';
                        return;
                    }
                    
                    // Validate file type
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Please select a valid image file (JPG, PNG, or GIF)');
                        this.value = '';
                        return;
                    }
                }
            });
        });
    </script>
</body>
</html>