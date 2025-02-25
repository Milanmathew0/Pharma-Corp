<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pharma-Corp - Your Health, Our Priority</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <style>
      :root {
        --primary-color: #2c9db7;
        --secondary-color: #858796;
      }

      body {
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      }

      .navbar {
        background-color: var(--primary-color);
      }

      .hero-section {
        padding: 100px 0;
        background: linear-gradient(rgba(44, 157, 183, 0.1), rgba(44, 157, 183, 0.1)),
                    url('pic/3.jpg') no-repeat center center;
        background-size: cover;
        position: relative;
        color: white;
      }

      .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1;
      }

      .hero-section .container {
        position: relative;
        z-index: 2;
      }

      .hero-section h1 {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
      }

      .hero-section p {
        font-size: 1.25rem;
        margin-bottom: 2rem;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
      }

      .feature-card {
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        background: white;
        transition: all 0.3s ease;
        border: 1px solid rgba(44, 157, 183, 0.1);
      }

      .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 8px 25px rgba(44, 157, 183, 0.2);
      }

      .feature-card img {
        border-radius: 12px;
        height: 250px;
        object-fit: cover;
        width: 100%;
        margin-bottom: 1.5rem;
      }

      .feature-card h3 {
        color: var(--primary-color);
        margin-bottom: 1rem;
        font-weight: 600;
      }

      .testimonial-section {
        background: url('pic/4.jpg') no-repeat center center;
        background-size: cover;
        position: relative;
        padding: 100px 0;
      }

      .testimonial-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.95);
      }

      .testimonial-section .container {
        position: relative;
      }

      .testimonial-card {
        background: white;
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        margin: 20px;
        transition: all 0.3s ease;
      }

      .testimonial-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(44, 157, 183, 0.2);
      }

      .cta-section {
        background: linear-gradient(rgba(44, 157, 183, 0.9), rgba(44, 157, 183, 0.9)),
                    url('pic/5.jpg') no-repeat center center;
        background-size: cover;
        color: white;
        padding: 100px 0;
        position: relative;
      }

      .social-icons a {
        color: white;
        margin: 0 10px;
        font-size: 1.5rem;
      }
    </style>
  </head>
  <body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
      <div class="container">
        <a class="navbar-brand" href="index.php">Pharma-Corp</a>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item">
              <a class="nav-link" href="index.php#features">Features</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="services.php">Services</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="index.php#testimonials">Testimonials</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="contact.php">Contact</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="login.php">Login</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="registration.php">Register</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <h1>Healthcare Management Solutions</h1>
            <p>
              Streamline your healthcare operations with our innovative platform
            </p>
            <a href="contact.php" class="btn btn-primary btn-lg">Get Started</a>
          </div>
          <div class="col-lg-6">
            <img
              src="pic/3.jpg"
              alt="Healthcare Management"
              class="img-fluid rounded shadow"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
      <div class="container">
        <div class="row">
          <div class="col-md-4">
            <div class="feature-card">
              <img src="pic/3.jpg" alt="Patient Management" class="img-fluid mb-3" />
              <h3>Patient Management</h3>
              <p>Efficiently manage patient records, appointments, and medical histories with our comprehensive system.</p>
              <a href="#" class="btn btn-outline-primary">Learn More</a>
            </div>
          </div>
          <div class="col-md-4">
            <div class="feature-card">
              <img src="pic/4.jpg" alt="Inventory Control" class="img-fluid mb-3" />
              <h3>Inventory Control</h3>
              <p>Keep track of your pharmacy inventory in real-time with automated alerts and reorder notifications.</p>
              <a href="#" class="btn btn-outline-primary">Learn More</a>
            </div>
          </div>
          <div class="col-md-4">
            <div class="feature-card">
              <img src="pic/5.jpg" alt="Analytics Dashboard" class="img-fluid mb-3" />
              <h3>Analytics Dashboard</h3>
              <p>Make data-driven decisions with our powerful analytics dashboard and reporting tools.</p>
              <a href="#" class="btn btn-outline-primary">Learn More</a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonial-section py-5">
      <div class="container">
        <h2 class="text-center mb-5">What Our Customers Say</h2>
        <div class="row">
          <div class="col-md-4">
            <div class="testimonial-card p-4">
              <p class="mb-3">
                "Excellent service! The online prescription management has made
                my life so much easier."
              </p>
              <div class="d-flex align-items-center">
                <img
                  src="https://via.placeholder.com/50"
                  class="rounded-circle me-3"
                  alt="Customer"
                />
                <div>
                  <h6 class="mb-0">Sarah Johnson</h6>
                  <small class="text-muted">Regular Customer</small>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="testimonial-card p-4">
              <p class="mb-3">
                "Fast delivery and great customer service. I highly recommend
                their services!"
              </p>
              <div class="d-flex align-items-center">
                <img
                  src="https://via.placeholder.com/50"
                  class="rounded-circle me-3"
                  alt="Customer"
                />
                <div>
                  <h6 class="mb-0">Michael Brown</h6>
                  <small class="text-muted">Loyal Customer</small>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="testimonial-card p-4">
              <p class="mb-3">
                "The pharmacists are very knowledgeable and always ready to
                help. Great experience!"
              </p>
              <div class="d-flex align-items-center">
                <img
                  src="https://via.placeholder.com/50"
                  class="rounded-circle me-3"
                  alt="Customer"
                />
                <div>
                  <h6 class="mb-0">Emily Davis</h6>
                  <small class="text-muted">New Customer</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section text-center">
      <div class="container">
        <h2 class="mb-4">Ready to Get Started?</h2>
        <p class="lead mb-4">
          Join thousands of satisfied customers who trust us with their
          healthcare needs.
        </p>
        <a href="registration.php" class="btn btn-light btn-lg">Create Account</a>
      </div>
    </section>

    <!-- Footer -->
    <footer>
      <div class="container">
        <div class="row">
          <div class="col-md-4 mb-4">
            <h5>About Pharma-Corp</h5>
            <p>
              Your trusted partner in healthcare management, providing
              innovative solutions for a healthier tomorrow.
            </p>
          </div>
          <div class="col-md-4 mb-4">
            <h5>Quick Links</h5>
            <ul class="list-unstyled">
              <li><a href="about.php" class="text-white">About Us</a></li>
              <li><a href="services.php" class="text-white">Services</a></li>
              <li>
                <a href="privacy.php" class="text-white">Privacy Policy</a>
              </li>
              <li>
                <a href="terms.php" class="text-white">Terms of Service</a>
              </li>
            </ul>
          </div>
          <div class="col-md-4 mb-4">
            <h5>Contact Us</h5>
            <p>
              Email: info@pharma-corp.com<br />
              Phone: (555) 123-4567<br />
              Address: 123 Health Street, Medical City
            </p>
          </div>
        </div>
        <hr class="bg-white" />
        <div class="row">
          <div class="col-md-6 text-center text-md-start">
            <p class="mb-0">&copy; 2024 Pharma-Corp. All rights reserved.</p>
          </div>
          <div class="col-md-6 text-center text-md-end social-icons">
            <a href="#"><i class="fab fa-facebook"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-linkedin"></i></a>
          </div>
        </div>
      </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
