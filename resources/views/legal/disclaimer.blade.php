@php $co = $site['legal_entity_name']; @endphp
<x-layouts.legal :title="$title" :page="$page" :updated="$updated">
<p><strong>Please read this Disclaimer carefully before using MAT LEGAL NETWORK.</strong></p>

<h2>1. Bar Council of India Rules</h2>
<p>The Bar Council of India does not permit advocates to advertise or solicit work (Rule 36, Chapter II, Part VI of the Bar Council of India Rules). This website is <strong>not</strong> an advertisement or solicitation by {{ $co }} or any advocate. By accessing it, you acknowledge and confirm that:</p>
<ul>
    <li>you are seeking information about {{ $co }} and advocates of your own accord, and there has been no advertisement, personal communication, solicitation, invitation or inducement of any sort by us or any advocate;</li>
    <li>the information about each advocate is provided by that advocate, is factual in nature, and is made available only on your specific request to view it;</li>
    <li>any use of this website or its information is at your own volition and risk.</li>
</ul>

<h2>2. Our legal services and independent advocates</h2>
<p>{{ $co }} is a law firm and gives legal advice through its own advocates. The general information on this website is not legal advice on your matter, and browsing the website, calling us or submitting an enquiry does not by itself create an advocate–client relationship with us. That relationship is formed only when you engage {{ $co }} for a specific matter and we agree to act for you.</p>
<p>Advocates listed in the directory are independent professionals and are not partners or employees of {{ $co }}. If you engage a listed advocate, the professional relationship, advice and fees are solely between you and that advocate, on terms you agree directly with them, and {{ $co }} is not responsible for their advice or services.</p>

<h2>3. No endorsement or guarantee</h2>
<p>Listing on the Platform does not mean that we recommend, endorse or vouch for any listed advocate’s competence, conduct or results. We do not guarantee the outcome of any matter. You are solely responsible for choosing an advocate and should make your own enquiries before engaging one.</p>

<h2>4. Verification limits</h2>
<p>We review enrolment and identity documents submitted by advocates before publishing a profile. This review is limited and is not a guarantee that the information is accurate, current or complete, or that an advocate holds a valid Certificate of Practice at any given time.</p>

<h2>5. Fees</h2>
<p>Consultation fees shown on profiles are indicative and provided by the advocate. Actual fees are agreed directly between you and the advocate. If you engage {{ $co }} itself, the fees for our services are agreed directly between you and us. We do not share in any listed advocate’s fees.</p>

<h2>6. Limitation of liability</h2>
<p>We shall not be liable for any loss or damage arising from reliance on information on the website, or from the acts, omissions, advice or services of any independent advocate listed on the website. Please see our <a href="{{ route('legal', 'terms') }}">Terms of Use</a> for full details.</p>
</x-layouts.legal>
