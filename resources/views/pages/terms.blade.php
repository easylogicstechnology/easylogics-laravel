@extends('layouts.page')

@section('title', 'Terms & Conditions')
@section('meta_description', 'Terms & Conditions for using Easy Logics Technology services.')
@section('heading', 'Terms & Conditions')
@section('subheading')Last updated {{ date('F Y') }}@endsection

@section('content')
    <div class="page-note">
        <i class="fa fa-info-circle"></i> This is a general template. Please review and adapt it with
        your own legal/compliance team before relying on it.
    </div>

    <p>
        These Terms &amp; Conditions ("Terms") govern your access to and use of the software,
        websites and services (the "Services") provided by <strong>Easy Logics Technology</strong>
        ("we", "us", "our"). By accessing or using the Services you agree to be bound by these Terms.
    </p>

    <h2>1. Use of the Services</h2>
    <p>
        You agree to use the Services only for lawful purposes and in accordance with these Terms.
        You are responsible for maintaining the confidentiality of your account credentials and for
        all activity that occurs under your account.
    </p>

    <h2>2. Accounts &amp; access</h2>
    <p>
        Accounts are provided to authorised committee members, property managers, accountants and
        other users of a society. You must provide accurate information and keep it up to date. We may
        suspend or terminate access that violates these Terms or is used improperly.
    </p>

    <h2>3. Customer data</h2>
    <p>
        You retain ownership of the data you enter into the Services. You are responsible for the
        accuracy and legality of that data. We process it to provide the Services and as described in
        our <a href="{{ route('privacy') }}">Privacy Policy</a>.
    </p>

    <h2>4. Fees</h2>
    <p>
        Where the Services are provided on a paid basis, fees and billing terms are as agreed in your
        subscription or order. Fees are non-refundable except where required by law or expressly
        stated otherwise.
    </p>

    <h2>5. Availability</h2>
    <p>
        We strive to keep the Services available and reliable, but we do not guarantee uninterrupted
        access. We may perform maintenance, updates or changes from time to time.
    </p>

    <h2>6. Limitation of liability</h2>
    <p>
        To the extent permitted by law, we are not liable for any indirect, incidental or
        consequential damages arising from your use of the Services. Nothing in these Terms limits
        liability that cannot be limited under applicable law.
    </p>

    <h2>7. Changes to these Terms</h2>
    <p>
        We may update these Terms from time to time. Continued use of the Services after changes take
        effect constitutes acceptance of the updated Terms.
    </p>

    <h2>8. Contact</h2>
    <p>
        Questions about these Terms? Contact us at
        <a href="mailto:info@easylogicstechnology.com">info@easylogicstechnology.com</a>.
    </p>
@endsection
