<?php
require_once '../config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Size Guide - ' . SITE_NAME;

// Initialize Database
if (!class_exists('Database')) {
    require_once '../classes/Database.php';
}

$db = new Database();

// Get current user if logged in
$current_user = null;
if (isset($_SESSION['user_id'])) {
    require_once '../classes/User.php';
    $user = new User();
    $current_user = $user->getUserById($_SESSION['user_id']);
}

// Get cart count
if (function_exists('get_cart_count')) {
    $cart_count = get_cart_count();
} else {
    $cart_count = 0;
}

// Get wishlist count
$wishlist_count = 0;
if (isset($_SESSION['wishlist']) && is_array($_SESSION['wishlist'])) {
    $wishlist_count = count($_SESSION['wishlist']);
}

// Helper functions
if (!function_exists('is_logged_in')) {
    function is_logged_in()
    {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('format_measurement')) {
    function format_measurement($cm)
    {
        return number_format($cm, 1) . ' cm';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-custom.css" rel="stylesheet">

    <style>
        .size-guide-section {
            padding: 100px 0 50px;
        }

        .size-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .size-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.1);
        }

        .size-card.active {
            border-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.05);
        }

        .size-table {
            font-size: 0.9rem;
        }

        .size-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .size-table td {
            vertical-align: middle;
        }

        .size-badge {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
            background-color: #f8f9fa;
            border: 2px solid #dee2e6;
            transition: all 0.2s;
        }

        .size-badge:hover,
        .size-badge.active {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
            cursor: pointer;
        }

        .foot-diagram {
            max-width: 300px;
            margin: 0 auto;
        }

        .measurement-step {
            padding: 20px;
            border-left: 4px solid #0d6efd;
            background-color: #f8f9fa;
            border-radius: 0 8px 8px 0;
            margin-bottom: 20px;
        }

        .conversion-table {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 30px;
        }

        .tips-card {
            border-left: 4px solid #20c997;
        }

        .warning-card {
            border-left: 4px solid #fd7e14;
        }

        .brand-logo {
            max-height: 50px;
            max-width: 100px;
            filter: grayscale(100%);
            opacity: 0.7;
            transition: all 0.3s;
        }

        .brand-logo:hover {
            filter: grayscale(0);
            opacity: 1;
        }

        .nav-tabs .nav-link.active {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }

        @media (max-width: 768px) {
            .size-guide-section {
                padding: 80px 0 30px;
            }

            .size-table {
                font-size: 0.8rem;
            }

            .conversion-table {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shoe-prints"></i> <?php echo htmlspecialchars(SITE_NAME); ?>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shop.php">Shop</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="size-guide.php">Size Guide</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center">
                    <!-- Search Form -->
                    <form class="d-flex me-3" action="shop.php" method="GET">
                        <input class="form-control me-2" type="search" name="search" placeholder="Search sneakers..."
                            aria-label="Search" style="min-width: 200px;">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>

                    <!-- Wishlist -->
                    <div class="me-3">
                        <a href="wishlist.php" class="text-light position-relative" style="text-decoration: none;">
                            <i class="fas fa-heart fa-lg"></i>
                            <?php if ($wishlist_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $wishlist_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <!-- Cart -->
                    <div class="me-3">
                        <a href="cart.php" class="text-light position-relative" style="text-decoration: none;">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $cart_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <!-- User Dropdown -->
                    <?php if (is_logged_in() && $current_user): ?>
                        <div class="dropdown">
                            <a href="#" class="text-light dropdown-toggle d-flex align-items-center"
                                data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration: none;">
                                <i class="fas fa-user fa-lg me-1"></i>
                                <span class="d-none d-lg-inline"><?php echo htmlspecialchars($current_user['first_name']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <h6 class="dropdown-header">Hello, <?php echo htmlspecialchars($current_user['first_name']); ?>!</h6>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item" href="../buyer/">
                                        <i class="fas fa-tachometer-alt me-2"></i>My Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="../buyer/orders.php">
                                        <i class="fas fa-shopping-bag me-2"></i>My Orders
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="../buyer/profile.php">
                                        <i class="fas fa-user me-2"></i>My Profile
                                    </a>
                                </li>
                                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'vendor'): ?>
                                    <li>
                                        <a class="dropdown-item" href="../vendor/">
                                            <i class="fas fa-store me-2"></i>Vendor Dashboard
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                                    <li>
                                        <a class="dropdown-item" href="../admin/">
                                            <i class="fas fa-cogs me-2"></i>Admin Dashboard
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div>
                            <a href="login.php" class="btn btn-outline-light me-2">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                            <a href="register.php" class="btn btn-primary">
                                <i class="fas fa-user-plus me-1"></i> Register
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Size Guide Section -->
    <section class="size-guide-section">
        <div class="container">
            <!-- Page Header -->
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-3">Sneaker Size Guide</h1>
                    <p class="lead text-muted">
                        Find your perfect fit with our comprehensive size guide. Learn how to measure your feet and convert between different sizing systems.
                    </p>
                </div>
            </div>

            <!-- Measurement Guide -->
            <div class="row mb-5">
                <div class="col-lg-6">
                    <div class="card size-card h-100">
                        <div class="card-body">
                            <h3 class="card-title mb-4">
                                <i class="fas fa-ruler-combined text-primary me-2"></i>How to Measure Your Feet
                            </h3>

                            <div class="measurement-step">
                                <h5><i class="fas fa-shoe-prints me-2"></i>Step 1: Prepare</h5>
                                <p class="mb-0">Wear the same type of socks you'll wear with your sneakers. Stand on a hard surface with your weight evenly distributed.</p>
                            </div>

                            <div class="measurement-step">
                                <h5><i class="fas fa-pencil-ruler me-2"></i>Step 2: Trace</h5>
                                <p class="mb-0">Place your foot on a piece of paper and trace around it. Keep the pen/pencil perpendicular to the paper.</p>
                            </div>

                            <div class="measurement-step">
                                <h5><i class="fas fa-ruler-vertical me-2"></i>Step 3: Measure</h5>
                                <p class="mb-0">Measure the length from the heel to the longest toe. Measure the width at the widest part of your foot.</p>
                            </div>

                            <div class="measurement-step">
                                <h5><i class="fas fa-calculator me-2"></i>Step 4: Calculate</h5>
                                <p class="mb-0">Use the longest measurement of both feet. Add 1-1.5 cm (0.4-0.6 inches) for comfort.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card size-card h-100">
                        <div class="card-body text-center">
                            <h3 class="card-title mb-4">
                                <i class="fas fa-shoe-prints text-primary me-2"></i>Foot Measurement Diagram
                            </h3>
                            <div class="foot-diagram mb-4">
                                <img src="../assets/images/foot-measurement.png" alt="Foot Measurement Diagram"
                                    class="img-fluid rounded shadow"
                                    onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'200\' viewBox=\'0 0 300 200\'%3E%3Crect width=\'100%25\' height=\'100%25\' fill=\'%23f8f9fa\'/%3E%3Cpath d=\'M50,150 C80,50 150,30 250,150\' stroke=\'%230d6efd\' stroke-width=\'2\' fill=\'none\'/%3E%3Ctext x=\'150\' y=\'100\' text-anchor=\'middle\' fill=\'%236c757d\' font-size=\'14\'%3EFoot Diagram%3C/text%3E%3C/svg%3E'">
                            </div>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded">
                                        <h5 class="text-primary mb-1">Length</h5>
                                        <p class="mb-0">Heel to longest toe</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded">
                                        <h5 class="text-primary mb-1">Width</h5>
                                        <p class="mb-0">Widest part of foot</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Size Conversion -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="conversion-table">
                        <h2 class="text-center mb-4">
                            <i class="fas fa-exchange-alt me-2"></i>International Size Conversion
                        </h2>

                        <ul class="nav nav-tabs justify-content-center mb-4" id="sizeTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="men-tab" data-bs-toggle="tab" data-bs-target="#men" type="button">
                                    <i class="fas fa-male me-1"></i> Men's Sizes
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="women-tab" data-bs-toggle="tab" data-bs-target="#women" type="button">
                                    <i class="fas fa-female me-1"></i> Women's Sizes
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="kids-tab" data-bs-toggle="tab" data-bs-target="#kids" type="button">
                                    <i class="fas fa-child me-1"></i> Kids' Sizes
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="sizeTabContent">
                            <!-- Men's Sizes -->
                            <div class="tab-pane fade show active" id="men" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover bg-white size-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>US</th>
                                                <th>UK</th>
                                                <th>EU</th>
                                                <th>Foot Length (cm)</th>
                                                <th>Foot Length (inches)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $men_sizes = [
                                                [6, 5.5, 38.5, 24.1, 9.5],
                                                [7, 6, 40, 24.8, 9.75],
                                                [8, 7, 41, 25.7, 10.1],
                                                [9, 8, 42, 26.4, 10.4],
                                                [10, 9, 43, 27.3, 10.75],
                                                [11, 10, 44.5, 28.0, 11.0],
                                                [12, 11, 46, 29.0, 11.4],
                                                [13, 12, 47, 29.7, 11.7],
                                            ];

                                            foreach ($men_sizes as $size):
                                            ?>
                                                <tr>
                                                    <td><strong><?php echo $size[0]; ?></strong></td>
                                                    <td><?php echo $size[1]; ?></td>
                                                    <td><?php echo $size[2]; ?></td>
                                                    <td><?php echo format_measurement($size[3]); ?></td>
                                                    <td><?php echo $size[4]; ?> in</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Women's Sizes -->
                            <div class="tab-pane fade" id="women" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover bg-white size-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>US</th>
                                                <th>UK</th>
                                                <th>EU</th>
                                                <th>Foot Length (cm)</th>
                                                <th>Foot Length (inches)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $women_sizes = [
                                                [5, 2.5, 35, 22.1, 8.7],
                                                [6, 3.5, 36, 22.9, 9.0],
                                                [7, 4.5, 37.5, 23.5, 9.25],
                                                [8, 5.5, 38.5, 24.1, 9.5],
                                                [9, 6.5, 40, 24.8, 9.75],
                                                [10, 7.5, 41, 25.4, 10.0],
                                                [11, 8.5, 42, 26.0, 10.25],
                                                [12, 9.5, 43, 26.7, 10.5],
                                            ];

                                            foreach ($women_sizes as $size):
                                            ?>
                                                <tr>
                                                    <td><strong><?php echo $size[0]; ?></strong></td>
                                                    <td><?php echo $size[1]; ?></td>
                                                    <td><?php echo $size[2]; ?></td>
                                                    <td><?php echo format_measurement($size[3]); ?></td>
                                                    <td><?php echo $size[4]; ?> in</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Kids' Sizes -->
                            <div class="tab-pane fade" id="kids" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover bg-white size-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>US</th>
                                                <th>UK</th>
                                                <th>EU</th>
                                                <th>Foot Length (cm)</th>
                                                <th>Age Range</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $kids_sizes = [
                                                [1, 13, 32, 12.7, '6-9 months'],
                                                [3, 2, 34, 14.0, '9-12 months'],
                                                [5, 3, 35, 15.2, '1-2 years'],
                                                [7, 5, 37, 16.5, '2-3 years'],
                                                [9, 6, 38, 17.8, '3-4 years'],
                                                [11, 8, 39, 19.1, '4-5 years'],
                                                [13, 12, 40, 20.3, '6-7 years'],
                                                [2, 1, 33, 13.3, '9-12 months'],
                                            ];

                                            foreach ($kids_sizes as $size):
                                            ?>
                                                <tr>
                                                    <td><strong><?php echo $size[0]; ?></strong></td>
                                                    <td><?php echo $size[1]; ?></td>
                                                    <td><?php echo $size[2]; ?></td>
                                                    <td><?php echo format_measurement($size[3]); ?></td>
                                                    <td><?php echo $size[4]; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Brand Specific Sizes -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card size-card">
                        <div class="card-body">
                            <h3 class="card-title mb-4">
                                <i class="fas fa-star text-warning me-2"></i>Brand-Specific Sizing Notes
                            </h3>

                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <div class="text-center p-3 h-100">
                                        <img src="../assets/images/brands/nike.png" alt="Nike" class="brand-logo mb-3"
                                            onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\" http://www.w3.org/2000/svg\" width=\"100\" height=\"50\" viewBox=\"0 0 100 50\"%3E%3Crect width=\"100%25\" height=\"100%25\" fill=\"%23f8f9fa\"/%3E%3Ctext x=\"50\" y=\"25\" text-anchor=\"middle\" dy=\".3em\" fill=\"%236c757d\" font-weight=\"bold\"%3ENIKE%3C/text%3E%3C/svg%3E'">
                                        <h5>Nike</h5>
                                        <p class="small text-muted mb-2">Generally true to size. Running shoes may run small.</p>
                                        <div class="badge bg-primary">Athletic Fit</div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-4">
                                    <div class="text-center p-3 h-100">
                                        <img src="../assets/images/brands/adidas.png" alt="Adidas" class="brand-logo mb-3"
                                            onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\" http://www.w3.org/2000/svg\" width=\"100\" height=\"50\" viewBox=\"0 0 100 50\"%3E%3Crect width=\"100%25\" height=\"100%25\" fill=\"%23f8f9fa\"/%3E%3Ctext x=\"50\" y=\"25\" text-anchor=\"middle\" dy=\".3em\" fill=\"%236c757d\" font-weight=\"bold\"%3EAdidas%3C/text%3E%3C/svg%3E'">
                                        <h5>Adidas</h5>
                                        <p class="small text-muted mb-2">Runs slightly narrow. Consider half size up for wide feet.</p>
                                        <div class="badge bg-info">Slim Fit</div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-4">
                                    <div class="text-center p-3 h-100">
                                        <img src="../assets/images/brands/jordan.png" alt="Jordan" class="brand-logo mb-3"
                                            onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\" http://www.w3.org/2000/svg\" width=\"100\" height=\"50\" viewBox=\"0 0 100 50\"%3E%3Crect width=\"100%25\" height=\"100%25\" fill=\"%23f8f9fa\"/%3E%3Ctext x=\"50\" y=\"25\" text-anchor=\"middle\" dy=\".3em\" fill=\"%236c757d\" font-weight=\"bold\"%3EJordan%3C/text%3E%3C/svg%3E'">
                                        <h5>Jordan</h5>
                                        <p class="small text-muted mb-2">True to size for most models. Retro models fit snug.</p>
                                        <div class="badge bg-warning text-dark">Standard Fit</div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info mt-3">
                                <h6><i class="fas fa-info-circle me-2"></i>Note:</h6>
                                <p class="mb-0">These are general guidelines. Always check individual product pages for specific sizing information from the vendor.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips & Warnings -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card tips-card h-100">
                        <div class="card-body">
                            <h4 class="card-title mb-3">
                                <i class="fas fa-lightbulb text-success me-2"></i>Pro Tips for Perfect Fit
                            </h4>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Measure both feet and use the larger measurement</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Measure at the end of the day when feet are largest</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Consider sock thickness when choosing size</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Leave about a thumb's width of space at the toe</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Check if the brand offers wide/narrow options</li>
                                <li><i class="fas fa-check text-success me-2"></i>Read customer reviews for specific model fit</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card warning-card h-100">
                        <div class="card-body">
                            <h4 class="card-title mb-3">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>Common Fit Issues
                            </h4>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-times text-danger me-2"></i><strong>Too Tight:</strong> Can cause blisters and discomfort</li>
                                <li class="mb-2"><i class="fas fa-times text-danger me-2"></i><strong>Too Loose:</strong> May cause tripping or foot sliding</li>
                                <li class="mb-2"><i class="fas fa-times text-danger me-2"></i><strong>Narrow Fit:</strong> Look for wide-width options</li>
                                <li class="mb-2"><i class="fas fa-times text-danger me-2"></i><strong>Arch Issues:</strong> Consider insoles for better support</li>
                                <li class="mb-2"><i class="fas fa-times text-danger me-2"></i><strong>Breaking In:</strong> Some shoes require break-in period</li>
                                <li><i class="fas fa-times text-danger me-2"></i><strong>Seasonal Fit:</strong> Feet may swell in hot weather</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Help Section -->
            <div class="row mt-5">
                <div class="col-12 text-center">
                    <div class="card bg-light">
                        <div class="card-body py-5">
                            <h3 class="mb-3">Still Unsure About Your Size?</h3>
                            <p class="lead text-muted mb-4">Our customer service team is here to help you find the perfect fit.</p>
                            <div class="d-flex flex-wrap justify-content-center gap-3">
                                <a href="contact.php" class="btn btn-primary btn-lg px-4">
                                    <i class="fas fa-headset me-2"></i>Contact Support
                                </a>
                                <a href="shop.php" class="btn btn-outline-primary btn-lg px-4">
                                    <i class="fas fa-shopping-bag me-2"></i>Shop Now
                                </a>
                                <a href="faq.php" class="btn btn-outline-secondary btn-lg px-4">
                                    <i class="fas fa-question-circle me-2"></i>Visit FAQ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="fas fa-shoe-prints"></i> <?php echo htmlspecialchars(SITE_NAME); ?></h5>
                    <p class="mt-3">Ethiopia's premier destination for authentic sneakers. Connecting buyers with trusted vendors nationwide.</p>
                    <div class="social-links mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-telegram fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="text-white-50 text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="shop.php" class="text-white-50 text-decoration-none">Shop</a></li>
                        <li class="mb-2"><a href="about.php" class="text-white-50 text-decoration-none">About Us</a></li>
                        <li class="mb-2"><a href="contact.php" class="text-white-50 text-decoration-none">Contact</a></li>
                        <li class="mb-2"><a href="size-guide.php" class="text-white-50 text-decoration-none">Size Guide</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Customer Service</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="terms.php" class="text-white-50 text-decoration-none">Terms & Conditions</a></li>
                        <li class="mb-2"><a href="privacy.php" class="text-white-50 text-decoration-none">Privacy Policy</a></li>
                        <li class="mb-2"><a href="shipping.php" class="text-white-50 text-decoration-none">Shipping Information</a></li>
                        <li class="mb-2"><a href="returns.php" class="text-white-50 text-decoration-none">Return Policy</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Contact Us</h5>
                    <p class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Addis Ababa, Ethiopia</p>
                    <p class="mb-2"><i class="fas fa-phone me-2"></i> +251 911 123 456</p>
                    <p class="mb-2"><i class="fas fa-envelope me-2"></i> info@<?php echo strtolower(str_replace(' ', '', SITE_NAME)); ?>.et</p>
                </div>
            </div>
            <hr class="bg-light">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-2">Accepted Payment Methods:</p>
                    <i class="fab fa-cc-visa fa-2x me-2"></i>
                    <i class="fab fa-cc-mastercard fa-2x me-2"></i>
                    <i class="fas fa-university fa-2x me-2"></i>
                    <i class="fas fa-mobile-alt fa-2x"></i>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Size Guide JavaScript -->
    <script>
        // Initialize Bootstrap tabs
        var tabEl = document.querySelector('button[data-bs-toggle="tab"]');
        if (tabEl) {
            var tab = new bootstrap.Tab(tabEl);
        }

        // Size selector functionality
        document.querySelectorAll('.size-badge').forEach(badge => {
            badge.addEventListener('click', function() {
                // Remove active class from all badges
                document.querySelectorAll('.size-badge').forEach(b => {
                    b.classList.remove('active');
                });

                // Add active class to clicked badge
                this.classList.add('active');

                // Show selected size
                const selectedSize = this.textContent;
                showAlert('info', 'Selected size: ' + selectedSize + '. Use this when ordering.');
            });
        });

        // Measurement calculator
        document.getElementById('calculate-size').addEventListener('click', function() {
            const footLength = parseFloat(document.getElementById('foot-length').value);
            const unit = document.getElementById('measurement-unit').value;

            if (!footLength || footLength <= 0) {
                showAlert('danger', 'Please enter a valid foot length.');
                return;
            }

            // Convert to cm if needed
            let lengthInCm = unit === 'inches' ? footLength * 2.54 : footLength;

            // Calculate approximate size (simplified)
            let usSize;
            if (lengthInCm >= 22 && lengthInCm <= 28) {
                // Women's sizes
                usSize = Math.round((lengthInCm - 21) / 0.4) + 4;
            } else if (lengthInCm >= 24 && lengthInCm <= 30) {
                // Men's sizes
                usSize = Math.round((lengthInCm - 23) / 0.4) + 6;
            } else {
                usSize = 'N/A';
            }

            // Show result
            const resultDiv = document.getElementById('size-result');
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <h5><i class="fas fa-shoe-prints me-2"></i>Recommended Size</h5>
                    <p class="mb-1">Foot Length: ${lengthInCm.toFixed(1)} cm</p>
                    <p class="mb-0">Approximate US Size: <strong>${usSize}</strong></p>
                    <small class="text-muted">This is an estimate. Always refer to brand-specific sizing.</small>
                </div>
            `;
        });

        // Print size guide
        document.getElementById('print-guide').addEventListener('click', function() {
            window.print();
        });

        // Helper function to show alerts
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i> 
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>

</html>