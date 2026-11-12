@php $co = $site['legal_entity_name']; @endphp
<x-layouts.legal :title="$title" :page="$page" :updated="$updated">
<p>This Privacy Policy explains how {{ $co }} (“<strong>we</strong>”, “<strong>us</strong>”), operating MAT LEGAL NETWORK (the “<strong>Platform</strong>”), collects, uses, shares and protects personal data. We act as a <strong>Data Fiduciary</strong> under the Digital Personal Data Protection Act, 2023 (“<strong>DPDP Act</strong>”), and follow the Information Technology Act, 2000 and the Information Technology (Reasonable Security Practices and Procedures and Sensitive Personal Data or Information) Rules, 2011.</p>

<h2>1. Personal data we collect</h2>
<h3>From people looking for a lawyer (“Users”)</h3>
<ul>
    <li>Name, mobile number, email address (optional), city, preferred contact method and the description of your matter that you submit in an enquiry.</li>
    <li>Records of calls or WhatsApp messages you make to our helpline.</li>
</ul>
<h3>From advocates (“Lawyers”)</h3>
<ul>
    <li>Name, gender (optional), photograph (optional), email, mobile and alternate numbers, office address and PIN code.</li>
    <li>Professional information: Bar enrolment number, State Bar Council, year of practice, education, firm, practice areas, courts, languages, consultation fee and profile description.</li>
    <li>Verification documents such as the Bar enrolment certificate/ID card and a government photo ID.</li>
    <li>Login credentials (passwords are stored only in hashed form) and a log of profile changes.</li>
</ul>
<h3>Automatically</h3>
<ul>
    <li>IP address, browser type, device information, pages visited and timestamps, collected through server logs and essential cookies (see our <a href="{{ route('legal', 'cookies') }}">Cookie Policy</a>).</li>
</ul>

<h2>2. Why we use it (purposes)</h2>
<ul>
    <li><strong>Users:</strong> to respond to your enquiry, understand your need, and share your request with suitable Lawyers so they can assist you.</li>
    <li><strong>Lawyers:</strong> to verify your enrolment and identity, publish your public profile, pass on client enquiries, communicate with you about your account, and maintain an audit trail of profile changes.</li>
    <li><strong>Everyone:</strong> to secure the Platform, prevent spam, fraud and scraping, comply with law, and improve our services.</li>
</ul>
<p>We process personal data on the basis of your <strong>consent</strong>, which you give when you submit a form or register, and for certain legitimate uses permitted by Section 7 of the DPDP Act (for example, compliance with law or a court order).</p>

<h2>3. What is public and what is not</h2>
<p>For Lawyers, only the public profile fields are shown on the website: name, photo, designation, firm, location (city and state), experience, education, practice areas, courts, languages, consultation fee, online-consultation availability, profile description and Profile ID. <strong>Phone numbers, email address, office address, enrolment number, Bar Council and verification documents are never displayed publicly.</strong></p>
<p>Users’ enquiries are never published.</p>

<h2>4. Who we share it with</h2>
<ul>
    <li><strong>Lawyers:</strong> your enquiry details (name, contact number, city and message) are shared with the Lawyer(s) we connect you with, with your consent.</li>
    <li><strong>Service providers (Data Processors):</strong> hosting, email delivery and communication providers who process data only on our instructions and under confidentiality obligations. We may also use an AI service provider to screen the text of enquiry messages for spam; your name, phone number, email address and IP address are not shared with it.</li>
    <li><strong>Authorities:</strong> where required by law, court order or a lawful request by a government agency.</li>
    <li><strong>Business transfer:</strong> in a merger, acquisition or sale of assets, subject to this Policy.</li>
</ul>
<p><strong>We do not sell or rent your personal data.</strong></p>

<h2>5. How long we keep it</h2>
<ul>
    <li>Enquiries: up to 3 years from the last interaction, unless a longer period is required by law.</li>
    <li>Lawyer accounts and documents: for as long as the account is active, and up to 3 years after closure for audit and dispute purposes.</li>
    <li>Security logs: up to 1 year.</li>
</ul>
<p>After this, data is deleted or anonymised.</p>

<h2>6. How we protect it</h2>
<p>We use reasonable security practices including HTTPS encryption, hashed passwords, role-based access, private storage for verification documents (accessible only to authorised staff, with every access logged), rate-limiting and anti-spam controls. No method of transmission or storage is 100% secure, but we work to protect your data and will notify affected persons and the Data Protection Board of India of a personal data breach as required by law.</p>

<h2>7. Your rights</h2>
<p>Subject to the DPDP Act, you have the right to:</p>
<ul>
    <li>access a summary of your personal data and how it is processed;</li>
    <li>correct, complete or update your personal data (Lawyers can do most of this from their dashboard);</li>
    <li>request erasure of your personal data, subject to legal retention requirements;</li>
    <li>withdraw consent at any time (this does not affect processing already done);</li>
    <li>nominate another person to exercise your rights in the event of death or incapacity;</li>
    <li>have your grievance redressed by our Grievance Officer, and then approach the Data Protection Board of India.</li>
</ul>
<p>To exercise these rights, email <a href="mailto:{{ $site['grievance_officer_email'] }}">{{ $site['grievance_officer_email'] }}</a>. We may need to verify your identity.</p>

<h2>8. Children</h2>
<p>The Platform is not intended for persons under 18. We do not knowingly collect personal data of children. If you are a parent or guardian and believe a child has provided data, contact us and we will delete it.</p>

<h2>9. Where data is stored</h2>
<p>Data is stored on servers located in India or in jurisdictions permitted under the DPDP Act. If data is transferred outside India, we will do so only as permitted by law.</p>

<h2>10. Changes to this Policy</h2>
<p>We may update this Policy. Significant changes will be highlighted on the Platform. The “Last updated” date shows the latest version.</p>

<h2>11. Grievance Officer</h2>
<p>{{ $site['grievance_officer_name'] }}<br>{{ $co }}, {{ $site['company_address'] }}<br>Email: <a href="mailto:{{ $site['grievance_officer_email'] }}">{{ $site['grievance_officer_email'] }}</a></p>
</x-layouts.legal>
