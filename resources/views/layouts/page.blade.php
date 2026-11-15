<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>@yield('title', 'Easy Logics Technology') &mdash; Easy Logics Technology</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="@yield('meta_description', 'GST-compliant society accounting & management software.')">
        <link rel="icon" type="image/png" href="{{ asset('images/fvcon.png') }}">
        <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        <link href="{{ asset('css/home.css') }}?v={{ filemtime(public_path('css/home.css')) }}" rel="stylesheet">
        <style>
            .page-hero{background:linear-gradient(135deg,var(--blue,#1e5fa8),var(--blue-dark,#164a85));color:#fff;padding:64px 0 48px}
            .page-hero h1{font-family:'Poppins',sans-serif;font-weight:800;font-size:2.2rem;margin:0 0 8px}
            .page-hero p{margin:0;opacity:.9;font-size:1.05rem}
            .page-body{padding:48px 0 64px}
            .page-body .prose{max-width:860px;margin:0 auto;color:#243244;line-height:1.75;font-size:1rem}
            .page-body .prose h2{font-family:'Poppins',sans-serif;font-weight:700;color:var(--blue-dark,#164a85);font-size:1.4rem;margin:32px 0 12px}
            .page-body .prose h3{font-family:'Poppins',sans-serif;font-weight:600;color:#1f2d3d;font-size:1.1rem;margin:22px 0 8px}
            .page-body .prose p{margin:0 0 14px}
            .page-body .prose ul{margin:0 0 16px;padding-left:22px}
            .page-body .prose li{margin:6px 0}
            .page-body .prose a{color:var(--blue,#1e5fa8)}
            .page-note{background:#f4f8fd;border:1px solid #dbe8f5;border-radius:10px;padding:14px 18px;margin:0 0 22px;font-size:.92rem;color:#375}
        </style>
    </head>
    <body>
        <!-- Top bar -->
        <div class="topbar">
            <div class="wrap">
                <div class="topbar-info">
                    <span><i class="fa fa-phone"></i>+91 8108107496 / +91 8208902649</span>
                    <span class="sep"><i class="fa fa-envelope"></i>info@easylogicstechnology.com</span>
                </div>
                <div class="topbar-social">
                    <a href="#" aria-label="Facebook"><i class="fa fa-facebook"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fa fa-linkedin"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fa fa-twitter"></i></a>
                </div>
            </div>
        </div>

        <!-- Nav -->
        <header class="nav" id="siteNav">
            <div class="wrap">
                <a class="nav-logo" href="{{ url('/') }}"><img src="{{ asset('images/easylogistic-7-6.png') }}" alt="Easy Logics Technology"></a>
                <nav>
                    <ul class="nav-links" id="navLinks">
                        <li><a href="{{ url('/') }}#features">Features</a></li>
                        <li><a href="{{ url('/') }}#solutions">Solutions</a></li>
                        <li><a href="{{ url('/') }}#why">Why Us</a></li>
                        <li><a href="{{ url('/') }}#founder">Partners</a></li>
                        <li><a href="{{ route('about') }}">About</a></li>
                        <li><a href="{{ url('/') }}#contact">Contact</a></li>
                    </ul>
                </nav>
                <div class="nav-cta">
                    <a class="btn btn-primary" href="{{ route('login') }}"><i class="fa fa-sign-in"></i> Login</a>
                    <button class="nav-toggle" onclick="document.getElementById('navLinks').classList.toggle('open')" aria-label="Menu"><i class="fa fa-bars"></i></button>
                </div>
            </div>
        </header>

        <!-- Page hero -->
        <section class="page-hero">
            <div class="wrap">
                <h1>@yield('heading', 'Easy Logics Technology')</h1>
                <p>@yield('subheading')</p>
            </div>
        </section>

        <!-- Page content -->
        <section class="page-body">
            <div class="wrap">
                <div class="prose">
                    @yield('content')
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="site" id="contact">
            <div class="wrap">
                <div class="foot-grid">
                    <div>
                        <span class="foot-logo"><img src="{{ asset('images/easylogistic-7-6.png') }}" alt="Easy Logics Technology"></span>
                        <p>GST-compliant society accounting &amp; management software for committees, property managers, accountants and auditors &mdash; on one integrated platform.</p>
                        <div class="foot-social">
                            <a href="#" aria-label="Facebook"><i class="fa fa-facebook"></i></a>
                            <a href="#" aria-label="LinkedIn"><i class="fa fa-linkedin"></i></a>
                            <a href="#" aria-label="Twitter"><i class="fa fa-twitter"></i></a>
                        </div>
                    </div>
                    <div>
                        <h4>Product</h4>
                        <ul class="foot-links">
                            <li><a href="{{ url('/') }}#features">Features</a></li>
                            <li><a href="{{ url('/') }}#solutions">Solutions</a></li>
                            <li><a href="{{ url('/') }}#why">Why Us</a></li>
                            <li><a href="{{ route('login') }}">Login</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4>Company</h4>
                        <ul class="foot-links">
                            <li><a href="{{ route('about') }}">About Us</a></li>
                            <li><a href="{{ route('terms') }}">Terms &amp; Conditions</a></li>
                            <li><a href="{{ route('privacy') }}">Privacy Policy</a></li>
                            <li><a href="{{ url('/') }}#contact">Contact Us</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4>Get in touch</h4>
                        <ul class="foot-contact" style="list-style:none">
                            <li><i class="fa fa-phone"></i><span>+91 8108107496<br>+91 8208902649</span></li>
                            <li><i class="fa fa-envelope"></i><span>info@easylogicstechnology.com</span></li>
                            <li><i class="fa fa-map-marker"></i><span>2nd Floor, Office No. 214, Gaury Commercial Complex, Nagar Road, Near Vasai Station (East)</span></li>
                        </ul>
                    </div>
                </div>
                <div class="foot-bottom">
                    &copy; {{ date('Y') }} Easy Logics Technology. All Rights Reserved.
                </div>
            </div>
        </footer>
    </body>
</html>
