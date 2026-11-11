@php $co = $site['legal_entity_name']; @endphp
<x-layouts.legal :title="$title" :page="$page" :updated="$updated">
<p>MAT LEGAL NETWORK, including everything that makes up the Platform, is the result of significant investment of skill, labour, time and money by {{ $co }}. This Policy explains what we own and what you may and may not do with it.</p>

<h2>1. What we own</h2>
<ul>
    <li><strong>Trademarks:</strong> the name “MAT LEGAL NETWORK”, the MAT logo and monogram, and any associated slogans are trademarks of {{ $co }} (™). Their use without our prior written consent is prohibited, and may amount to infringement or passing off under the Trade Marks Act, 1999.</li>
    <li><strong>Copyright:</strong> the website software and source code, page designs and layouts, user-interface and “look and feel”, graphics, icons, illustrations, written content, legal documents, and the selection, arrangement and compilation of the lawyer directory are original works protected under the Copyright Act, 1957 (a compilation, including a computer database, is a “literary work” under Section 2(o)).</li>
    <li><strong>Data:</strong> the directory database, including the structure of our filters, categories and listings, and all enquiry and usage data generated through the Platform.</li>
</ul>
<p>Profile content supplied by lawyers remains theirs, licensed to us for display under our Lawyer Listing Terms.</p>

<h2>2. What you may do</h2>
<ul>
    <li>View the Platform in a web browser for your personal, non-commercial use in finding legal help.</li>
    <li>Share links to pages on the Platform.</li>
</ul>

<h2>3. What you may not do</h2>
<ul>
    <li>Copy, reproduce, republish, distribute or adapt the website’s design, layout, code, text, graphics or any substantial part of the directory.</li>
    <li>Scrape, crawl, harvest, or extract data (manually or automatically) or circumvent rate limits or technical protections.</li>
    <li>Use our content or data to create, populate or train any competing directory, product, service or AI/ML model.</li>
    <li>Create a website, app or service that imitates our name, branding, design or user experience so as to cause confusion.</li>
    <li>Frame or mirror any part of the Platform, or remove any copyright, trademark or proprietary notice.</li>
</ul>

<h2>4. Enforcement</h2>
<p>We actively monitor for copying and misuse. Infringement may lead to:</p>
<ul>
    <li>civil remedies including injunctions, damages and accounts of profits under Sections 55 of the Copyright Act, 1957 and Sections 134–135 of the Trade Marks Act, 1999;</li>
    <li>criminal proceedings under Section 63 of the Copyright Act, 1957 and Sections 103–104 of the Trade Marks Act, 1999;</li>
    <li>compensation under Section 43 of the Information Technology Act, 2000 for unauthorised access to, or downloading, copying or extraction of data from, our computer systems, and prosecution under Section 66 where done dishonestly or fraudulently;</li>
    <li>notices to hosting providers, domain registrars, app stores and search engines to remove infringing material.</li>
</ul>

<h2>5. Reporting infringement</h2>
<p>If you believe content on the Platform infringes your rights, or you see someone copying our Platform, write to <a href="mailto:{{ $site['grievance_officer_email'] }}">{{ $site['grievance_officer_email'] }}</a> with details of the work, the location of the infringing material and your contact details. We will respond within the timelines set out in our <a href="{{ route('legal', 'grievance') }}">Grievance Redressal</a> policy.</p>

<h2>6. Permissions</h2>
<p>For any use not expressly permitted, including press or partnership use of our name or logo, contact <a href="mailto:{{ $site['company_email'] }}">{{ $site['company_email'] }}</a>. Permission must be in writing.</p>
</x-layouts.legal>
