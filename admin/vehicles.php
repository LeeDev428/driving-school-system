<?php
// Turn off error reporting for AJAX requests to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering immediately
ob_start();

session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["user_type"] !== "admin") {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../config.php";

// Initialize variables
$page_title = "Vehicles";
$header_title = "Vehicle Management";
$notification_count = 3;

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    // Clean any output buffer and set JSON header
    ob_clean();
    header('Content-Type: application/json');
    
    // Suppress any further output
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    switch ($_POST['action']) {
        case 'add_vehicle':
            $make = $_POST['make'];
            $model = $_POST['model'];
            $year = $_POST['year'];
            $license_plate = $_POST['license_plate'];
            $transmission_type = $_POST['transmission_type'];
            $vehicle_type = $_POST['vehicle_type'];
            $color = $_POST['color'];
            $notes = $_POST['notes'];
            
            $sql = "INSERT INTO vehicles (make, model, year, license_plate, transmission_type, vehicle_type, color, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssisssss", $make, $model, $year, $license_plate, $transmission_type, $vehicle_type, $color, $notes);
                
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Vehicle added successfully!']);
                } else {
                    if (mysqli_errno($conn) == 1062) { // Duplicate entry
                        echo json_encode(['success' => false, 'message' => 'License plate already exists!']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error adding vehicle.']);
                    }
                }
                mysqli_stmt_close($stmt);
            }
            exit;
            
        case 'update_vehicle':
            $vehicle_id = $_POST['vehicle_id'];
            $make = $_POST['make'];
            $model = $_POST['model'];
            $year = $_POST['year'];
            $license_plate = $_POST['license_plate'];
            $transmission_type = $_POST['transmission_type'];
            $vehicle_type = $_POST['vehicle_type'];
            $color = $_POST['color'];
            $is_available = isset($_POST['is_available']) ? 1 : 0;
            $notes = $_POST['notes'];
            $last_maintenance = !empty($_POST['last_maintenance']) ? $_POST['last_maintenance'] : null;
            $next_maintenance = !empty($_POST['next_maintenance']) ? $_POST['next_maintenance'] : null;
            
            $sql = "UPDATE vehicles SET make = ?, model = ?, year = ?, license_plate = ?, transmission_type = ?, 
                    vehicle_type = ?, color = ?, is_available = ?, notes = ?, last_maintenance = ?, next_maintenance = ? 
                    WHERE id = ?";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssissssissi", $make, $model, $year, $license_plate, $transmission_type, 
                                     $vehicle_type, $color, $is_available, $notes, $last_maintenance, $next_maintenance, $vehicle_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Vehicle updated successfully!']);
                } else {
                    if (mysqli_errno($conn) == 1062) {
                        echo json_encode(['success' => false, 'message' => 'License plate already exists!']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error updating vehicle.']);
                    }
                }
                mysqli_stmt_close($stmt);
            }
            exit;
            
        case 'delete_vehicle':
            $vehicle_id = $_POST['vehicle_id'];
            
            // Set vehicle as unavailable instead of deleting (safer)
            $sql = "UPDATE vehicles SET is_available = 0 WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $vehicle_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Vehicle deactivated successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error deactivating vehicle.']);
                }
                mysqli_stmt_close($stmt);
            }
            exit;
    }
}

// Get all vehicles with appointment statistics
$vehicles_sql = "SELECT v.*, 
                        COUNT(a.id) as total_appointments,
                        COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_appointments,
                        COUNT(CASE WHEN a.appointment_date = CURDATE() THEN 1 END) as today_appointments
                 FROM vehicles v 
                 LEFT JOIN appointments a ON v.id = a.vehicle_id
                 GROUP BY v.id
                 ORDER BY v.make, v.model";

$vehicles = [];
if ($result = mysqli_query($conn, $vehicles_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $vehicles[] = $row;
    }
}

// Generate content
ob_start();
?>

