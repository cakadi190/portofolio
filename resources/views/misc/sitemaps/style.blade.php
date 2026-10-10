{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
@php
    $measurementId = app(\App\Services\SystemSettingService::class)
        ->get('google_analytics_id', config('services.google_analytics.measurement_id'));
@endphp
{{--
    Stylesheet XSL agar sitemap terbaca manusia di browser (mesin pencari
    mengabaikannya). Desain
    mengikuti penampil sitemap BatamTix; font Signika, logo dan warna merek milik proyek ini.
    Dibuat mandiri (CSS/JS inline) karena halaman XSL tidak lewat Vite.

    Kolom Judul muncul bila dokumen memakai namespace `custom`.
--}}
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
  xmlns:custom="{{ url('/schemas/sitemap-custom/1.0') }}">
  <xsl:output method="html" encoding="UTF-8" indent="yes" />

  <xsl:template match="/">
    <xsl:apply-templates select="sitemap:urlset | sitemap:sitemapindex" />
  </xsl:template>

  <xsl:template name="page">
    <xsl:param name="title" />
    <xsl:param name="count" />
    <xsl:param name="countLabel" />
    <xsl:param name="subtitle" />
    <xsl:param name="body" />
    <html lang="id">
      <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="robots" content="noindex" />
        <title><xsl:value-of select="$title" /> &#8226; {{ config('app.name') }}</title>
        <link rel="icon" href="{{ url('/favicon.svg') }}" type="image/svg+xml" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Signika:wght@400;500;600;700&amp;display=swap" />
@if ($measurementId)
        <script async="async" src="https://www.googletagmanager.com/gtag/js?id={{ $measurementId }}"><xsl:text> </xsl:text></script>
        <script><![CDATA[
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', @json($measurementId), {
            page_title: document.title,
            page_location: window.location.href,
            content_group: 'sitemap'
          });
        ]]></script>
