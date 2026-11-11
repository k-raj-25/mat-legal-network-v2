@php $co = $site['legal_entity_name']; @endphp
<x-layouts.legal :title="$title" :page="$page" :updated="$updated">
<p>In accordance with the Information Technology Act, 2000, the Information Technology (Intermediary Guidelines and Digital Media Ethics Code) Rules, 2021 and the Digital Personal Data Protection Act, 2023, {{ $co }} has appointed a Grievance Officer to address complaints about the Platform.</p>

<h2>Grievance Officer</h2>
<p><strong>{{ $site['grievance_officer_name'] }}</strong><br>
{{ $co }}<br>
{{ $site['company_address'] }}<br>
Email: <a href="mailto:{{ $site['grievance_officer_email'] }}">{{ $site['grievance_officer_email'] }}</a><br>
Hours: {{ $site['support_hours'] }}</p>

<h2>What you can raise</h2>
<ul>
    <li>Content on the Platform that you believe is unlawful, inaccurate, defamatory, infringing or violates our Terms of Use;</li>
    <li>Concerns about how your personal data is processed, or requests to access, correct or erase it;</li>
    <li>Complaints about a lawyer’s profile, or misuse of the Platform;</li>
    <li>Reports of copying, scraping or impersonation of MAT LEGAL NETWORK.</li>
</ul>

<h2>How to raise a complaint</h2>
<p>Email the Grievance Officer with your name, contact details, a description of the issue, the relevant page link(s) or Profile ID, and any supporting material.</p>

<h2>Timelines</h2>
<ul>
    <li>We will <strong>acknowledge</strong> your complaint within <strong>24 hours</strong>.</li>
    <li>We will <strong>resolve</strong> it within <strong>15 days</strong> of receipt.</li>
    <li>Complaints about content that exposes a person’s private areas, shows them in full or partial nudity, or impersonates them will be acted on within <strong>24 hours</strong>, as required by Rule 3(2)(b) of the 2021 Rules.</li>
</ul>

<h2>Escalation</h2>
<p>If you are not satisfied with our resolution, you may approach the Grievance Appellate Committee constituted under the 2021 Rules, or, for personal data matters, the Data Protection Board of India under the DPDP Act, 2023.</p>
</x-layouts.legal>