<div class="vehicles-container">
    <div class="page-header">
        <div class="header-left">
            <h2>Vehicle Management</h2>
            <p>Manage driving school fleet and vehicle assignments</p>
        </div>
        <button class="add-btn" onclick="openAddModal()">
            <i class="fas fa-car"></i> Add New Vehicle
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-car"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count($vehicles); ?></h3>
                <p>Total Vehicles</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count(array_filter($vehicles, function($v) { return $v['is_available']; })); ?></h3>
                <p>Available Vehicles</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-cogs"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count(array_filter($vehicles, function($v) { return $v['transmission_type'] == 'manual'; })); ?></h3>
                <p>Manual Transmission</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo array_sum(array_column($vehicles, 'today_appointments')); ?></h3>
                <p>Today's Bookings</p>
            </div>
        </div>
    </div>

    <!-- Vehicles Grid -->
    <div class="vehicles-grid">
        <?php foreach ($vehicles as $vehicle): ?>
            <div class="vehicle-card <?php echo $vehicle['is_available'] ? 'available' : 'unavailable'; ?>">
                <div class="vehicle-header">
                    <div class="vehicle-image">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="vehicle-info">
                        <h3><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></h3>
                        <p class="vehicle-year"><?php echo $vehicle['year']; ?></p>
                        <p class="vehicle-plate"><?php echo htmlspecialchars($vehicle['license_plate']); ?></p>
                    </div>
                    <div class="vehicle-status">
                        <span class="status-badge <?php echo $vehicle['is_available'] ? 'available' : 'unavailable'; ?>">
                            <?php echo $vehicle['is_available'] ? 'Available' : 'Unavailable'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="vehicle-details">
                    <div class="detail-row">
                        <span class="detail-label">
                            <i class="fas fa-cogs"></i> Transmission:
                        </span>
                        <span class="transmission-badge <?php echo $vehicle['transmission_type']; ?>">
                            <?php echo ucfirst($vehicle['transmission_type']); ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">
                            <i class="fas fa-car-side"></i> Type:
                        </span>
                        <span><?php echo ucfirst($vehicle['vehicle_type']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">
                            <i class="fas fa-palette"></i> Color:
                        </span>
                        <span><?php echo htmlspecialchars($vehicle['color'] ?: 'Not specified'); ?></span>
                    </div>
                    <?php if ($vehicle['last_maintenance']): ?>
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="fas fa-tools"></i> Last Service:
                            </span>
                            <span><?php echo date('M j, Y', strtotime($vehicle['last_maintenance'])); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($vehicle['next_maintenance']): ?>
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="fas fa-calendar-plus"></i> Next Service:
                            </span>
                            <span class="<?php echo strtotime($vehicle['next_maintenance']) <= strtotime('+30 days') ? 'maintenance-due' : ''; ?>">
                                <?php echo date('M j, Y', strtotime($vehicle['next_maintenance'])); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="vehicle-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $vehicle['total_appointments']; ?></span>
                        <span class="stat-label">Total Bookings</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $vehicle['completed_appointments']; ?></span>
                        <span class="stat-label">Completed</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $vehicle['today_appointments']; ?></span>
                        <span class="stat-label">Today</span>
                    </div>
                </div>
                
                <?php if ($vehicle['notes']): ?>
                    <div class="vehicle-notes">
                        <i class="fas fa-sticky-note"></i>
                        <?php echo htmlspecialchars($vehicle['notes']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="vehicle-actions">
                    <button class="action-btn edit" onclick="editVehicle(<?php echo htmlspecialchars(json_encode($vehicle)); ?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="action-btn toggle" onclick="toggleVehicleStatus(<?php echo $vehicle['id']; ?>, <?php echo $vehicle['is_available'] ? 'false' : 'true'; ?>)">
                        <i class="fas fa-<?php echo $vehicle['is_available'] ? 'pause' : 'play'; ?>"></i> 
                        <?php echo $vehicle['is_available'] ? 'Deactivate' : 'Activate'; ?>
                    </button>
                    <button class="action-btn schedule" onclick="viewVehicleSchedule(<?php echo $vehicle['id']; ?>)">
                        <i class="fas fa-calendar"></i> Schedule
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add/Edit Vehicle Modal -->
<div id="vehicle-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Add New Vehicle</h3>
            <span class="close-btn" onclick="closeVehicleModal()">&times;</span>
        </div>
        <form id="vehicle-form">
            <input type="hidden" id="vehicle_id" name="vehicle_id">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="make">Make *</label>
                    <input type="text" id="make" name="make" required placeholder="e.g., Toyota">
                </div>
                <div class="form-group">
                    <label for="model">Model *</label>
                    <input type="text" id="model" name="model" required placeholder="e.g., Corolla">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="year">Year *</label>
                    <input type="number" id="year" name="year" min="2000" max="2030" required placeholder="e.g., 2022">
                </div>
                <div class="form-group">
                    <label for="license_plate">License Plate *</label>
                    <input type="text" id="license_plate" name="license_plate" required placeholder="e.g., ABC-123">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="transmission_type">Transmission *</label>
                    <select id="transmission_type" name="transmission_type" required>
                        <option value="">Select transmission</option>
                        <option value="automatic">Automatic</option>
                        <option value="manual">Manual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="vehicle_type">Vehicle Type *</label>
                    <select id="vehicle_type" name="vehicle_type" required>
                        <option value="">Select type</option>
                        <option value="car">Car</option>
                        <option value="motorcycle">Motorcycle</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="color">Color</label>
                    <input type="text" id="color" name="color" placeholder="e.g., White">
                </div>
                <div class="form-group" id="available-checkbox" style="display: none;">
                    <label class="checkbox-label">
                        <input type="checkbox" id="is_available" name="is_available" checked>
                        <span class="checkmark"></span>
                        Available for bookings
                    </label>
                </div>
            </div>
            
            <div class="form-row" id="maintenance-row" style="display: none;">
                <div class="form-group">
                    <label for="last_maintenance">Last Maintenance</label>
                    <input type="date" id="last_maintenance" name="last_maintenance">
                </div>
                <div class="form-group">
                    <label for="next_maintenance">Next Maintenance</label>
                    <input type="date" id="next_maintenance" name="next_maintenance">
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Any special notes about this vehicle..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeVehicleModal()" class="cancel-btn">Cancel</button>
                <button type="submit" id="submit-btn" class="submit-btn">Add Vehicle</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();

// Add additional styles
$extra_styles = <<<EOT
<style>
.vehicles-container {
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #3a3f48;
}

.header-left h2 {
    margin: 0 0 5px 0;
    color: #ffcc00;
}

.header-left p {
    margin: 0;
    color: #8b8d93;
}

.add-btn {
    background: #ffcc00;
    color: #1a1d24;
    border: none;
    padding: 12px 20px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.add-btn:hover {
    background: #e6b800;
    transform: translateY(-2px);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #282c34;
    border: 1px solid #3a3f48;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background: rgba(255, 204, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffcc00;
    font-size: 20px;
}

.stat-info h3 {
    margin: 0 0 5px 0;
    font-size: 24px;
    font-weight: 600;
}

.stat-info p {
    margin: 0;
    color: #8b8d93;
    font-size: 14px;
}

.vehicles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
}

.vehicle-card {
    background: #282c34;
    border: 1px solid #3a3f48;
    border-radius: 10px;
    padding: 20px;
    transition: all 0.3s;
}

.vehicle-card:hover {
    border-color: #ffcc00;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.vehicle-card.unavailable {
    opacity: 0.7;
    border-color: #666;
}

.vehicle-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.vehicle-image {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background: #ffcc00;
    color: #1a1d24;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-right: 15px;
}

.vehicle-info {
    flex: 1;
}

.vehicle-info h3 {
    margin: 0 0 5px 0;
    font-size: 18px;
}

.vehicle-year {
    margin: 0 0 3px 0;
    color: #8b8d93;
    font-size: 14px;
}

.vehicle-plate {
    margin: 0;
    color: #ffcc00;
    font-size: 13px;
    font-weight: 600;
}

.vehicle-status {
    margin-left: 10px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.available {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
}

.status-badge.unavailable {
    background: rgba(244, 67, 54, 0.2);
    color: #f44336;
}

.vehicle-details {
    margin-bottom: 20px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 14px;
}

.detail-label {
    color: #8b8d93;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

.detail-label i {
    width: 14px;
    color: #ffcc00;
}

.transmission-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.transmission-badge.automatic {
    background: rgba(33, 150, 243, 0.2);
    color: #2196F3;
}

.transmission-badge.manual {
    background: rgba(255, 152, 0, 0.2);
    color: #ff9800;
}

.maintenance-due {
    color: #ff3333 !important;
    font-weight: 600;
}

.vehicle-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    padding: 15px;
    background: #1e2129;
    border-radius: 6px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 18px;
    font-weight: 600;
    color: #ffcc00;
}

.stat-label {
    font-size: 11px;
    color: #8b8d93;
    text-transform: uppercase;
}

.vehicle-notes {
    background: rgba(255, 204, 0, 0.1);
    border-left: 3px solid #ffcc00;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 0 4px 4px 0;
    font-size: 14px;
    color: #8b8d93;
}

.vehicle-notes i {
    color: #ffcc00;
    margin-right: 8px;
}

.vehicle-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.action-btn {
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 4px;
    flex: 1;
    justify-content: center;
}

.action-btn.edit {
    background: #2196F3;
    color: white;
}

.action-btn.toggle {
    background: #ff9800;
    color: white;
}

.action-btn.schedule {
    background: #4CAF50;
    color: white;
}

.action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
}

.modal-content {
    background-color: #282c34;
    margin: 2% auto;
    padding: 0;
    border: 1px solid #3a3f48;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #3a3f48;
}

.modal-header h3 {
    margin: 0;
    color: #ffcc00;
}

.close-btn {
    color: #8b8d93;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    border: none;
    background: none;
}

.close-btn:hover {
    color: white;
}

.modal form {
    padding: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #8b8d93;
    font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #3a3f48;
    border-radius: 5px;
    background: #1e2129;
    color: white;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #ffcc00;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: white;
}

.checkbox-label input[type="checkbox"] {
    width: auto;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #3a3f48;
}

.cancel-btn {
    background: none;
    border: 1px solid #3a3f48;
    color: #8b8d93;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
}

.cancel-btn:hover {
    border-color: #8b8d93;
    color: white;
}

.submit-btn {
    background: #ffcc00;
    border: none;
    color: #1a1d24;
    padding: 10px 20px;
    border-radius: 5px;
    font-weight: 600;
    cursor: pointer;
}

.submit-btn:hover {
    background: #e6b800;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .vehicles-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .vehicle-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .vehicle-stats {
        flex-direction: column;
        gap: 10px;
    }
    
    .vehicle-actions {
        flex-direction: column;
    }
}
</style>
EOT;

// Add additional scripts
$extra_scripts = <<<EOT
<script>
let isEditing = false;

function openAddModal() {
    document.getElementById('modal-title').textContent = 'Add New Vehicle';
    document.getElementById('submit-btn').textContent = 'Add Vehicle';
    document.getElementById('vehicle-form').reset();
    document.getElementById('vehicle_id').value = '';
    document.getElementById('available-checkbox').style.display = 'none';
    document.getElementById('maintenance-row').style.display = 'none';
    isEditing = false;
    
    document.getElementById('vehicle-modal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function editVehicle(vehicle) {
    document.getElementById('modal-title').textContent = 'Edit Vehicle';
    document.getElementById('submit-btn').textContent = 'Update Vehicle';
    
    // Fill form with vehicle data
    document.getElementById('vehicle_id').value = vehicle.id;
    document.getElementById('make').value = vehicle.make;
    document.getElementById('model').value = vehicle.model;
    document.getElementById('year').value = vehicle.year;
    document.getElementById('license_plate').value = vehicle.license_plate;
    document.getElementById('transmission_type').value = vehicle.transmission_type;
    document.getElementById('vehicle_type').value = vehicle.vehicle_type;
    document.getElementById('color').value = vehicle.color || '';
    document.getElementById('is_available').checked = vehicle.is_available == 1;
    document.getElementById('notes').value = vehicle.notes || '';
    document.getElementById('last_maintenance').value = vehicle.last_maintenance || '';
    document.getElementById('next_maintenance').value = vehicle.next_maintenance || '';
    
    document.getElementById('available-checkbox').style.display = 'block';
    document.getElementById('maintenance-row').style.display = 'grid';
    isEditing = true;
    
    document.getElementById('vehicle-modal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeVehicleModal() {
    document.getElementById('vehicle-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('vehicle-form').reset();
    isEditing = false;
}

function toggleVehicleStatus(vehicleId, newStatus) {
    const action = newStatus === 'true' ? 'activate' : 'deactivate';
    if (confirm(`Are you sure you want to \${action} this vehicle?`)) {
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_vehicle&vehicle_id=\${vehicleId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
}

function viewVehicleSchedule(vehicleId) {
    // This would open a modal showing the vehicle's schedule
    alert('View schedule for vehicle ID: ' + vehicleId);
}

// Form submission
document.getElementById('vehicle-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const action = isEditing ? 'update_vehicle' : 'add_vehicle';
    formData.append('action', action);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeVehicleModal();
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('vehicle-modal');
    if (event.target === modal) {
        closeVehicleModal();
    }
}
</script>
EOT;

// Include the main layout template
include "../layouts/main_layout.php";
?>