@endif
        <style>
          :root {
            --primary: #2e3192; --accent: #f7941d; --green: #009245;
            --bg: #f8f9fa; --card: #fff; --text: #212529; --muted: #6c757d; --border: #dee2e6; --stripe: #00000008;
            --font-body: 'Signika', system-ui, sans-serif;
            --font-heading: 'Signika', system-ui, sans-serif;
          }
          * { box-sizing: border-box; }
          body { margin: 0; background: var(--bg); color: var(--text); font: 400 15px/1.55 var(--font-body); }
          h1, .brand { font-family: var(--font-heading); }
          a { color: var(--primary); }
          .navbar { background: var(--primary); margin-bottom: 24px; }
          .container { margin: 0 auto; max-width: 1140px; padding: 0 16px; }
          .navbar .container { align-items: center; display: flex; gap: 12px; justify-content: space-between; padding-block: 12px; }
          .brand { align-items: center; color: #fff; display: flex; font-weight: 700; gap: 12px; letter-spacing: -0.02em; text-decoration: none; }
          .brand img { display: block; height: 32px; width: auto; }
          .badge { background: #fff; border-radius: 999px; color: var(--primary); font-size: .8rem; font-weight: 600; padding: 3px 10px; white-space: nowrap; }
          .alert { background: #e9ecef; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 16px; padding: 12px 16px; }
          label { color: var(--muted); display: block; font-size: .85rem; margin-bottom: 4px; }
          input[type=search] { background: var(--card); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font: inherit; margin-bottom: 16px; padding: 8px 12px; width: 100%; }
          input[type=search]:focus { border-color: var(--primary); outline: 3px solid #2e319233; }
          .table-wrap { background: var(--card); border: 1px solid var(--border); border-radius: 8px; overflow-x: auto; }
          table { border-collapse: collapse; width: 100%; }
          th, td { padding: 10px 12px; text-align: left; vertical-align: middle; }
          thead th { border-bottom: 2px solid var(--border); font-family: var(--font-heading); font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; }
          tbody tr:nth-child(odd) { background: var(--stripe); }
          tbody tr:hover { background: #2e319212; }
          tbody tr[hidden] { display: none; }
          .url { word-break: break-all; }
          .muted { color: var(--muted); }
          .tag { background: #e9ecef; border-radius: 6px; font-size: .75rem; font-weight: 600; padding: 2px 8px; white-space: nowrap; }
          .tag-img { background: #e0f2fe; color: #075985; }
          .bar { background: #e9ecef; border-radius: 99px; flex: 1; height: 6px; overflow: hidden; }
          .bar span { background: var(--accent); display: block; height: 100%; }
          .prio { align-items: center; display: flex; gap: 8px; min-width: 110px; }
          details { margin-top: 6px; }
          summary { cursor: pointer; list-style: none; }
          summary::-webkit-details-marker { display: none; }
          details ul { border-left: 2px solid var(--border); font-size: .85rem; margin: 8px 0 0; padding: 0 0 0 12px; list-style: none; }
          details li { margin-bottom: 6px; word-break: break-all; }
          .empty { margin-top: 12px; text-align: center; }
          footer { color: var(--muted); font-size: .85rem; margin-top: 24px; padding-bottom: 32px; text-align: center; }
          .visually-hidden { border: 0; clip: rect(0 0 0 0); height: 1px; overflow: hidden; position: absolute; width: 1px; }
        </style>
      </head>
      <body>
        <nav class="navbar" aria-label="Navigasi peta situs">
          <div class="container">
            <a class="brand" href="{{ route('home') }}">
              <img src="{{ url('/images/brands/logo-white.svg') }}" alt="{{ config('app.name') }}" />
              <span><xsl:value-of select="$title" /></span>
            </a>
            <span class="badge" id="sitemap-count" data-label="{$countLabel}">
              <xsl:value-of select="$count" /><xsl:text> </xsl:text><xsl:value-of select="$countLabel" />
            </span>
          </div>
        </nav>
        <main class="container">
          <h1 class="visually-hidden"><xsl:value-of select="$title" /></h1>
          <div class="alert" role="alert">
            <strong>Informasi</strong><xsl:text> </xsl:text><xsl:copy-of select="$subtitle" />
          </div>
          <label for="sitemap-filter">Cari</label>
          <input type="search" id="sitemap-filter" placeholder="Ketik untuk menyaring baris&#8230;" />
          <div class="table-wrap"><xsl:copy-of select="$body" /></div>
          <p class="empty muted" id="sitemap-empty" hidden="hidden">Tidak ada baris yang cocok dengan pencarian.</p>
          <footer>
            Hak Cipta &#169; {{ date('Y') }} <a href="{{ route('home') }}">{{ config('app.name') }}</a>.
          </footer>
        </main>
        <script><![CDATA[
          (function () {
            var input = document.getElementById('sitemap-filter');
            var table = document.querySelector('table');
            if (!input || !table) { return; }
            var rows = Array.prototype.slice.call(table.tBodies[0].rows);
            var counter = document.getElementById('sitemap-count');
            var empty = document.getElementById('sitemap-empty');
            var label = counter.getAttribute('data-label');
            var searchTimer;
            function track(name, params) {
              if (typeof window.gtag === 'function') { window.gtag('event', name, params); }
            }
            document.addEventListener('click', function (event) {
              var link = event.target.closest && event.target.closest('a[href]');
              if (!link) { return; }
              track('select_content', {
                content_type: 'sitemap_url',
                item_id: link.getAttribute('href'),
                link_text: (link.textContent || '').trim().slice(0, 100)
              });
            });
            input.addEventListener('input', function () {
              clearTimeout(searchTimer);
              searchTimer = setTimeout(function () {
                if (input.value.trim() !== '') {
                  track('search', { search_term: input.value.trim(), search_location: 'sitemap' });
                }
              }, 800);
              var keyword = input.value.trim().toLowerCase();
              var visible = 0;
              rows.forEach(function (row) {
                var match = keyword === '' || row.textContent.toLowerCase().indexOf(keyword) !== -1;
                row.hidden = !match;
                if (match) { visible++; }
              });
              counter.textContent = visible + ' ' + label;
              empty.hidden = visible !== 0;
            });
          })();
        ]]></script>
      </body>
    </html>
  </xsl:template>

  <xsl:template match="sitemap:urlset">
    <xsl:call-template name="page">
      <xsl:with-param name="title" select="'XML Sitemap'" />
      <xsl:with-param name="count" select="count(sitemap:url)" />
      <xsl:with-param name="countLabel" select="'URL'" />
      <xsl:with-param name="subtitle">
        Ini adalah peta situs yang dibaca mesin pencari (Google, Bing, dan lainnya). Tampilan ini
        dirender lewat XSL agar enak dibaca manusia; gunakan kolom pencarian untuk menemukan halaman tertentu.
      </xsl:with-param>
      <xsl:with-param name="body">
        <xsl:variable name="hasTitle" select="boolean(sitemap:url/custom:title)" />
        <table>
          <caption class="visually-hidden">Daftar URL pada peta situs</caption>
          <thead>
            <tr>
              <th scope="col">#</th>
              <xsl:if test="$hasTitle"><th scope="col">Judul</th></xsl:if>
              <th scope="col">URL</th>
              <th scope="col">Terakhir Diubah</th>
              <th scope="col">Frekuensi</th>
              <th scope="col">Prioritas</th>
            </tr>
          </thead>
          <tbody>
            <xsl:for-each select="sitemap:url">
              <tr>
                <td class="muted"><xsl:value-of select="position()" /></td>
                <xsl:if test="$hasTitle"><td><xsl:value-of select="custom:title" /></td></xsl:if>
                <td class="url">
                  <a href="{sitemap:loc}" target="_blank" rel="noopener"><xsl:value-of select="sitemap:loc" /></a>
                  <xsl:if test="image:image">
                    <details>
                      <summary><span class="tag tag-img"><xsl:value-of select="count(image:image)" /> gambar</span></summary>
                      <ul>
                        <xsl:for-each select="image:image">
                          <li>
                            <xsl:if test="image:title"><xsl:value-of select="image:title" /><xsl:text>: </xsl:text></xsl:if>
                            <a href="{image:loc}" target="_blank" rel="noopener"><xsl:value-of select="image:loc" /></a>
                          </li>
                        </xsl:for-each>
                      </ul>
                    </details>
                  </xsl:if>
                </td>
                <td class="muted"><xsl:value-of select="substring(sitemap:lastmod, 1, 10)" /></td>
                <td><xsl:if test="sitemap:changefreq"><span class="tag"><xsl:value-of select="sitemap:changefreq" /></span></xsl:if></td>
                <td>
                  <xsl:if test="string(number(sitemap:priority)) != 'NaN'">
                    <div class="prio">
                      <div class="bar"><span style="width: {sitemap:priority * 100}%"></span></div>
                      <small class="muted"><xsl:value-of select="sitemap:priority" /></small>
                    </div>
                  </xsl:if>
                </td>
              </tr>
            </xsl:for-each>
          </tbody>
        </table>
      </xsl:with-param>
    </xsl:call-template>
  </xsl:template>

  <xsl:template match="sitemap:sitemapindex">
    <xsl:call-template name="page">
      <xsl:with-param name="title" select="'XML Sitemap Index'" />
      <xsl:with-param name="count" select="count(sitemap:sitemap)" />
      <xsl:with-param name="countLabel" select="'sitemap'" />
      <xsl:with-param name="subtitle">
        Ini adalah indeks peta situs: daftar sitemap turunan yang dibaca mesin pencari.
      </xsl:with-param>
      <xsl:with-param name="body">
        <xsl:variable name="hasTitle" select="boolean(sitemap:sitemap/custom:title)" />
        <table>
          <caption class="visually-hidden">Daftar sitemap</caption>
          <thead>
            <tr>
              <th scope="col">#</th>
              <xsl:if test="$hasTitle"><th scope="col">Judul</th></xsl:if>
              <th scope="col">Sitemap</th>
              <th scope="col">Terakhir Diubah</th>
            </tr>
          </thead>
          <tbody>
            <xsl:for-each select="sitemap:sitemap">
              <tr>
                <td class="muted"><xsl:value-of select="position()" /></td>
                <xsl:if test="$hasTitle"><td><xsl:value-of select="custom:title" /></td></xsl:if>
                <td class="url"><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc" /></a></td>
                <td class="muted"><xsl:value-of select="substring(sitemap:lastmod, 1, 10)" /></td>
              </tr>
            </xsl:for-each>
          </tbody>
        </table>
      </xsl:with-param>
    </xsl:call-template>
  </xsl:template>
</xsl:stylesheet>
