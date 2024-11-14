<?php
// Database connection configuration
$db_host = 'localhost';
$db_name = 'travel_reservation';
$db_user = 'root'; // Update with your database username
$db_pass = ''; // Update with your database password

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_reservation':
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO Reservation (
                            type_id, destination_id, agent_id, customer_name,
                            booking_date, travel_date, status, cost, revenue
                        ) VALUES (?, ?, ?, ?, ?, ?, 'CONFIRMED', ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['type_id'],
                        $_POST['destination_id'],
                        $_POST['agent_id'],
                        $_POST['customer_name'],
                        $_POST['booking_date'],
                        $_POST['travel_date'],
                        $_POST['cost'],
                        $_POST['revenue']
                    ]);
                    $message = "Reservation added successfully!";
                } catch(PDOException $e) {
                    $error = "Error adding reservation: " . $e->getMessage();
                }
                break;

            case 'update_status':
                try {
                    $stmt = $pdo->prepare("
                        UPDATE Reservation 
                        SET status = ?,
                            revenue = CASE WHEN ? = 'CANCELLED' THEN 0 ELSE revenue END 
                        WHERE reservation_id = ?
                    ");
                    $stmt->execute([
                        $_POST['status'],
                        $_POST['status'],
                        $_POST['reservation_id']
                    ]);
                    $message = "Status updated successfully!";
                } catch(PDOException $e) {
                    $error = "Error updating status: " . $e->getMessage();
                }
                break;
        }
    }
}

// Handle filters
$where_conditions = [];
$params = [];

if (isset($_GET['type_id']) && $_GET['type_id'] != '') {
    $where_conditions[] = "r.type_id = ?";
    $params[] = $_GET['type_id'];
}

if (isset($_GET['destination_id']) && $_GET['destination_id'] != '') {
    $where_conditions[] = "r.destination_id = ?";
    $params[] = $_GET['destination_id'];
}

if (isset($_GET['agent_id']) && $_GET['agent_id'] != '') {
    $where_conditions[] = "r.agent_id = ?";
    $params[] = $_GET['agent_id'];
}

if (isset($_GET['status']) && $_GET['status'] != '') {
    $where_conditions[] = "r.status = ?";
    $params[] = $_GET['status'];
}

if (isset($_GET['date_from']) && $_GET['date_from'] != '') {
    $where_conditions[] = "r.travel_date >= ?";
    $params[] = $_GET['date_from'];
}

if (isset($_GET['date_to']) && $_GET['date_to'] != '') {
    $where_conditions[] = "r.travel_date <= ?";
    $params[] = $_GET['date_to'];
}

