<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Easy Logics Technology &mdash; GST-Compliant Society Accounting Software</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="GST-compliant society accounting &amp; management software for managing committees, property managers, accountants and auditors. Billing, ledgers, registers and compliance on one platform.">
        <link rel="icon" type="image/png" href="{{ asset('images/fvcon.png') }}">
        <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        <link href="{{ asset('css/home.css') }}?v={{ filemtime(public_path('css/home.css')) }}" rel="stylesheet">
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
                        <li><a href="#features">Features</a></li>
                        <li><a href="#solutions">Solutions</a></li>
                        <li><a href="#why">Why Us</a></li>
                        <li><a href="#founder">Partners</a></li>
                        <li><a href="/about/about_us.php">About</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </nav>
                <div class="nav-cta">
                    <a class="btn btn-primary" href="javascript:void(0)" onclick="openLogin()"><i class="fa fa-sign-in"></i> Login</a>
                    <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu"><i class="fa fa-bars"></i></button>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section class="hero">
            <div class="wrap">
                <div class="hero-text">
                    <span class="hero-eyebrow"><i class="fa fa-shield"></i> GST Compliant &bull; Audit Ready &bull; Trusted Since 2018</span>
                    <h1>Society Accounting &amp; Management, <span class="accent">made effortless.</span></h1>
                    <p class="lead">A single, integrated platform for managing committees, property managers, accountants and auditors &mdash; from maintenance billing to ledgers, registers and statutory compliance.</p>
                    <div class="hero-actions">
                        <a class="btn btn-accent" href="javascript:void(0)" onclick="openLogin()"><i class="fa fa-rocket"></i> Get Started</a>
                        <a class="btn btn-ghost" href="#features"><i class="fa fa-play-circle"></i> Explore Features</a>
                        <a class="btn btn-outline" href="#enquiry"><i class="fa fa-envelope"></i> Enquiry</a>
                    </div>
                    <div class="hero-trust">
                        <span><i class="fa fa-check-circle"></i> Double-entry accounting</span>
                        <span><i class="fa fa-check-circle"></i> Cloud based</span>
                        <span><i class="fa fa-check-circle"></i> Secure &amp; reliable</span>
                    </div>
                </div>
                <div class="hero-badge b1">
                    <i class="fa fa-file-text-o"></i>
                    <div><div class="hb-num">Instant</div><div class="hb-lbl">Maintenance bills</div></div>
                </div>
                <div class="hero-badge b2">
                    <i class="fa fa-balance-scale"></i>
                    <div><div class="hb-num">Trial Balance</div><div class="hb-lbl">Always ties out</div></div>
                </div>
            </div>
            <svg class="hero-wave" viewBox="0 0 1440 70" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path fill="#ffffff" d="M0,40 C360,80 720,0 1080,24 C1260,36 1380,48 1440,40 L1440,70 L0,70 Z"></path>
            </svg>
        </section>

        <!-- At a glance : live figures, same source as the admin dashboard -->
        @php
            $gv = fn ($k) => $glanceStats[$k] ?? 0;
        @endphp
        <section class="glance">
            <div class="wrap">
                <div class="section-head reveal">
                    <span class="eyebrow">Trusted across India</span>
                    <h2>Easy Logics <span class="accent">at a Glance</span></h2>
                </div>
                <div class="glance-row reveal">
                    <div class="glance-item">
                        <div class="glance-emoji">🧾</div>
                        <div class="glance-num" data-count-target="{{ (int) $gv('bills') }}" data-count-final="{{ \App\Support\HomePage::glanceNumber($gv('bills')) }}">0</div>
                        <div class="glance-lbl">Bills &amp; receipts<br>generated</div>
                    </div>
                    <div class="glance-item">
                        <div class="glance-emoji">👥</div>
                        <div class="glance-num" data-count-target="{{ (int) $gv('members') }}" data-count-final="{{ \App\Support\HomePage::glanceNumber($gv('members')) }}">0</div>
                        <div class="glance-lbl">Members on<br>the platform</div>
                    </div>
                    <div class="glance-item">
                        <div class="glance-emoji">🏢</div>
                        <div class="glance-num" data-count-target="{{ (int) $gv('societies') }}" data-count-final="{{ \App\Support\HomePage::glanceNumber($gv('societies')) }}">0</div>
                        <div class="glance-lbl">Societies<br>onboarded</div>
                    </div>
                    <div class="glance-item">
                        <div class="glance-emoji">🏬</div>
                        <div class="glance-num" data-count-target="{{ (int) $gv('buildings') }}" data-count-final="{{ \App\Support\HomePage::glanceNumber($gv('buildings')) }}">0</div>
                        <div class="glance-lbl">Buildings<br>managed</div>
                    </div>
                    <div class="glance-item">
                        <div class="glance-emoji">🏘️</div>
                        <div class="glance-num" data-count-target="{{ (int) $gv('wings') }}" data-count-final="{{ \App\Support\HomePage::glanceNumber($gv('wings')) }}">0</div>
                        <div class="glance-lbl">Wings<br>managed</div>
                    </div>
                    <div class="glance-item">
                        <div class="glance-emoji">🤝</div>
                        <div class="glance-num" data-count-target="{{ (int) $gv('resellers') }}" data-count-final="{{ \App\Support\HomePage::glanceNumber($gv('resellers')) }}">0</div>
                        <div class="glance-lbl">Channel partners<br>&amp; associates</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Founder : dynamic, admin-managed (Admin > Website Content > Manage Founder) -->
        @if (!empty($founder) && !empty($founder->name))
        <section class="founder" id="founder">
            <div class="wrap">
                <div class="section-head reveal">
                    <span class="eyebrow">Leadership</span>
                    <h2>Meet the <span class="accent">Founder</span></h2>
                </div>
                <div class="founder-card reveal">
                    <div class="founder-photo">
                        @if (!empty($founder->image_path))
                            <img src="{{ asset($founder->image_path) }}" alt="{{ $founder->name }}" loading="lazy">
                        @else
                            <img src="{{ asset('images/user.png') }}" alt="{{ $founder->name }}" loading="lazy">
                        @endif
                    </div>
                    <div class="founder-info">
                        <h2>{{ $founder->name }}</h2>
                        @if (!empty($founder->designation))
                            <span class="founder-role">{{ $founder->designation }}</span>
                        @endif
                        @if (!empty($founder->description))
                            <p>{{ $founder->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>
        @endif

        <!-- Features -->
        <section class="features" id="features">
            <div class="wrap">
                <div class="section-head reveal">
                    <span class="eyebrow">Everything you need</span>
                    <h2>One platform to run your <span class="accent">society</span></h2>
                    <p>Purpose-built for managing committees, property managers, accountants and auditors &mdash; seamlessly integrated end to end.</p>
                </div>
                <div class="feat-grid">
                    <div class="feat-card reveal">
                        <div class="feat-icon"><i class="fa fa-database"></i></div>
                        <h3>Society Data Management</h3>
                        <p>A single source of truth for members, flats, wings and vehicles &mdash; with ownership, tenant and transfer history in one place.</p>
                    </div>
                    <div class="feat-card reveal">
                        <div class="feat-icon"><i class="fa fa-calculator"></i></div>
                        <h3>Accounting Management</h3>
                        <p>Full double-entry accounting with ledgers, journal vouchers, receipts, payments and a trial balance that always ties out.</p>
                    </div>
                    <div class="feat-card reveal">
                        <div class="feat-icon"><i class="fa fa-file-text"></i></div>
                        <h3>Maintenance Billing</h3>
                        <p>Generate periodic maintenance bills and debit notes, apply interest or penalty on late payment, and reward prompt payers with rebates.</p>
                    </div>
                    <div class="feat-card reveal">
                        <div class="feat-icon"><i class="fa fa-bullhorn"></i></div>
                        <h3>Notices &amp; Circulars</h3>
                        <p>Publish notices and circulars to all members instantly and keep a clean record of every communication you send.</p>
                    </div>
                    <div class="feat-card reveal">
                        <div class="feat-icon"><i class="fa fa-book"></i></div>
                        <h3>Manage Registers</h3>
                        <p>Maintain statutory registers &mdash; members, share, nomination and property &mdash; accurately and always ready for inspection.</p>
                    </div>
                    <div class="feat-card reveal">
                        <div class="feat-icon"><i class="fa fa-check-square-o"></i></div>
                        <h3>Manage Compliance</h3>
                        <p>Stay audit-ready with GST-compliant records, statutory reports and compliance tracking built into every module.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Powerful Features : the complete brochure list -->
        <section class="powerfeat" id="powerful">
            <div class="wrap">
                <div class="section-head reveal">
                    <span class="eyebrow">Everything included</span>
                    <h2>Our <span class="accent">Powerful Features</span></h2>
                    <p>Every book, register, report and statutory form your society needs &mdash; all in one place.</p>
                </div>
                <div class="pf-grid reveal">
                    <div class="pf-col">
                        <h4><i class="fa fa-calculator"></i> Accounts &amp; Ledgers</h4>
                        <ul>
                            <li>Book of Accounts</li>
                            <li><a href="{{ asset('files/demo_reports/bank_book_demo.pdf') }}" target="_blank" rel="noopener">Bank Book</a></li>
                            <li>Cash Book</li>
                            <li><a href="{{ asset('files/demo_reports/balance_sheet_demo.pdf') }}" target="_blank" rel="noopener">Balance Sheet</a></li>
                            <li>Trial Balance</li>
                            <li><a href="{{ asset('files/demo_reports/income_expenditure_demo.pdf') }}" target="_blank" rel="noopener">Income &amp; Expenditure</a></li>
                            <li>General Ledger</li>
                            <li>Member Ledger</li>
                            <li>Bank Reconciliation</li>
                            <li>Receipts &amp; Payment</li>
                            <li>GST Register</li>
                        </ul>
                    </div>
                    <div class="pf-col">
                        <h4><i class="fa fa-cogs"></i> Billing &amp; Communication</h4>
                        <ul>
                            <li>Multiple Bill Formats</li>
                            <li>Multiple Languages</li>
                            <li>Member Access</li>
                            <li>Mail &amp; SMS</li>
                            <li>Automatic Bill Generation<small>Monthly &bull; Quarterly &bull; Bi-monthly &bull; Half-yearly &bull; Yearly</small></li>
                            <li>TDS &amp; GST</li>
                            <li>Easy Access, anywhere</li>
                        </ul>
                    </div>
                    <div class="pf-col">
                        <h4><i class="fa fa-folder-open"></i> Registers, Forms &amp; Notices</h4>
                        <ul>
                            <li>Form I &amp; Form J</li>
                            <li>Share Register</li>
                            <li>FD Register</li>
                            <li>Audit Form<small>Form 1, Form 28, Form 0</small></li>
                            <li>By-Laws</li>
                            <li>Complaint Register</li>
                            <li>Letters &mdash; Outstanding Reminder &amp; General Notice (MC Act 101)</li>
                            <li>Circular &amp; Notice</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Trust strip : six assurances -->
        <section class="trust-strip">
            <div class="wrap">
                <div class="trust-row reveal">
                    <div class="trust-cell"><div class="tc-ic"><i class="fa fa-shield"></i></div><h5>Secure Data</h5></div>
                    <div class="trust-cell"><div class="tc-ic"><i class="fa fa-cloud"></i></div><h5>Cloud Based</h5></div>
                    <div class="trust-cell"><div class="tc-ic"><i class="fa fa-mobile"></i></div><h5>Mobile App</h5></div>
                    <div class="trust-cell"><div class="tc-ic"><i class="fa fa-headphones"></i></div><h5>24&times;7 Support</h5></div>
                    <div class="trust-cell"><div class="tc-ic"><i class="fa fa-refresh"></i></div><h5>Auto Backup</h5></div>
                    <div class="trust-cell"><div class="tc-ic"><i class="fa fa-users"></i></div><h5>Multi-User Access</h5></div>
                </div>
            </div>
        </section>

        <!-- Detail: Billing -->
        <section class="detail" id="solutions">
            <div class="wrap">
                <div class="detail-text reveal">
                    <span class="eyebrow">Society Billing</span>
                    <h2>Bill members in minutes, not days</h2>
                    <p style="color:var(--slate)">Raise accurate maintenance bills for the whole society or for specific purposes, exactly as your bye-laws require.</p>
                    <ul class="detail-list">
                        <li><i class="fa fa-check-circle"></i> Maintenance charges on a regular periodical basis, or purpose-specific debit notes generated with ease.</li>
                        <li><i class="fa fa-check-circle"></i> Interest and penalty automatically charged on delayed payments.</li>
                        <li><i class="fa fa-check-circle"></i> Rebate for prompt payment, as per the rules set by your society.</li>
                    </ul>
                </div>
                <div class="detail-media reveal"><img src="{{ asset('images/billing.png') }}" alt="Maintenance billing screen"></div>
            </div>
        </section>

        <!-- Detail: Accounting -->
        <section class="detail rev">
            <div class="wrap">
                <div class="detail-text reveal">
                    <span class="eyebrow">Accounting &amp; Ledgers</span>
                    <h2>Books that always balance</h2>
                    <p style="color:var(--slate)">Every transaction flows straight into your books, so your financial position is accurate the moment it happens.</p>
                    <ul class="detail-list">
                        <li><i class="fa fa-check-circle"></i> Ledger heads, journal vouchers and general receipts with proper cash and bank tracking.</li>
                        <li><i class="fa fa-check-circle"></i> Trial balance with clear flags whenever it does not tie, so differences are easy to locate.</li>
                        <li><i class="fa fa-check-circle"></i> Receipts, payments, income &amp; expenditure and balance sheet, ready whenever you need them.</li>
                    </ul>
                </div>
                <div class="detail-media reveal"><img src="{{ asset('images/ledger-heads.png') }}" alt="Ledger and accounting screen"></div>
            </div>
        </section>

        <!-- Detail: Reports & Compliance -->
        <section class="detail">
            <div class="wrap">
                <div class="detail-text reveal">
                    <span class="eyebrow">Reports &amp; Compliance</span>
                    <h2>Audit-ready, all year round</h2>
                    <p style="color:var(--slate)">Give your accountants and auditors exactly what they need &mdash; complete, GST-compliant records at their fingertips.</p>
                    <ul class="detail-list">
                        <li><i class="fa fa-check-circle"></i> Member ledgers covering current and old members, so nothing is ever lost on transfer.</li>
                        <li><i class="fa fa-check-circle"></i> Statutory registers and reports maintained as required by law.</li>
                        <li><i class="fa fa-check-circle"></i> GST-compliant billing and reporting across every module.</li>
                    </ul>
                </div>
                <div class="detail-media reveal"><img src="{{ asset('images/High.jpg') }}" alt="Reports and compliance"
                    onerror="this.onerror=null;this.src='{{ asset('images/billing.png') }}';"></div>
            </div>
        </section>

        <!-- Why -->
        <section class="why" id="why">
            <div class="wrap">
                <div class="section-head reveal">
                    <span class="eyebrow">Why Easy Logics</span>
                    <h2>Built for the way societies actually work</h2>
                </div>
                <div class="why-grid">
                    <div class="why-item reveal">
                        <div class="wi-ic"><i class="fa fa-cubes"></i></div>
                        <h3>Truly integrated</h3>
                        <p>Data, billing, accounting and compliance work together &mdash; no double entry, no reconciliation headaches.</p>
                    </div>
                    <div class="why-item reveal">
                        <div class="wi-ic"><i class="fa fa-file-o"></i></div>
                        <h3>GST compliant</h3>
                        <p>Billing and reporting designed to keep your society compliant out of the box.</p>
                    </div>
                    <div class="why-item reveal">
                        <div class="wi-ic"><i class="fa fa-users"></i></div>
                        <h3>For every role</h3>
                        <p>Committees, property managers, accountants and auditors &mdash; each gets the view they need.</p>
                    </div>
                    <div class="why-item reveal">
                        <div class="wi-ic"><i class="fa fa-headphones"></i></div>
                        <h3>Real support</h3>
                        <p>A responsive team on call and email, ready to help you whenever you need it.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Channel Partners : dynamic, admin-managed (Admin > Manage Partners) -->
        @if ($partners->isNotEmpty())
        <section class="partners" id="partners">
            <div class="wrap">
                <div class="section-head reveal">
                    <span class="eyebrow">Our Network</span>
                    <h2>Trusted <span class="accent">Channel Partners</span></h2>
                </div>
                <p class="partners-intro reveal">Accounting professionals and consultants across India who help societies get the most out of Easy Logics.</p>
                <div class="partners-grid reveal">
                    @foreach ($partners as $partner)
                    <div class="partner-card">
                        <div class="partner-photo">
                            @if (!empty($partner->image_path))
                                <img src="{{ asset($partner->image_path) }}" alt="{{ $partner->name }}" loading="lazy">
                            @else
                                <img src="{{ asset('images/user.png') }}" alt="{{ $partner->name }}" loading="lazy">
                            @endif
                        </div>
                        <div class="partner-body">
                            @if (!empty($partner->location))
                                <span class="partner-location">{{ $partner->location }}</span>
                            @endif
                            <div class="partner-name">{{ $partner->name }}</div>
                            @if (!empty($partner->company))
                                <div class="partner-company">({{ $partner->company }})</div>
                            @endif
                            <div class="partner-underline"></div>
                            @if (!empty($partner->description))
                                <div class="partner-desc">{{ $partner->description }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        <!-- CTA -->
        <div class="cta reveal">
            <h2>Ready to simplify your society management?</h2>
            <p>Join the committees and accountants already running billing, accounting and compliance on one seamless platform.</p>
            <div class="hero-actions">
                <a class="btn btn-accent" href="javascript:void(0)" onclick="openLogin()"><i class="fa fa-rocket"></i> Get Started</a>
                <a class="btn btn-ghost" href="#contact"><i class="fa fa-phone"></i> Talk to Us</a>
            </div>
        </div>

        <!-- Enquiry -->
        <section class="enquiry" id="enquiry">
            <div class="wrap enquiry-grid">
                <div class="enquiry-copy reveal">
                    <span class="eyebrow">Let's Talk</span>
                    <h2>We'd love to hear <span class="accent">from you.</span></h2>
                    <p>Questions about billing, accounting or compliance for your society? Tell us a bit about your community and our team will reach out.</p>
                    <div class="enquiry-pills">
                        <a class="pill-mail" href="mailto:info@easylogicstechnology.com"><i class="fa fa-envelope"></i> info@easylogicstechnology.com</a>
                        <a class="pill-phone" href="tel:+918108107496"><i class="fa fa-phone"></i> +91 8108107496</a>
                    </div>
                </div>
                <div class="enquiry-card reveal">
                    <h3><i class="fa fa-comment"></i> How can we help?</h3>
                    <div id="enquiryResult"></div>
                    <form id="enquiry_form">
                        <div class="eq-field">
                            <label for="eq_society">Society / Apartment / Villa Community *</label>
                            <input type="text" id="eq_society" name="society_name" placeholder="Enter society name" required>
                        </div>
                        <div class="eq-row">
                            <div class="eq-field">
                                <label for="eq_city">City</label>
                                <input type="text" id="eq_city" name="city" placeholder="Enter city">
                            </div>
                            <div class="eq-field">
                                <label for="eq_phone">Phone *</label>
                                <input type="text" id="eq_phone" name="phone" placeholder="+91 98xxxxxxxx" required>
                            </div>
                        </div>
                        <div class="eq-row">
                            <div class="eq-field">
                                <label for="eq_email">Email</label>
                                <input type="email" id="eq_email" name="email" placeholder="Enter email">
                            </div>
                            <div class="eq-field">
                                <label for="eq_describe">What best describes you?</label>
                                <select id="eq_describe" name="describes_you">
                                    <option value="">Select...</option>
                                    <option value="Committee Member">Committee Member</option>
                                    <option value="Property Manager">Property Manager</option>
                                    <option value="Accountant">Accountant</option>
                                    <option value="Auditor">Auditor</option>
                                    <option value="Reseller / Channel Partner">Reseller / Channel Partner</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="eq-row">
                            <div class="eq-field">
                                <label for="eq_fname">First Name *</label>
                                <input type="text" id="eq_fname" name="first_name" placeholder="First name" required>
                            </div>
                            <div class="eq-field">
                                <label for="eq_lname">Last Name</label>
                                <input type="text" id="eq_lname" name="last_name" placeholder="Last name">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-accent" id="enquirySubmitBtn"><i class="fa fa-paper-plane"></i> Send my message</button>
                        <div class="enquiry-note">We'll only use these details to get in touch about your enquiry.</div>
                    </form>
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
                            <li><a href="#features">Features</a></li>
                            <li><a href="#solutions">Solutions</a></li>
                            <li><a href="#why">Why Us</a></li>
                            <li><a href="javascript:void(0)" onclick="openLogin()">Login</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4>Company</h4>
                        <ul class="foot-links">
                            <li><a href="/about/about_us.php">About Us</a></li>
                            <li><a href="/terms/termandcondition">Terms &amp; Conditions</a></li>
                            <li><a href="/privacy_policy">Privacy Policy</a></li>
                            <li><a href="#contact">Contact Us</a></li>
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

        <!-- Login modal -->
        <div class="modal-ov" id="loginModal" onclick="if(event.target===this)closeLogin()">
            <div class="modal-card" role="dialog" aria-modal="true" aria-label="Login">
                <div class="modal-head">
                    <button class="modal-close" onclick="closeLogin()" aria-label="Close">&times;</button>
                    <h3>Welcome back</h3>
                    <p>Sign in to your Easy Logics account</p>
                </div>
                <div class="modal-body">
                    <div class="flash-slot">
                        @if ($loginMessage)<div class="error">{{ $loginMessage }}</div>@endif
                    </div>
                    <form class="form" action="{{ route('login') }}" method="post" id="login_form">
                        @csrf
                        <div class="form-field">
                            <label for="username">Username</label>
                            <div class="input-wrap">
                                <i class="fa fa-user"></i>
                                <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="Enter your username" required autocomplete="username">
                            </div>
                        </div>
                        <div class="form-field">
                            <label for="password">Password</label>
                            <div class="input-wrap">
                                <i class="fa fa-lock"></i>
                                <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                            </div>
                        </div>
                        <div class="form-row">
                            <label><input type="checkbox" name="remember_me" value="1"> Keep me logged in</label>
                            <a href="javascript:void(0)">Forgot password?</a>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-sign-in"></i> Sign in</button>
                    </form>
                    <div class="modal-foot">New to Easy Logics? <a href="{{ route('login') }}">Register now</a></div>
                </div>
            </div>
        </div>

        <script>
        function openLogin(){document.getElementById('loginModal').classList.add('open');document.body.style.overflow='hidden';}
        function closeLogin(){document.getElementById('loginModal').classList.remove('open');document.body.style.overflow='';}
        document.getElementById('enquiry_form').addEventListener('submit', function(e){
            e.preventDefault();
            var btn=document.getElementById('enquirySubmitBtn');
            var resultBox=document.getElementById('enquiryResult');
            btn.disabled=true;
            var formData=new FormData(this);
            fetch('{{ route('enquiry.submit') }}',{method:'POST',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:formData})
                .then(function(r){return r.json();})
                .then(function(res){
                    resultBox.innerHTML='<div class="flash-slot"><div class="'+(res.error?'error':'success')+'">'+res.message+'</div></div>';
                    if(!res.error){document.getElementById('enquiry_form').reset();}
                    btn.disabled=false;
                })
                .catch(function(){
                    resultBox.innerHTML='<div class="flash-slot"><div class="error">Could not send your enquiry right now. Please try again later.</div></div>';
                    btn.disabled=false;
                });
        });
        function toggleNav(){document.getElementById('navLinks').classList.toggle('open');}
        document.addEventListener('keydown',function(e){if(e.key==='Escape')closeLogin();});

        // Sticky nav shadow
        var nav=document.getElementById('siteNav');
        window.addEventListener('scroll',function(){
            if(window.scrollY>10)nav.classList.add('scrolled');else nav.classList.remove('scrolled');
        });

        // Close mobile menu on link click
        document.querySelectorAll('#navLinks a').forEach(function(a){
            a.addEventListener('click',function(){document.getElementById('navLinks').classList.remove('open');});
        });

        // Reveal on scroll
        var io=new IntersectionObserver(function(entries){
            entries.forEach(function(en){if(en.isIntersecting){en.target.classList.add('in');io.unobserve(en.target);}});
        },{threshold:.12});
        document.querySelectorAll('.reveal').forEach(function(el){io.observe(el);});

        // Auto count-up for the "at a glance" live figures, once the row scrolls into view
        function runCountUp(el){
            var target=parseInt(el.getAttribute('data-count-target'),10)||0;
            var final=el.getAttribute('data-count-final');
            var duration=1400,start=null;
            function step(ts){
                if(!start)start=ts;
                var progress=Math.min((ts-start)/duration,1);
                var eased=1-Math.pow(1-progress,3);
                el.textContent=Math.floor(eased*target).toLocaleString('en-IN');
                if(progress<1){requestAnimationFrame(step);}else{el.textContent=final;}
            }
            requestAnimationFrame(step);
        }
        var glanceIo=new IntersectionObserver(function(entries){
            entries.forEach(function(en){
                if(en.isIntersecting){
                    en.target.querySelectorAll('.glance-num[data-count-target]').forEach(runCountUp);
                    glanceIo.unobserve(en.target);
                }
            });
        },{threshold:.3});
        document.querySelectorAll('.glance-row').forEach(function(el){glanceIo.observe(el);});

        // If login failed, the server set a flash message -> auto-open the modal
        @if ($loginMessage)
        openLogin();
        @endif
        </script>

    </body>
</html>
