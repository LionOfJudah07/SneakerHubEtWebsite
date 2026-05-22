<?php
require_once '../config.php';

// Require buyer login
require_buyer();

$page_title = 'Address Book - ' . SITE_NAME;

// Get user data
$user = new User();
$user_data = $user->getUserById($_SESSION['user_id']);

// Handle address actions
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_address'])) {
        // Add new address
        $address_data = [
            'user_id' => $_SESSION['user_id'],
            'type' => sanitize_input($_POST['type']),
            'full_name' => sanitize_input($_POST['full_name']),
            'phone' => sanitize_input($_POST['phone']),
            'address_line1' => sanitize_input($_POST['address_line1']),
            'address_line2' => sanitize_input($_POST['address_line2'] ?? ''),
            'city' => sanitize_input($_POST['city']),
            'region' => sanitize_input($_POST['region']),
            'is_default' => isset($_POST['is_default']) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Validate required fields
        $required = ['type', 'full_name', 'phone', 'address_line1', 'city', 'region'];
        foreach ($required as $field) {
            if (empty($address_data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        
        // Validate phone
        if (!empty($address_data['phone']) && !validate_phone($address_data['phone'])) {
            $errors[] = 'Please enter a valid Ethiopian phone number (format: +251911234567).';
        }
        
        if (empty($errors)) {
            try {
                $db = new Database();
                
                // If setting as default, remove default from other addresses
                if ($address_data['is_default']) {
                    $db->query("UPDATE user_addresses SET is_default = false WHERE user_id = :user_id");
                    $db->bind(':user_id', $_SESSION['user_id']);
                    $db->execute();
                }
                
                // Insert new address
                $db->insert('user_addresses', $address_data);
                $success = 'Address added successfully!';
            } catch (Exception $e) {
                $errors[] = 'Failed to add address: ' . $e->getMessage();
            }
        }
        
    } elseif (isset($_POST['update_address'])) {
        // Update address
        $address_id = intval($_POST['address_id']);
        $address_data = [
            'type' => sanitize_input($_POST['type']),
            'full_name' => sanitize_input($_POST['full_name']),
            'phone' => sanitize_input($_POST['phone']),
            'address_line1' => sanitize_input($_POST['address_line1']),
            'address_line2' => sanitize_input($_POST['address_line2'] ?? ''),
            'city' => sanitize_input($_POST['city']),
            'region' => sanitize_input($_POST['region']),
            'is_default' => isset($_POST['is_default']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Validate required fields
        $required = ['type', 'full_name', 'phone', 'address_line1', 'city', 'region'];
        foreach ($required as $field) {
            if (empty($address_data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        
        // Validate phone
        if (!empty($address_data['phone']) && !validate_phone($address_data['phone'])) {
            $errors[] = 'Please enter a valid Ethiopian phone number (format: +251911234567).';
        }
        
        if (empty($errors)) {
            try {
                $db = new Database();
                
                // Check if address belongs to user
                $db->query("SELECT id FROM user_addresses WHERE id = :id AND user_id = :user_id");
                $db->bind(':id', $address_id);
                $db->bind(':user_id', $_SESSION['user_id']);
                $address_exists = $db->single();
                
                if (!$address_exists) {
                    throw new Exception('Address not found or access denied.');
                }
                
                // If setting as default, remove default from other addresses
                if ($address_data['is_default']) {
                    $db->query("UPDATE user_addresses SET is_default = false WHERE user_id = :user_id AND id != :id");
                    $db->bind(':user_id', $_SESSION['user_id']);
                    $db->bind(':id', $address_id);
                    $db->execute();
                }
                
                // Update address
                $db->update('user_addresses', $address_data, 'id = :id AND user_id = :user_id', [
                    'id' => $address_id,
                    'user_id' => $_SESSION['user_id']
                ]);
                
                $success = 'Address updated successfully!';
            } catch (Exception $e) {
                $errors[] = 'Failed to update address: ' . $e->getMessage();
            }
        }
    }
}

// Handle delete address
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $address_id = intval($_GET['delete']);
    
    try {
        $db = new Database();
        
        // Check if address belongs to user
        $db->query("SELECT id, is_default FROM user_addresses WHERE id = :id AND user_id = :user_id");
        $db->bind(':id', $address_id);
        $db->bind(':user_id', $_SESSION['user_id']);
        $address = $db->single();
        
        if ($address) {
            // Don't allow deletion if it's the only address
            $db->query("SELECT COUNT(*) as count FROM user_addresses WHERE user_id = :user_id");
            $db->bind(':user_id', $_SESSION['user_id']);
            $count_result = $db->single();
            
            if ($count_result['count'] <= 1) {
                $_SESSION['error'] = 'Cannot delete your only address. Please add another address first.';
            } else {
                // Delete the address
                $db->query("DELETE FROM user_addresses WHERE id = :id AND user_id = :user_id");
                $db->bind(':id', $address_id);
                $db->bind(':user_id', $_SESSION['user_id']);
                $db->execute();
                
                // If deleted address was default, set another as default
                if ($address['is_default']) {
                    $db->query("UPDATE user_addresses SET is_default = true WHERE user_id = :user_id LIMIT 1");
                    $db->bind(':user_id', $_SESSION['user_id']);
                    $db->execute();
                }
                
                $_SESSION['success'] = 'Address deleted successfully!';
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to delete address: ' . $e->getMessage();
    }
    
    redirect('addresses.php');
}

// Set default address
if (isset($_GET['set_default']) && is_numeric($_GET['set_default'])) {
    $address_id = intval($_GET['set_default']);
    
    try {
        $db = new Database();
        
        // Check if address belongs to user
        $db->query("SELECT id FROM user_addresses WHERE id = :id AND user_id = :user_id");
        $db->bind(':id', $address_id);
        $db->bind(':user_id', $_SESSION['user_id']);
        $address_exists = $db->single();
        
        if ($address_exists) {
            // Remove default from all addresses
            $db->query("UPDATE user_addresses SET is_default = false WHERE user_id = :user_id");
            $db->bind(':user_id', $_SESSION['user_id']);
            $db->execute();
            
            // Set new default
            $db->query("UPDATE user_addresses SET is_default = true WHERE id = :id AND user_id = :user_id");
            $db->bind(':id', $address_id);
            $db->bind(':user_id', $_SESSION['user_id']);
            $db->execute();
            
            $_SESSION['success'] = 'Default address updated successfully!';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to set default address: ' . $e->getMessage();
    }
    
    redirect('addresses.php');
}

// Get user addresses
$db = new Database();
$db->query("SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC");
$db->bind(':user_id', $_SESSION['user_id']);
$addresses = $db->resultSet();

// Get edit address if specified
$edit_address = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $address_id = intval($_GET['edit']);
    $db->query("SELECT * FROM user_addresses WHERE id = :id AND user_id = :user_id");
    $db->bind(':id', $address_id);
    $db->bind(':user_id', $_SESSION['user_id']);
    $edit_address = $db->single();
}

$cart_count = get_cart_count();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-custom.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <?php include '../public/includes/navbar.php'; ?>

    <!-- Dashboard Layout -->
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <?php include 'includes/sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">Address Book</h2>
                        <p class="text-muted mb-0">Manage your shipping and billing addresses</p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                        <i class="fas fa-plus me-2"></i>Add New Address
                    </button>
                </div>
                
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $_SESSION['success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <?php unset($_SESSION['success']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $_SESSION['error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <?php unset($_SESSION['error']); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Addresses List -->
                <div class="row">
                    <?php if (empty($addresses)): ?>
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-map-marker-alt fa-4x text-muted mb-3"></i>
                                <h4>No Addresses Found</h4>
                                <p class="text-muted mb-4">You haven't added any addresses yet. Add your first address to get started.</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                    <i class="fas fa-plus me-2"></i>Add Your First Address
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($addresses as $address): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow-sm <?php echo $address['is_default'] ? 'border-primary' : ''; ?>">
                            <div class="card-header d-flex justify-content-between align-items-center <?php echo $address['is_default'] ? 'bg-primary text-white' : 'bg-light'; ?>">
                                <div>
                                    <h6 class="mb-0">
                                        <?php if ($address['is_default']): ?>
                                        <i class="fas fa-star text-warning me-2"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars(ucfirst($address['type'])); ?> Address
                                    </h6>
                                </div>
                                <div>
                                    <?php if ($address['is_default']): ?>
                                    <span class="badge bg-warning">Default</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <h6 class="mb-2"><?php echo htmlspecialchars($address['full_name']); ?></h6>
                                <p class="mb-2">
                                    <?php echo htmlspecialchars($address['address_line1']); ?><br>
                                    <?php if (!empty($address['address_line2'])): ?>
                                    <?php echo htmlspecialchars($address['address_line2']); ?><br>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['region']); ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($address['phone']); ?>
                                </p>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="addresses.php?edit=<?php echo $address['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary me-2"
                                           data-bs-toggle="modal" 
                                           data-bs-target="#editAddressModal"
                                           onclick="loadEditAddress(<?php echo $address['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="addresses.php?delete=<?php echo $address['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Delete this address?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                    <?php if (!$address['is_default']): ?>
                                    <a href="addresses.php?set_default=<?php echo $address['id']; ?>" 
                                       class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-star me-1"></i>Set as Default
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Ethiopian Regions Info -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Shipping Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <h6>Addis Ababa</h6>
                                <p class="small text-muted mb-0">Delivery: 1-2 business days</p>
                                <p class="small text-muted mb-0">Shipping: ETB 150</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <h6>Major Cities</h6>
                                <p class="small text-muted mb-0">Delivery: 3-5 business days</p>
                                <p class="small text-muted mb-0">Shipping: ETB 250</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <h6>Other Regions</h6>
                                <p class="small text-muted mb-0">Delivery: 5-10 business days</p>
                                <p class="small text-muted mb-0">Shipping: ETB 350</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Address</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="add-address-form">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">Address Type *</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="home">Home</option>
                                    <option value="work">Work</option>
                                    <option value="billing">Billing</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user_data['phone']); ?>"
                                       pattern="\+251[0-9]{9}" placeholder="+251911234567" required>
                                <small class="text-muted">Ethiopian format: +251911234567</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="region" class="form-label">Region *</label>
                                <select class="form-select" id="region" name="region" required>
                                    <option value="">Select Region</option>
                                    <?php foreach (get_ethiopian_regions() as $region): ?>
                                    <option value="<?php echo $region; ?>"><?php echo $region; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City/Town *</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="address_line1" class="form-label">Address Line 1 *</label>
                                <input type="text" class="form-control" id="address_line1" name="address_line1" 
                                       placeholder="Street address, P.O. Box, etc." required>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="address_line2" class="form-label">Address Line 2 (Optional)</label>
                                <input type="text" class="form-control" id="address_line2" name="address_line2" 
                                       placeholder="Apartment, suite, unit, building, floor, etc.">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                                    <label class="form-check-label" for="is_default">
                                        Set as default address
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_address" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Address
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Address Modal -->
    <div class="modal fade" id="editAddressModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Address</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="edit-address-form">
                    <input type="hidden" id="edit_address_id" name="address_id">
                    <div class="modal-body">
                        <div id="edit-address-loading" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Loading address details...</p>
                        </div>
                        <div id="edit-address-content" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_type" class="form-label">Address Type *</label>
                                    <select class="form-select" id="edit_type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="home">Home</option>
                                        <option value="work">Work</option>
                                        <option value="billing">Billing</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="edit_full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="edit_phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="edit_phone" name="phone" 
                                           pattern="\+251[0-9]{9}" placeholder="+251911234567" required>
                                    <small class="text-muted">Ethiopian format: +251911234567</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="edit_region" class="form-label">Region *</label>
                                    <select class="form-select" id="edit_region" name="region" required>
                                        <option value="">Select Region</option>
                                        <?php foreach (get_ethiopian_regions() as $region): ?>
                                        <option value="<?php echo $region; ?>"><?php echo $region; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="edit_city" class="form-label">City/Town *</label>
                                    <input type="text" class="form-control" id="edit_city" name="city" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="edit_address_line1" class="form-label">Address Line 1 *</label>
                                    <input type="text" class="form-control" id="edit_address_line1" name="address_line1" required>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="edit_address_line2" class="form-label">Address Line 2 (Optional)</label>
                                    <input type="text" class="form-control" id="edit_address_line2" name="address_line2">
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit_is_default" name="is_default">
                                        <label class="form-check-label" for="edit_is_default">
                                            Set as default address
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_address" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Update Address
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../public/includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-fill city based on region
        document.getElementById('region').addEventListener('change', function() {
            const region = this.value;
            const cityInput = document.getElementById('city');
            
            const regionCities = {
                'Addis Ababa': 'Addis Ababa',
                'Oromia': 'Adama',
                'Amhara': 'Bahir Dar',
                'Tigray': 'Mekelle',
                'Southern Nations': 'Hawassa',
                'Somali': 'Jijiga',
                'Afar': 'Semera',
                'Benishangul-Gumuz': 'Assosa',
                'Gambela': 'Gambela',
                'Harari': 'Harar',
                'Sidama': 'Hawassa',
                'Dire Dawa': 'Dire Dawa'
            };
            
            if (regionCities[region]) {
                cityInput.value = regionCities[region];
            } else {
                cityInput.value = '';
            }
        });
        
        document.getElementById('edit_region').addEventListener('change', function() {
            const region = this.value;
            const cityInput = document.getElementById('edit_city');
            
            const regionCities = {
                'Addis Ababa': 'Addis Ababa',
                'Oromia': 'Adama',
                'Amhara': 'Bahir Dar',
                'Tigray': 'Mekelle',
                'Southern Nations': 'Hawassa',
                'Somali': 'Jijiga',
                'Afar': 'Semera',
                'Benishangul-Gumuz': 'Assosa',
                'Gambela': 'Gambela',
                'Harari': 'Harar',
                'Sidama': 'Hawassa',
                'Dire Dawa': 'Dire Dawa'
            };
            
            if (regionCities[region]) {
                cityInput.value = regionCities[region];
            }
        });
        
        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function() {
            formatPhoneNumber(this);
        });
        
        document.getElementById('edit_phone').addEventListener('input', function() {
            formatPhoneNumber(this);
        });
        
        function formatPhoneNumber(input) {
            let value = input.value.replace(/\D/g, '');
            
            if (value.startsWith('0')) {
                value = '+251' + value.substring(1);
            } else if (value.startsWith('251')) {
                value = '+' + value;
            } else if (value.startsWith('9') && value.length >= 9) {
                value = '+251' + value;
            }
            
            if (value.length > 13) {
                value = value.substring(0, 13);
            }
            
            input.value = value;
        }
        
        // Load edit address data
        function loadEditAddress(addressId) {
            const loadingDiv = document.getElementById('edit-address-loading');
            const contentDiv = document.getElementById('edit-address-content');
            const form = document.getElementById('edit-address-form');
            
            // Show loading, hide content
            loadingDiv.style.display = 'block';
            contentDiv.style.display = 'none';
            
            // Fetch address data
            fetch(`../api/addresses.php?action=get&id=${addressId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate form fields
                        document.getElementById('edit_address_id').value = data.address.id;
                        document.getElementById('edit_type').value = data.address.type;
                        document.getElementById('edit_full_name').value = data.address.full_name;
                        document.getElementById('edit_phone').value = data.address.phone;
                        document.getElementById('edit_region').value = data.address.region;
                        document.getElementById('edit_city').value = data.address.city;
                        document.getElementById('edit_address_line1').value = data.address.address_line1;
                        document.getElementById('edit_address_line2').value = data.address.address_line2 || '';
                        document.getElementById('edit_is_default').checked = data.address.is_default;
                        
                        // Hide loading, show content
                        loadingDiv.style.display = 'none';
                        contentDiv.style.display = 'block';
                    } else {
                        alert('Failed to load address details.');
                        $('#editAddressModal').modal('hide');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading address details.');
                    $('#editAddressModal').modal('hide');
                });
        }
        
        // Reset edit modal when hidden
        $('#editAddressModal').on('hidden.bs.modal', function() {
            const loadingDiv = document.getElementById('edit-address-loading');
            const contentDiv = document.getElementById('edit-address-content');
            
            loadingDiv.style.display = 'block';
            contentDiv.style.display = 'none';
        });
        
        // Form validation
        document.getElementById('add-address-form').addEventListener('submit', function(e) {
            const phone = document.getElementById('phone').value;
            const phoneRegex = /^\+251[0-9]{9}$/;
            
            if (!phoneRegex.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid Ethiopian phone number (format: +251911234567).');
                return;
            }
        });
        
        document.getElementById('edit-address-form').addEventListener('submit', function(e) {
            const phone = document.getElementById('edit_phone').value;
            const phoneRegex = /^\+251[0-9]{9}$/;
            
            if (!phoneRegex.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid Ethiopian phone number (format: +251911234567).');
                return;
            }
        });
    </script>
</body>
</html>