// Build the WHERE clause
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Fetch required data for dropdowns
$types = $pdo->query("SELECT * FROM ReservationType ORDER BY type_name")->fetchAll();
$destinations = $pdo->query("SELECT * FROM Destination ORDER BY country, city")->fetchAll();
$agents = $pdo->query("SELECT * FROM Agent ORDER BY first_name, last_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-5">
        <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <h2 class="mb-4">Travel Reservation System</h2>

        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Filter Reservations</h4>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Reservation Type</label>
                        <select name="type_id" class="form-select">
                            <option value="">All Types</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?php echo $type['type_id']; ?>" 
                                    <?php echo (isset($_GET['type_id']) && $_GET['type_id'] == $type['type_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Destination</label>
                        <select name="destination_id" class="form-select">
                            <option value="">All Destinations</option>
                            <?php foreach ($destinations as $dest): ?>
                                <option value="<?php echo $dest['destination_id']; ?>"
                                    <?php echo (isset($_GET['destination_id']) && $_GET['destination_id'] == $dest['destination_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dest['city'] . ', ' . $dest['country']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Agent</label>
                        <select name="agent_id" class="form-select">
                            <option value="">All Agents</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo $agent['agent_id']; ?>"
                                    <?php echo (isset($_GET['agent_id']) && $_GET['agent_id'] == $agent['agent_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <?php
                            $statuses = ['CONFIRMED', 'CANCELLED', 'COMPLETED', 'PENDING'];
                            foreach ($statuses as $status):
                            ?>
                                <option value="<?php echo $status; ?>"
                                    <?php echo (isset($_GET['status']) && $_GET['status'] == $status) ? 'selected' : ''; ?>>
                                    <?php echo $status; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Travel Date From</label>
                        <input type="date" name="date_from" class="form-control" 
                               value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : ''; ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Travel Date To</label>
                        <input type="date" name="date_to" class="form-control"
                               value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : ''; ?>">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Apply Filters
                        </button>
                        <a href="?" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0">Summary Statistics</h4>
            </div>
            <div class="card-body">
                <?php
                $stats_query = "
                    SELECT 
                        COUNT(*) as total_bookings,
                        SUM(revenue) as total_revenue,
                        SUM(cost) as total_cost,
                        SUM(revenue - cost) as total_profit,
                        COUNT(CASE WHEN status = 'CANCELLED' THEN 1 END) as cancelled_bookings,
                        COUNT(CASE WHEN status = 'CONFIRMED' THEN 1 END) as confirmed_bookings
                    FROM Reservation r
                    $where_clause
                ";
                $stats_stmt = $pdo->prepare($stats_query);
                $stats_stmt->execute($params);
                $stats = $stats_stmt->fetch();
                ?>
                <div class="row text-center">
                    <div class="col-md-2">
                        <h5>Total Bookings</h5>
                        <p class="h3"><?php echo $stats['total_bookings']; ?></p>
                    </div>
                    <div class="col-md-2">
                        <h5>Total Revenue</h5>
                        <p class="h3">$<?php echo number_format($stats['total_revenue'], 2); ?></p>
                    </div>
                    <div class="col-md-2">
                        <h5>Total Cost</h5>
                        <p class="h3">$<?php echo number_format($stats['total_cost'], 2); ?></p>
                    </div>
                    <div class="col-md-2">
                        <h5>Total Profit</h5>
                        <p class="h3">$<?php echo number_format($stats['total_profit'], 2); ?></p>
                    </div>
                    <div class="col-md-2">
                        <h5>Confirmed</h5>
                        <p class="h3"><?php echo $stats['confirmed_bookings']; ?></p>
                    </div>
                    <div class="col-md-2">
                        <h5>Cancelled</h5>
                        <p class="h3"><?php echo $stats['cancelled_bookings']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add New Reservation Form -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Add New Reservation</h4>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <input type="hidden" name="action" value="add_reservation">
                    
                    <div class="col-md-6">
                        <label class="form-label">Customer Name</label>
                        <input type="text" name="customer_name" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Reservation Type</label>
                        <select name="type_id" class="form-select" required>
                            <?php foreach ($types as $type): ?>
                                <option value="<?php echo $type['type_id']; ?>">
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Destination</label>
                        <select name="destination_id" class="form-select" required>
                            <?php foreach ($destinations as $dest): ?>
                                <option value="<?php echo $dest['destination_id']; ?>">
                                    <?php echo htmlspecialchars($dest['city'] . ', ' . $dest['country']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Agent</label>
                        <select name="agent_id" class="form-select" required>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo $agent['agent_id']; ?>">
                                    <?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Booking Date</label>
                        <input type="date" name="booking_date" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Travel Date</label>
                        <input type="date" name="travel_date" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Cost</label>
                        <input type="number" name="cost" step="0.01" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Revenue</label>
                        <input type="number" name="revenue" step="0.01" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Add Reservation</button>
                </form>
            </div>
        </div>

        <!-- View/Update Reservations -->
        <div class="card">
            <div class="card-header">
                <h4>Current Reservations</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Destination</th>
                                <th>Travel Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("
                                SELECT r.*, rt.type_name, d.city, d.country 
                                FROM Reservation r
                                JOIN ReservationType rt ON r.type_id = rt.type_id
                                JOIN Destination d ON r.destination_id = d.destination_id
                                ORDER BY r.travel_date DESC
                            ");
                            while ($row = $stmt->fetch()): ?>
                                <tr>
                                    <td><?php echo $row['reservation_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['city'] . ', ' . $row['country']); ?></td>
                                    <td><?php echo $row['travel_date']; ?></td>
                                    <td><?php echo $row['status']; ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                                            <select name="status" class="form-select form-select-sm d-inline-block w-auto">
                                                <option value="CONFIRMED" <?php echo $row['status'] == 'CONFIRMED' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="CANCELLED" <?php echo $row['status'] == 'CANCELLED' ? 'selected' : ''; ?>>Cancelled</option>
                                                <option value="COMPLETED" <?php echo $row['status'] == 'COMPLETED' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="PENDING" <?php echo $row['status'] == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>