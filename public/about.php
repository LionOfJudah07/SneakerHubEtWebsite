<?php
// public/about.php

// Include config first
require_once '../includes/config.php';

// Include functions from root directory
require_once '../functions.php';

$page_title = 'About Us - ' . SITE_NAME;
$cart_count = get_cart_count();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-custom.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #16a34a;
            --primary-dark: #0f7a35;
            --primary-light: #4ade80;
            --secondary-color: #6b7280;
            --success-color: #22c55e;
            --info-color: #14b8a6;
            --warning-color: #facc15;
            --danger-color: #ef4444;
            --dark-color: #0b0f0e;
        }
        
        .hero-section {
            background: linear-gradient(135deg, rgba(22, 163, 74, 0.08) 0%, rgba(249, 250, 251, 1) 100%);
            border-radius: 20px;
            padding: 4rem 2rem;
        }
        
        .stat-card {
            transition: transform 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1) !important;
        }
        
        .team-card {
            transition: all 0.3s ease;
            border-radius: 15px;
            overflow: hidden;
            border: none;
        }
        
        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        }
        
        .team-card img {
            height: 250px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .team-card:hover img {
            transform: scale(1.05);
        }
        
        .value-card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            height: 100%;
            border-left: 4px solid var(--primary-color);
        }
        
        .value-card:hover {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white !important;
        }
        
        .value-card:hover .card-title,
        .value-card:hover .card-text {
            color: white !important;
        }
        
        .value-card:hover i {
            color: white !important;
        }
        
        .icon-box {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            color: white;
            box-shadow: 0 8px 20px rgba(22, 163, 74, 0.3);
        }
        
        .icon-box i {
            font-size: 2.5rem;
        }
        
        .section-title {
            position: relative;
            padding-bottom: 1rem;
            margin-bottom: 3rem;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            border-radius: 2px;
        }
        
        .stats-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border-radius: 20px;
            overflow: hidden;
        }
        
        .stat-number {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.5rem;
            color: white;
        }
        
        .cta-section {
            background: linear-gradient(rgba(11, 15, 14, 0.9), rgba(11, 15, 14, 0.9)), url('https://images.unsplash.com/photo-1552346154-21d32810aba3?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            border-radius: 20px;
            overflow: hidden;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 2rem 1rem;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .icon-box {
                width: 80px;
                height: 80px;
            }
            
            .icon-box i {
                font-size: 2rem;
            }
        }
        
        .breadcrumb {
            background-color: transparent !important;
            padding: 0 !important;
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: var(--secondary-color);
        }
        
        .bg-dark {
            background-color: var(--dark-color) !important;
        }
        
        .text-primary {
            color: var(--primary-color) !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .border-primary {
            border-color: var(--primary-color) !important;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Breadcrumb -->
    <section class="breadcrumb-section py-3">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home me-1"></i>Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">About Us</li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- About Content -->
    <main class="py-5">
        <!-- Hero Section -->
        <section class="container mb-5">
            <div class="row align-items-center hero-section g-4">
                <div class="col-lg-6">
                    <span class="badge bg-primary mb-3">EST. 2024</span>
                    <h1 class="display-4 fw-bold mb-4">About <span class="text-primary">Sneaker</span><span class="text-dark">Hub</span> Ethiopia</h1>
                    <p class="lead mb-4">
                        We are Ethiopia's premier destination for authentic sneakers, 
                        connecting sneaker enthusiasts with trusted vendors nationwide.
                    </p>
                    <p class="mb-4">
                        Founded in 2024, SneakerHub Ethiopia was born from a passion for sneakers 
                        and a vision to create a trusted marketplace where buyers can find 
                        authentic sneakers and sellers can reach a wider audience.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="shop.php" class="btn btn-primary btn-lg px-4">
                            <i class="fas fa-shopping-bag me-2"></i>Shop Now
                        </a>
                        <a href="contact.php" class="btn btn-outline-primary btn-lg px-4">
                            <i class="fas fa-envelope me-2"></i>Contact Us
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="position-relative">
                        <img src="../assets/images/about-hero.jpg" alt="About SneakerHub" 
                             class="img-fluid rounded-3 shadow-lg" 
                             onerror="this.src='https://images.unsplash.com/photo-1552346154-21d32810aba3?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80'">
                        <div class="position-absolute bottom-0 end-0 bg-white p-3 rounded-3 shadow-sm">
                            <div class="text-center">
                                <div class="display-6 fw-bold text-primary">500+</div>
                                <small class="text-muted">Happy Customers</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mission & Vision -->
        <section class="container mb-5">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card stat-card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="icon-box">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <h3 class="card-title text-center mb-3">Our Mission</h3>
                            <p class="card-text text-center text-muted">
                                To provide Ethiopia's sneaker community with a trusted platform 
                                where authenticity is guaranteed, and every transaction is secure 
                                and transparent. We aim to revolutionize sneaker commerce in Ethiopia.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card stat-card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="icon-box">
                                <i class="fas fa-eye"></i>
                            </div>
                            <h3 class="card-title text-center mb-3">Our Vision</h3>
                            <p class="card-text text-center text-muted">
                                To become the leading sneaker marketplace in East Africa, 
                                fostering a vibrant community of sneaker enthusiasts and 
                                setting new standards for e-commerce in the region.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Choose Us -->
        <section class="container mb-5">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold section-title">Why Choose SneakerHub?</h2>
                    <p class="lead text-muted">We're committed to providing the best sneaker shopping experience</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="text-center p-4">
                        <div class="icon-box mb-4">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-3">100% Authentic</h4>
                        <p class="text-muted mb-0">All sneakers are verified for authenticity through our rigorous verification process before listing.</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="text-center p-4">
                        <div class="icon-box mb-4">
                            <i class="fas fa-truck-fast"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-3">Nationwide Delivery</h4>
                        <p class="text-muted mb-0">Fast and reliable delivery across all Ethiopian regions with real-time tracking.</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="text-center p-4">
                        <div class="icon-box mb-4">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-3">Secure Payments</h4>
                        <p class="text-muted mb-0">Multiple secure payment options including TeleBirr, CBE Birr, and bank transfers.</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="text-center p-4">
                        <div class="icon-box mb-4">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-3">24/7 Support</h4>
                        <p class="text-muted mb-0">Dedicated customer support in Amharic, English, and other local languages.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Our Values -->
        <section class="container mb-5">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold section-title">Our Core Values</h2>
                    <p class="lead text-muted">The principles that guide everything we do</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card value-card h-100 shadow-sm p-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-gem fa-2x text-warning me-3"></i>
                                <h4 class="card-title mb-0">Integrity</h4>
                            </div>
                            <p class="card-text">
                                We believe in transparency and honesty in all our dealings. 
                                Every sneaker sold on our platform undergoes rigorous authenticity checks.
                                We maintain the highest ethical standards in our operations.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card value-card h-100 shadow-sm p-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-users fa-2x text-info me-3"></i>
                                <h4 class="card-title mb-0">Community</h4>
                            </div>
                            <p class="card-text">
                                We're building more than a marketplace - we're creating a community 
                                of sneaker enthusiasts who share knowledge, passion, and style.
                                We support local vendors and promote Ethiopian sneaker culture.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card value-card h-100 shadow-sm p-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-rocket fa-2x text-success me-3"></i>
                                <h4 class="card-title mb-0">Innovation</h4>
                            </div>
                            <p class="card-text">
                                We continuously improve our platform to provide the best 
                                shopping experience with the latest technology and features.
                                We embrace change and constantly seek better solutions.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Team Section -->
        <section class="container mb-5">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold section-title">Meet Our Team</h2>
                    <p class="lead text-muted">The passionate people behind SneakerHub Ethiopia</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card team-card h-100 shadow-sm">
                        <div class="card-img-top overflow-hidden">
                            <img src="../assets/images/team/team1.jpg" class="w-100" alt="Samuel T."
                                 onerror="this.src='https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'">
                        </div>
                        <div class="card-body text-center p-4">
                            <h5 class="card-title fw-bold mb-1">Samuel T.</h5>
                            <p class="card-text text-primary small mb-3">Founder & CEO</p>
                            <p class="card-text small text-muted mb-3">Former sneaker collector with 10+ years in e-commerce</p>
                            <div class="social-links">
                                <a href="#" class="btn btn-outline-primary btn-sm rounded-circle me-2">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="#" class="btn btn-outline-primary btn-sm rounded-circle">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card team-card h-100 shadow-sm">
                        <div class="card-img-top overflow-hidden">
                            <img src="../assets/images/team/team2.jpg" class="w-100" alt="Marta G."
                                 onerror="this.src='https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'">
                        </div>
                        <div class="card-body text-center p-4">
                            <h5 class="card-title fw-bold mb-1">Marta G.</h5>
                            <p class="card-text text-primary small mb-3">Operations Manager</p>
                            <p class="card-text small text-muted mb-3">Expert in logistics and customer experience</p>
                            <div class="social-links">
                                <a href="#" class="btn btn-outline-primary btn-sm rounded-circle me-2">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="#" class="btn btn-outline-primary btn-sm rounded-circle">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card team-card h-100 shadow-sm">
                        <div class="card-img-top overflow-hidden">
                            <img src="../assets/images/team/team3.jpg" class="w-100" alt="Yonas K."
                                 onerror="this.src='https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'">
                        </div>
                        <div class="card-body text-center p-4">
                            <h5 class="card-title fw-bold mb-1">Yonas K.</h5>
                            <p class="card-text text-primary small mb-3">Tech Lead</p>
                            <p class="card-text small text-muted mb-3">Full-stack developer with passion for scalable solutions</p>
                            <div class="social-links">
                                <a href="#" class="btn btn-outline-primary btn-sm rounded-circle me-2">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="#" class="btn btn-outline-primary btn-sm rounded-circle">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card team-card h-100 shadow-sm">
                        <div class="card-img-top overflow-hidden">
                            <img src="../assets/images/team/team4.jpg" class="w-100" alt="Selam A."
                                 onerror="this.src='https://images.unsplash.com/photo-1544005313-94ddf0286df2?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'">
                        </div>
                        <div class="card-body text-center p-4">
                            <h5 class="card-title fw-bold mb-1">Selam A.</h5>
                            <p class="card-text text-primary small mb-3">Customer Support</p>
                            <p class="card-text small text-muted mb-3">Dedicated to ensuring customer satisfaction</p>
                            <div class="social-links">
                                <a href="#" class="btn btn-outline-primary btn-sm rounded-circle me-2">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="#" class="btn btn-outline-primary btn-sm rounded-circle">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats -->
        <section class="container mb-5">
            <div class="stats-section text-white py-5">
                <div class="container">
                    <div class="row text-center">
                        <div class="col-md-3 col-6 mb-4">
                            <div class="stat-number">500+</div>
                            <p class="mb-0 opacity-75">Happy Customers</p>
                        </div>
                        <div class="col-md-3 col-6 mb-4">
                            <div class="stat-number">200+</div>
                            <p class="mb-0 opacity-75">Verified Vendors</p>
                        </div>
                        <div class="col-md-3 col-6 mb-4">
                            <div class="stat-number">1K+</div>
                            <p class="mb-0 opacity-75">Products Listed</p>
                        </div>
                        <div class="col-md-3 col-6 mb-4">
                            <div class="stat-number">98%</div>
                            <p class="mb-0 opacity-75">Satisfaction Rate</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="container">
            <div class="cta-section text-white py-5">
                <div class="container py-5">
                    <div class="row justify-content-center">
                        <div class="col-lg-8 text-center">
                            <h2 class="display-5 fw-bold mb-4">Ready to Join Our Community?</h2>
                            <p class="lead mb-4">Whether you're buying or selling, SneakerHub Ethiopia is your ultimate sneaker destination.</p>
                            <div class="d-flex flex-wrap justify-content-center gap-3">
                                <a href="register.php" class="btn btn-light btn-lg px-4 py-3">
                                    <i class="fas fa-user-plus me-2"></i>Join Now
                                </a>
                                <a href="contact.php" class="btn btn-outline-light btn-lg px-4 py-3">
                                    <i class="fas fa-question-circle me-2"></i>Get Help
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
</body>
</html>