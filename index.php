<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScrapSmart - Smart Value for Scrap</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --dark-text: #1e293b;
            --light-bg: #ffffff;
            --card-shadow: rgba(0, 0, 0, 0.08);
        }

        /* --- White Theme with Image Background --- */
        body {
            background: linear-gradient(rgba(255, 255, 255, 0.92), rgba(255, 255, 255, 0.92)), 
                        url('https://img.freepik.com/premium-photo/industrial-warehouse-filled-with-scrap-material_836950-2817.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            color: var(--dark-text);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* --- Attractive White Glass Cards --- */
        .feature-card, .step-card, .contact-card {
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
            border-radius: 24px;
            padding: 40px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 30px var(--card-shadow);
            color: var(--dark-text);
        }

        .feature-card:hover {
            background: #ffffff !important;
            border-color: var(--primary) !important;
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.1);
        }

        .navbar {
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.2rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.6rem;
            color: var(--dark-text) !important;
        }
        .navbar-brand i { color: var(--primary); }
        .brand-highlight { color: var(--primary); }

        .nav-link {
            color: var(--dark-text) !important;
            font-weight: 600;
            transition: 0.3s;
            margin: 0 10px;
        }

        .nav-link:hover { color: var(--primary) !important; }

        .hero {
            padding: 180px 0 100px;
            min-height: 100vh;
        }

        .hero h1 {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 25px;
            color: var(--dark-text);
        }

        .feature-icon {
            width: 60px; height: 60px;
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 25px;
        }

        .btn-primary-custom {
            background: var(--primary);
            color: white; padding: 14px 35px;
            border-radius: 12px; font-weight: 600;
            border: none; transition: 0.3s;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-primary-custom:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline-custom {
            border: 2px solid var(--primary);
            color: var(--primary); padding: 14px 35px;
            border-radius: 12px; font-weight: 600;
            background: transparent; transition: 0.3s;
        }

        .btn-outline-custom:hover {
            background: var(--primary);
            color: white;
        }

        .section-title {
            font-size: 2.8rem; font-weight: 800;
            text-align: center; margin-bottom: 60px;
            color: var(--dark-text);
        }

        .step-number {
            font-size: 3rem; font-weight: 800;
            color: rgba(16, 185, 129, 0.2);
            margin-bottom: 10px;
        }

        .form-control {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: var(--dark-text) !important;
            padding: 15px; border-radius: 12px;
        }

        /* --- ENHANCED FOOTER STYLING --- */
        /* --- Enhanced Footer Styling --- */
.footer {
    background: #0f172a; /* Deep navy background from image */
    padding: 80px 0 30px;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.footer h5 {
    color: #ffffff;
    font-weight: 700;
    margin-bottom: 25px;
}

.footer-link {
    color: #94a3b8 !important;
    text-decoration: none;
    display: block;
    margin-bottom: 12px;
    transition: 0.3s;
}

.footer-link:hover {
    color: var(--primary) !important;
}

.social-icon {
    width: 38px;
    height: 38px;
    background: rgba(255, 255, 255, 0.05);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: white;
    text-decoration: none;
    margin-right: 10px;
    transition: 0.3s;
}

.social-icon:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-3px);
}

.newsletter-box {
    display: flex;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 5px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.newsletter-box input {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
}

.newsletter-btn {
    background: #3b82f6; /* Blue button from image */
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    transition: 0.3s;
}

.newsletter-btn:hover {
    background: #2563eb;
}

        .text-secondary { color: #64748b !important; }

        html { scroll-behavior: smooth; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="fas fa-recycle me-2"></i>Smart<span class="brand-highlight">Scrap</span></a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                <li class="nav-item"><a class="nav-link" href="#how-it-works">Process</a></li>
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
            </ul>
        </div>
    </div>
</nav>

<section id="home" class="hero">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h1 data-aos="fade-up">Turn Your Scrap Into <br><span style="color: var(--primary);">Smart Digital Value</span></h1>
                <p class="lead mb-5" data-aos="fade-up" data-aos-delay="100" style="color: #64748b; max-width: 700px; margin: 0 auto;">A smart platform where sellers get the best price and dealers get quality scrap fast, transparent, and reliable.</p>
                <div data-aos="fade-up" data-aos-delay="200" class="d-flex justify-content-center gap-3">
                    <a href="seller/login.php" class="btn btn-primary-custom px-5">Sell Scrap Now</a>
                    <a href="choose-role.php" class="btn btn-outline-custom">Get Started</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="about" class="py-5 mt-5">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <h2 class="display-5 fw-800 mb-4"><b>Manage</b><span class="text-success"><b> Waste Smarter</b></span></h2>
                <p class="text-secondary mb-4" style="line-height: 1.8;">ScrapSmart is an innovative platform that bridges the gap between scrap vendors and verified stakeholders. We're on a mission to make scrap collection efficient, transparent, and rewarding for everyone involved.</p>
                <div class="row g-3">
                    <div class="col-6"><p><i class="fas fa-check-circle text-success me-2"></i> Verified Dealers</p></div>
                    <div class="col-6"><p><i class="fas fa-check-circle text-success me-2"></i> Live Pricing</p></div>
                    <div class="col-6"><p><i class="fas fa-check-circle text-success me-2"></i> OTP Verification</p></div>
                    <div class="col-6"><p><i class="fas fa-check-circle text-success me-2"></i> GPS Tracking</p></div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="position-relative">
                    <img src="https://img.freepik.com/premium-photo/industrial-warehouse-filled-with-scrap-material_836950-2817.jpg" class="img-fluid rounded-5 shadow-lg" alt="Recycling Scrap Material">
                </div>
            </div>
        </div>
    </div>
</section>

<section id="features" class="py-5">
    <div class="container py-5">
        <h2 class="section-title" data-aos="fade-up">Why Choose Us?</h2>
        <div class="row g-4">
            <div class="col-md-4" data-aos="zoom-in">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <h4>Location-Based</h4>
                    <p class="text-secondary">Find nearest scrap dealers in your area with our smart location tracking system.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="zoom-in" data-aos-delay="100">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                    <h4>Dynamic Pricing</h4>
                    <p class="text-secondary">Get real-time market prices for all types of scrap materials instantly.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="zoom-in" data-aos-delay="200">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-lock"></i></div>
                    <h4>Secure & Verified</h4>
                    <p class="text-secondary">End-to-end security with OTP-based verification for every transaction.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="how-it-works" class="py-5 text-center">
    <div class="container py-5">
        <h2 class="section-title" data-aos="fade-up">Simple 4-Step Process</h2>
        <div class="row g-4">
            <div class="col-md-3" data-aos="fade-up"><div class="step-card"><div class="step-number">01</div><h5>Register</h5><p class="text-secondary small">Easy onboarding for all users</p></div></div>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="100"><div class="step-card"><div class="step-number">02</div><h5>Discover</h5><p class="text-secondary small">Find dealers around you</p></div></div>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="200"><div class="step-card"><div class="step-number">03</div><h5>Schedule</h5><p class="text-secondary small">Book pickups at your ease</p></div></div>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="300"><div class="step-card"><div class="step-number">04</div><h5>Get Paid</h5><p class="text-secondary small">Transparent digital valuation</p></div></div>
        </div>
    </div>
</section>

<section id="contact" class="py-5 mb-5">
    <div class="container py-5">
        <div class="row g-5">
            <div class="col-lg-5" data-aos="fade-right">
                <h2 class="fw-800 mb-4">Let's Talk!</h2>
                <p class="text-secondary mb-5">Have questions? Our team is here to help you make your waste valuable.</p>
                <div class="d-flex mb-4">
                    <div class="icon-box me-3 text-success"><i class="fas fa-envelope"></i></div>
                    <div><p class="mb-0 fw-bold">Email Us</p><p class="text-secondary">support@scrapsmart.com</p></div>
                </div>
                <div class="d-flex">
                    <div class="icon-box me-3 text-success"><i class="fas fa-phone"></i></div>
                    <div><p class="mb-0 fw-bold">Call Us</p><p class="text-secondary">+91 98765 43210</p></div>
                </div>
            </div>
            <div class="col-lg-7" data-aos="fade-left">
                <div class="contact-card">
                    <form>
                        <div class="row">
                            <div class="col-md-6 mb-3"><input type="text" class="form-control" placeholder="Name"></div>
                            <div class="col-md-6 mb-3"><input type="email" class="form-control" placeholder="Email"></div>
                        </div>
                        <div class="mb-3"><textarea class="form-control" rows="4" placeholder="Your Message"></textarea></div>
                        <button class="btn btn-primary-custom w-100">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <a class="navbar-brand mb-3 d-inline-block" href="#" style="font-size: 1.8rem;">
                    <i class="fas fa-recycle me-2" style="color: #10b981;"></i><span style="color: #ffffff;">Scrap</span><span class="brand-highlight">Smart</span>
                </a>
                <p class="text-secondary mb-4">Revolutionizing the scrap industry with transparency and technology. Making recycling profitable for everyone.</p>
                <div class="d-flex">
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h5>Quick Links</h5>
                <a href="#home" class="footer-link">Home</a>
                <a href="#features" class="footer-link">Features</a>
                <a href="#" class="footer-link">Market Prices</a>
                <a href="#" class="footer-link">Dealers List</a>
            </div>

            <div class="col-lg-2 col-md-6">
                <h5>Support</h5>
                <a href="#" class="footer-link">Help Center</a>
                <a href="#" class="footer-link">Terms of Service</a>
                <a href="#" class="footer-link">Privacy Policy</a>
                <a href="#contact" class="footer-link">Contact Us</a>
            </div>

            <div class="col-lg-4">
                <h5>Newsletter</h5>
                <p class="text-secondary mb-3">Stay updated with the latest scrap market rates.</p>
                <div class="newsletter-box">
                    <input type="email" class="form-control" placeholder="Your Email">
                    <button class="newsletter-btn"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>

        <hr class="my-5 border-secondary opacity-25">
        
        <div class="text-center">
            <p class="mb-0 small text-secondary">&copy; 2026 ScrapSmart. Empowering Sustainable Disposal. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 1000, once: true });
</script>
</body>
</html>