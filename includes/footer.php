<!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5><i class="fas fa-calendar-alt me-2"></i><?php echo SITE_NAME; ?></h5>
                    <p class="text-muted">Your premier destination for booking amazing events. Discover concerts, sports, conferences, and more!</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                
                <div class="col-md-2 mb-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>" class="text-muted text-decoration-none">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/events/events.php" class="text-muted text-decoration-none">Events</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">About Us</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-md-2 mb-3">
                    <h6>Categories</h6>
                    <ul class="list-unstyled">
                        <?php
                        $footer_categories = getCategories();
                        foreach (array_slice($footer_categories, 0, 4) as $category):
                        ?>
                        <li>
                            <a href="<?php echo SITE_URL; ?>/events/events.php?category=<?php echo $category['id']; ?>" 
                               class="text-muted text-decoration-none">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="col-md-2 mb-3">
                    <h6>Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none">Help Center</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">FAQ</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Terms of Service</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div class="col-md-2 mb-3">
                    <h6>Contact Info</h6>
                    <ul class="list-unstyled text-muted">
                        <li><i class="fas fa-envelope me-2"></i><?php echo SITE_EMAIL; ?></li>
                        <li><i class="fas fa-phone me-2"></i>+1 (555) 123-4567</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i>123 Event Street<br>New York, NY 10001</li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">
                        <i class="fas fa-shield-alt me-1"></i>Secure Booking
                        <span class="mx-2">|</span>
                        <i class="fas fa-headset me-1"></i>24/7 Support
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($additional_scripts)): ?>
        <?php foreach ($additional_scripts as $script): ?>
            <script src="<?php echo SITE_URL; ?>/assets/js/<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
</body>
</html>