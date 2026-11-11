@php $co = $site['legal_entity_name']; @endphp
<x-layouts.legal :title="$title" :page="$page" :updated="$updated">
<p>These Lawyer Listing Terms (“<strong>Listing Terms</strong>”) apply to every advocate (“<strong>you</strong>”) who registers on MAT LEGAL NETWORK (the “<strong>Platform</strong>”), operated by {{ $co }} (“<strong>we</strong>”, “<strong>us</strong>”). They are in addition to our <a href="{{ route('legal', 'terms') }}">Terms of Use</a> and <a href="{{ route('legal', 'privacy') }}">Privacy Policy</a>. By registering, you accept these Listing Terms.</p>

<h2>1. Eligibility</h2>
<ul>
    <li>You must be an advocate enrolled with a State Bar Council in India and entitled to practise (including holding a valid Certificate of Practice where applicable).</li>
    <li>You must not be suspended or debarred from practice. You must inform us within 7 days if your enrolment or right to practise is suspended, withdrawn or restricted.</li>
</ul>

<h2>2. Registration and verification</h2>
<ul>
    <li>You must provide true, accurate and complete information and genuine documents, and keep them up to date.</li>
    <li>Your profile becomes visible to the public only after we approve it. We may verify your details with the relevant Bar Council or other sources.</li>
    <li>We may approve, request changes to, reject, suspend or remove any profile at our sole discretion, including where information appears inaccurate, documents cannot be verified, complaints are received, or these Listing Terms are breached.</li>
</ul>

<h2>3. Profile content and professional conduct</h2>
<ul>
    <li>Your profile must be factual and comply with the Bar Council of India Rules, including Rule 36 (no advertising or solicitation). Do not include claims of success rates, comparisons with other advocates, testimonials, case results, or your phone number, email or website in the description.</li>
    <li>You must not upload content that is false, misleading, defamatory, infringing, obscene or otherwise unlawful.</li>
    <li>You are solely responsible for the professional services you provide, your advice, fees and conduct, and for compliance with the Advocates Act, 1961 and the Bar Council of India Rules.</li>
</ul>

<h2>4. Changes to your profile</h2>
<p>Once approved, edits you make go live immediately. Every change is recorded and reviewed by our team. We may reverse changes or suspend a profile that does not comply with these Listing Terms.</p>

<h2>5. Client enquiries</h2>
<ul>
    <li>Clients contact us, not you directly; we pass on enquiries at our discretion. We do not guarantee any number or type of enquiries.</li>
    <li>Client details we share with you are confidential personal data. You may use them only to respond to that client’s request, must keep them secure, and must not use them for marketing or share them with anyone else.</li>
    <li>You are free to decline any matter, for example due to conflict of interest.</li>
</ul>

<h2>6. Fees</h2>
<p>Listing on the Platform is currently <strong>free of charge</strong>. We do not charge you any referral fee or share in your professional fees. If we introduce optional paid features in the future, we will tell you in advance and they will apply only if you choose them.</p>

<h2>7. Licence to us</h2>
<p>You grant us a non-exclusive, royalty-free, worldwide licence to host, display, format and reproduce the public parts of your profile (including your photograph) on the Platform and in search engine results, for as long as your profile is listed. You confirm you have the right to grant this licence.</p>

<h2>8. Platform ownership</h2>
<p>You must not copy, scrape, or reuse the Platform’s design, software, content or other Lawyers’ information, or use our name or marks without written permission. See our <a href="{{ route('legal', 'intellectual-property') }}">Intellectual Property &amp; Copyright Policy</a>.</p>

<h2>9. Independent relationship</h2>
<p>You are an independent professional. Nothing in these Listing Terms creates an employment, partnership, agency or fee-sharing relationship between you and us.</p>

<h2>10. Indemnity and liability</h2>
<p>You will indemnify us against any claim arising from your profile content, your professional services, or your breach of these Listing Terms or any law. Our liability to you is limited as set out in the Terms of Use.</p>

<h2>11. Closing your account</h2>
<p>You may ask us to remove your profile at any time by writing to <a href="mailto:{{ $site['company_email'] }}">{{ $site['company_email'] }}</a>. We will remove it from public view promptly and handle your data as described in the Privacy Policy.</p>

<h2>12. Governing law</h2>
<p>These Listing Terms are governed by Indian law, and the courts at {{ $site['jurisdiction_city'] }} shall have exclusive jurisdiction.</p>
</x-layouts.legal>
