<?php
/**
 * EEAT — Expert profile functions
 *
 * eeat_load($pdo, $subNicheId, $lang)  → array|null
 * eeat_social_icon($url)               → SVG string
 * eeat_render_box($profile, $lang)     → HTML string
 * eeat_jsonld_person($profile, $lang)  → JSON-LD <script> string
 */

// ── Load profile ──────────────────────────────────────────────────────────────

function eeat_load(PDO $pdo, int $subNicheId, string $lang): ?array
{
    $stmt = $pdo->prepare(
        'SELECT expert_name, social_link, bio_fr, bio_en, bio_de, bio_it
         FROM eeat_profiles WHERE sub_niche_id = :sid LIMIT 1'
    );
    $stmt->execute([':sid' => $subNicheId]);
    $row = $stmt->fetch();
    if (!$row) return null;

    $bioCol = 'bio_' . strtolower($lang);
    $bio = $row[$bioCol] ?? $row['bio_en'] ?? $row['bio_fr'] ?? '';

    return [
        'expert_name' => $row['expert_name'],
        'social_link' => $row['social_link'],
        'bio'         => $bio,
    ];
}

// ── Social icon ───────────────────────────────────────────────────────────────

function eeat_social_icon(string $url): string
{
    $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
    $host = preg_replace('/^www\./', '', $host);

    if (str_contains($host, 'twitter.com') || str_contains($host, 'x.com')) {
        return '<svg viewBox="0 0 24 24" fill="currentColor" class="eeat-social-icon"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.402 6.231H2.746l7.733-8.835L1.254 2.25H8.08l4.264 5.634 5.9-5.634Zm-1.161 17.52h1.833L7.084 4.126H5.117Z"/></svg>';
    }
    if (str_contains($host, 'instagram.com')) {
        return '<svg viewBox="0 0 24 24" fill="currentColor" class="eeat-social-icon"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069Zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073Zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162S8.597 18.163 12 18.163s6.162-2.759 6.162-6.162S15.403 5.838 12 5.838Zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4Zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44Z"/></svg>';
    }
    if (str_contains($host, 'linkedin.com')) {
        return '<svg viewBox="0 0 24 24" fill="currentColor" class="eeat-social-icon"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286ZM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065Zm1.782 13.019H3.555V9h3.564v11.452ZM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003Z"/></svg>';
    }
    if (str_contains($host, 'facebook.com')) {
        return '<svg viewBox="0 0 24 24" fill="currentColor" class="eeat-social-icon"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073Z"/></svg>';
    }
    if (str_contains($host, 'youtube.com')) {
        return '<svg viewBox="0 0 24 24" fill="currentColor" class="eeat-social-icon"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814ZM9.545 15.568V8.432L15.818 12l-6.273 3.568Z"/></svg>';
    }
    // Lien générique
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="eeat-social-icon"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';
}

// ── Social platform name ──────────────────────────────────────────────────────

function eeat_social_label(string $url): string
{
    $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
    $host = preg_replace('/^www\./', '', $host);
    if (str_contains($host, 'twitter.com') || str_contains($host, 'x.com')) return 'X / Twitter';
    if (str_contains($host, 'instagram.com')) return 'Instagram';
    if (str_contains($host, 'linkedin.com'))  return 'LinkedIn';
    if (str_contains($host, 'facebook.com'))  return 'Facebook';
    if (str_contains($host, 'youtube.com'))   return 'YouTube';
    return 'Website';
}

// ── Labels i18n ───────────────────────────────────────────────────────────────

function eeat_labels(string $lang): array
{
    $map = [
        'EN' => ['title' => 'Expert opinion', 'follow' => 'Follow'],
        'FR' => ['title' => 'Avis expert',    'follow' => 'Suivre'],
        'DE' => ['title' => 'Expertenurteil', 'follow' => 'Folgen'],
        'IT' => ['title' => 'Parere esperto', 'follow' => 'Segui'],
    ];
    return $map[strtoupper($lang)] ?? $map['EN'];
}

// ── Render HTML box ───────────────────────────────────────────────────────────

function eeat_render_box(array $profile, string $lang): string
{
    $name    = htmlspecialchars($profile['expert_name'], ENT_QUOTES);
    $bio     = htmlspecialchars($profile['bio'],         ENT_QUOTES);
    $social  = htmlspecialchars($profile['social_link'], ENT_QUOTES);
    $icon    = eeat_social_icon($profile['social_link']);
    $label   = eeat_social_label($profile['social_link']);
    $lbls    = eeat_labels($lang);
    $initial = mb_strtoupper(mb_substr($profile['expert_name'], 0, 1));

    return <<<HTML
<div class="eeat-box">
  <style>
    .eeat-box{background:#fff8f0;border:1px solid #ffe0b0;border-radius:10px;padding:1.1rem 1.25rem;margin:1.75rem 0;font-size:.88rem}
    .eeat-box-header{display:flex;align-items:center;gap:.75rem;margin-bottom:.65rem}
    .eeat-avatar{width:42px;height:42px;border-radius:50%;background:#e85d26;color:#fff;font-weight:800;font-size:1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;line-height:1}
    .eeat-meta{min-width:0}
    .eeat-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#c94e1e;margin-bottom:.15rem}
    .eeat-name{font-weight:700;color:#111;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .eeat-bio{color:#444;line-height:1.6;margin-bottom:.75rem}
    .eeat-social{display:inline-flex;align-items:center;gap:.4rem;color:#c94e1e;font-weight:600;font-size:.8rem;text-decoration:none;transition:opacity .15s}
    .eeat-social:hover{opacity:.75}
    .eeat-social-icon{width:14px;height:14px;flex-shrink:0}
  </style>
  <div class="eeat-box-header">
    <div class="eeat-avatar" aria-hidden="true">{$initial}</div>
    <div class="eeat-meta">
      <p class="eeat-label">{$lbls['title']}</p>
      <p class="eeat-name">{$name}</p>
    </div>
  </div>
  <p class="eeat-bio">{$bio}</p>
  <a href="{$social}" target="_blank" rel="noopener noreferrer" class="eeat-social">
    {$icon}
    {$lbls['follow']} · {$label}
  </a>
</div>
HTML;
}

// ── JSON-LD Person ────────────────────────────────────────────────────────────

function eeat_jsonld_person(array $profile): string
{
    $person = [
        '@context'   => 'https://schema.org',
        '@type'      => 'Person',
        'name'       => $profile['expert_name'],
        'description'=> $profile['bio'],
        'sameAs'     => [$profile['social_link']],
    ];
    $json = json_encode($person, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    return "<script type=\"application/ld+json\">\n{$json}\n</script>\n";
}
