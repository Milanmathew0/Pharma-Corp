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
        background: linear-gradient(
          rgba(44, 157, 183, 0.1),
          rgba(44, 157, 183, 0.1)
        );
      }

      .feature-card {
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        background: white;
        transition: transform 0.3s ease;
      }

      .feature-card:hover {
        transform: translateY(-5px);
      }

      .feature-card img {
        border-radius: 8px;
        height: 200px;
        object-fit: cover;
        width: 100%;
      }

      .feature-icon {
        font-size: 2.5rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
      }

      .testimonial-section {
        background-color: #f8f9fc;
      }

      .testimonial-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin: 20px;
      }

      .cta-section {
        background-color: var(--primary-color);
        color: white;
        padding: 60px 0;
      }

      footer {
        background-color: #333;
        color: white;
        padding: 40px 0;
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
        <a class="navbar-brand" href="index.html">Pharma-Corp</a>
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
              <a class="nav-link" href="index.html#features">Features</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="services.html">Services</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="index.html#testimonials"
                >Testimonials</a
              >
            </li>
            <li class="nav-item">
              <a class="nav-link" href="contact.html">Contact</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="login.html">Login</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="registration.html">Register</a>
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
            <a href="contact.html" class="btn btn-primary btn-lg"
              >Get Started</a
            >
          </div>
          <div class="col-lg-6">
            <img
              src="images/hero/hero-bg.jpg"
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
              <img
                src="images/features/feature-1.jpg"
                alt="Feature 1"
                class="img-fluid mb-3"
              />
              <h3>Patient Management</h3>
              // ... content ...
            </div>
          </div>
          <div class="col-md-4">
            <div class="feature-card">
              <img
                src="images/features/feature-2.jpg"
                alt="Feature 2"
                class="img-fluid mb-3"
              />
              <h3>Inventory Control</h3>
              // ... content ...
            </div>
          </div>
          <div class="col-md-4">
            <div class="feature-card">
              <img
                src="images/features/feature-3.jpg"
                alt="Feature 3"
                class="img-fluid mb-3"
              />
              <h3>Analytics Dashboard</h3>
              // ... content ...
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
        <a href="registration.html" class="btn btn-light btn-lg"
          >Create Account</a
        >
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
              <li><a href="about.html" class="text-white">About Us</a></li>
              <li><a href="services.html" class="text-white">Services</a></li>
              <li>
                <a href="privacy.html" class="text-white">Privacy Policy</a>
              </li>
              <li>
                <a href="terms.html" class="text-white">Terms of Service</a>
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
