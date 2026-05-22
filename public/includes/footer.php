<?php
// Footer for public pages
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'SneakerHub Ethiopia');
}
?>

<footer class="bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-shoe-prints fa-2x text-primary me-3"></i>
                    <h4 class="fw-bold mb-0"><span class="text-primary">Sneaker</span><span class="text-white">Hub</span> Ethiopia</h4>
                </div>
                <p class="mb-4">Ethiopia's premier destination for authentic sneakers. Connecting buyers with trusted vendors nationwide.</p>
                <div class="social-links">
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle me-2" title="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle me-2" title="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle me-2" title="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle" title="Telegram">
                        <i class="fab fa-telegram"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h5 class="fw-bold mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php" class="text-white-50 text-decoration-none hover-primary">Home</a></li>
                    <li class="mb-2"><a href="shop.php" class="text-white-50 text-decoration-none hover-primary">Shop</a></li>
                    <li class="mb-2"><a href="about.php" class="text-white-50 text-decoration-none hover-primary">About Us</a></li>
                    <li class="mb-2"><a href="contact.php" class="text-white-50 text-decoration-none hover-primary">Contact</a></li>
                    <li class="mb-2"><a href="terms.php" class="text-white-50 text-decoration-none hover-primary">Terms & Conditions</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="fw-bold mb-3">Customer Service</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="faq.php" class="text-white-50 text-decoration-none hover-primary">FAQ</a></li>
                    <li class="mb-2"><a href="shipping.php" class="text-white-50 text-decoration-none hover-primary">Shipping Information</a></li>
                    <li class="mb-2"><a href="returns.php" class="text-white-50 text-decoration-none hover-primary">Return Policy</a></li>
                    <li class="mb-2"><a href="privacy.php" class="text-white-50 text-decoration-none hover-primary">Privacy Policy</a></li>
                    <li class="mb-2"><a href="size-guide.php" class="text-white-50 text-decoration-none hover-primary">Size Guide</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3 mb-4">
                <h5 class="fw-bold mb-3">Contact Us</h5>
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <div class="d-flex">
                            <i class="fas fa-map-marker-alt text-primary me-2 mt-1"></i>
                            <div>
                                <p class="mb-0 fw-semibold">Address</p>
                                <p class="mb-0 text-white-50 small">Addis Ababa, Ethiopia</p>
                            </div>
                        </div>
                    </li>
                    <li class="mb-3">
                        <div class="d-flex">
                            <i class="fas fa-phone text-primary me-2 mt-1"></i>
                            <div>
                                <p class="mb-0 fw-semibold">Phone</p>
                                <p class="mb-0 text-white-50 small">+251 911 123 456</p>
                            </div>
                        </div>
                    </li>
                    <li class="mb-3">
                        <div class="d-flex">
                            <i class="fas fa-envelope text-primary me-2 mt-1"></i>
                            <div>
                                <p class="mb-0 fw-semibold">Email</p>
                                <p class="mb-0 text-white-50 small">info@sneakerhub.et</p>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="d-flex">
                            <i class="fas fa-clock text-primary me-2 mt-1"></i>
                            <div>
                                <p class="mb-0 fw-semibold">Hours</p>
                                <p class="mb-0 text-white-50 small">Mon-Sun: 9:00 AM - 8:00 PM</p>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        
        <hr class="bg-secondary my-4">
        
        <div class="row align-items-center">
            <div class="col-md-6 mb-3 mb-md-0">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-2 d-inline-block me-3">Accepted Payment Methods:</p>
                <div class="d-inline-block">
                    <i class="fab fa-cc-visa fa-lg me-2 text-white-50" title="Visa"></i>
                    <i class="fab fa-cc-mastercard fa-lg me-2 text-white-50" title="MasterCard"></i>
                    <i class="fas fa-university fa-lg me-2 text-white-50" title="Bank Transfer"></i>
                    <i class="fas fa-mobile-alt fa-lg text-white-50" title="Mobile Money"></i>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12 text-center">
                <p class="text-white-50 small mb-0">
                    <i class="fas fa-shield-alt text-primary me-1"></i>
                    Secure Shopping Guaranteed
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button type="button" class="btn btn-primary btn-floating btn-lg" id="btn-back-to-top">
    <i class="fas fa-arrow-up"></i>
</button>

<style>
    .bg-dark {
        background-color: #0b0f0e !important;
    }
    
    .text-primary {
        color: #16a34a !important;
    }
    
    .hover-primary {
        transition: color 0.2s ease;
    }
    
    .hover-primary:hover {
        color: #4ade80 !important;
    }
    
    .btn-primary {
        background-color: #16a34a;
        border-color: #16a34a;
    }
    
    .btn-primary:hover {
        background-color: #0f7a35;
        border-color: #0f7a35;
    }
    
    .btn-floating {
        position: fixed;
        bottom: 20px;
        right: 20px;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s ease;
        background-color: #16a34a;
        border-color: #16a34a;
    }
    
    .btn-floating:hover {
        background-color: #0f7a35;
        border-color: #0f7a35;
    }
    
    .btn-floating.show {
        opacity: 1;
    }
</style>

<script>
    // Back to top button
    const backToTopButton = document.getElementById("btn-back-to-top");
    
    window.onscroll = function() {
        if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
            backToTopButton.classList.add("show");
        } else {
            backToTopButton.classList.remove("show");
        }
    };
    
    backToTopButton.addEventListener("click", function() {
        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
    });
</script>