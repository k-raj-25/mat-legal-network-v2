# MAT LEGAL NETWORK
# Automated scraping or bulk copying of this website is prohibited by our Terms of Use.
User-agent: *
Disallow: /admin
Disallow: /lawyer/
Disallow: /lawyer$
Disallow: /login
Disallow: /register
Disallow: /forgot-password
Disallow: /enquiries
Disallow: /locations/

# AI training / bulk data-harvesting crawlers
User-agent: GPTBot
Disallow: /

User-agent: CCBot
Disallow: /

User-agent: Google-Extended
Disallow: /

User-agent: Bytespider
Disallow: /

User-agent: ClaudeBot
Disallow: /

Sitemap: {{ route('sitemap') }}
