<?php
require_once 'includes/functions.php';

// Redirect if not logged in (before any HTML output)
if (!isLoggedIn()) {
    redirect('login.php');
}

$page_title = "Our Timeline";
require_once 'includes/header.php';

$user = getCurrentUser();
$action = $_GET['action'] ?? 'list';
$message = '';

// Handle event creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $eventDate = $_POST['event_date'];
    $eventTime = $_POST['event_time'] ?? null;
    
    if (!empty($title) && !empty($eventDate)) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO events (user_id, title, description, event_date, event_time) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$_SESSION['user_id'], $title, $description, $eventDate, $eventTime])) {
            $message = '<div class="alert alert-success">Event created successfully!</div>';
            $action = 'list';
        } else {
            $message = '<div class="alert alert-danger">Failed to create event.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Title and date are required.</div>';
    }
}

// Handle event deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$_GET['delete'], $_SESSION['user_id']])) {
        $message = '<div class="alert alert-success">Event deleted successfully!</div>';
    }
}

// Get all events
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM events WHERE user_id = ? ORDER BY event_date ASC");
$stmt->execute([$_SESSION['user_id']]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate upcoming and past events
$upcomingEvents = [];
$pastEvents = [];
$today = date('Y-m-d');

foreach ($events as $event) {
    if ($event['event_date'] >= $today) {
        $upcomingEvents[] = $event;
    } else {
        $pastEvents[] = $event;
    }
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 style="font-family: 'Great Vibes', cursive; font-size: 3rem; color: var(--v-rose-deep);">
            <i class="fas fa-heart text-primary me-2"></i>Our Special Dates
        </h1>
        <a href="events.php?action=create" class="btn btn-primary rounded-pill px-4">
            <i class="fas fa-plus me-1"></i>Save a Date
        </a>
    </div>
    
    <?php echo $message; ?>
    
    <?php if ($action === 'create'): ?>
        <!-- Create Event Form -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-plus me-2"></i>Mark a New Moment
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="title" class="form-label">
                                    <i class="fas fa-heading me-1"></i>Event Title
                                </label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       placeholder="Anniversary, Birthday, Date Night, etc." required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left me-1"></i>Description
                                </label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          placeholder="Add details about this event..."></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="event_date" class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Date
                                    </label>
                                    <input type="date" class="form-control" id="event_date" name="event_date" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="event_time" class="form-label">
                                        <i class="fas fa-clock me-1"></i>Time (Optional)
                                    </label>
                                    <input type="time" class="form-control" id="event_time" name="event_time">
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="create_event" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Create Event
                                </button>
                                <a href="events.php" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Events List -->
        <div class="row">
            <!-- Upcoming Events -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-day me-2"></i>Looking Forward
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($upcomingEvents): ?>
                            <div class="list-group">
                                <?php foreach ($upcomingEvents as $event): 
                                    $daysUntil = floor((strtotime($event['event_date']) - time()) / (60 * 60 * 24));
                                ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($event['title']); ?></h6>
                                                <?php if ($event['description']): ?>
                                                    <p class="mb-1 small text-muted">
                                                        <?php echo htmlspecialchars($event['description']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo formatDate($event['event_date']); ?>
                                                    <?php if ($event['event_time']): ?>
                                                        <i class="fas fa-clock ms-2 me-1"></i>
                                                        <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                                    <?php endif; ?>
                                                </small>
                                                <br>
                                                <span class="badge bg-info mt-1">
                                                    <?php echo $daysUntil == 0 ? 'Today!' : ($daysUntil == 1 ? 'Tomorrow' : "In $daysUntil days"); ?>
                                                </span>
                                            </div>
                                            <a href="events.php?delete=<?php echo $event['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Delete this event?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No upcoming events</p>
                                <a href="events.php?action=create" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Add Event
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Past Events -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Cherished Memories
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($pastEvents): ?>
                            <div class="list-group">
                                <?php foreach (array_slice($pastEvents, 0, 5) as $event): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($event['title']); ?></h6>
                                                <?php if ($event['description']): ?>
                                                    <p class="mb-1 small text-muted">
                                                        <?php echo htmlspecialchars($event['description']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo formatDate($event['event_date']); ?>
                                                </small>
                                            </div>
                                            <a href="events.php?delete=<?php echo $event['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Delete this event?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($pastEvents) > 5): ?>
                                <div class="text-center mt-3">
                                    <small class="text-muted">Showing 5 of <?php echo count($pastEvents); ?> past events</small>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No past events</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
