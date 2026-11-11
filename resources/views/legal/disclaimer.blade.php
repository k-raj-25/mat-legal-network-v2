@php $co = $site['legal_entity_name']; @endphp
<x-layouts.legal :title="$title" :page="$page" :updated="$updated">
<p><strong>Please read this Disclaimer carefully before using MAT LEGAL NETWORK.</strong></p>

<h2>1. Bar Council of India Rules</h2>
<p>The Bar Council of India does not permit advocates to advertise or solicit work (Rule 36, Chapter II, Part VI of the Bar Council of India Rules). This website is <strong>not</strong> an advertisement or solicitation by any advocate. By accessing it, you acknowledge and confirm that:</p>
<ul>
    <li>you are seeking information about advocates of your own accord, and there has been no advertisement, personal communication, solicitation, invitation or inducement of any sort by us or any advocate;</li>
    <li>the information about each advocate is provided by that advocate, is factual in nature, and is made available only on your specific request to view it;</li>
    <li>any use of this website or its information is at your own volition and risk.</li>
</ul>

<h2>2. Not a law firm; no legal advice</h2>
<p>{{ $co }} is not a law firm, does not practise law and does not give legal advice. Nothing on this website constitutes legal advice or creates an advocate–client relationship between you and us. Any professional relationship is formed only between you and the advocate you engage, on terms you agree directly with them.</p>

<h2>3. No endorsement or guarantee</h2>
<p>Listing on the Platform does not mean that we recommend, endorse or vouch for any advocate’s competence, conduct or results. We do not guarantee the outcome of any matter. You are solely responsible for choosing an advocate and should make your own enquiries before engaging one.</p>

<h2>4. Verification limits</h2>
<p>We review enrolment and identity documents submitted by advocates before publishing a profile. This review is limited and is not a guarantee that the information is accurate, current or complete, or that an advocate holds a valid Certificate of Practice at any given time.</p>

<h2>5. Fees</h2>
<p>Consultation fees shown on profiles are indicative and provided by the advocate. Actual fees are agreed directly between you and the advocate. We do not collect fees for legal services and do not share in any advocate’s fees.</p>

<h2>6. Limitation of liability</h2>
<p>We shall not be liable for any loss or damage arising from reliance on information on the website, or from the acts, omissions, advice or services of any advocate. Please see our <a href="{{ route('legal', 'terms') }}">Terms of Use</a> for full details.</p>
</x-layouts.legal>
