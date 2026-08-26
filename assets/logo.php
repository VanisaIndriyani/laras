<?php
// Output LARAS inline SVG logo — Clean Blue-Navy-White Palette:
// Deep Navy #0B1C48 · Medium Blue #233E90 · Light Blue #3B5FC7 · White · Soft Red accent (badge only)
function laras_logo($size = 42, $with_badge = true) {
    $s = (int)$size;
    // Clean BPKP-inspired Navy/Blue core palette
    $bpkp_navy      = '#0B1C48';   // Deep Navy (footer gelap resmi)
    $bpkp_blue      = '#1F3A8B';   // Medium Blue (card berita biru korporat)
    $bpkp_lightblue = '#3B5FC7';   // Biru Muda (gradient highlight)
    $bpkp_red       = '#E5232B';   // Soft Red — ONLY for small approval badge accent
    $bpkp_red2      = '#F14249';   // Red Muda (badge gradient)
    $rx = round($s * 0.22);
    echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="{$s}" height="{$s}" style="flex-shrink:0;display:block">
  <defs>
    <linearGradient id="lbg{$s}" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$bpkp_navy}"/>
      <stop offset="55%" stop-color="{$bpkp_blue}"/>
      <stop offset="100%" stop-color="{$bpkp_lightblue}"/>
    </linearGradient>
    <linearGradient id="la{$s}" x1="0" y1="1" x2="1" y2="0">
      <stop offset="0%" stop-color="{$bpkp_red}"/>
      <stop offset="100%" stop-color="{$bpkp_red2}"/>
    </linearGradient>
    <linearGradient id="lc{$s}" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="#ffffff"/>
      <stop offset="100%" stop-color="#dbeafe"/>
    </linearGradient>
  </defs>
  <rect x="8" y="8" width="184" height="184" rx="{$rx}" fill="url(#lbg{$s})"/>
  <rect x="8" y="8" width="184" height="184" rx="{$rx}" fill="none" stroke="#ffffff" stroke-opacity="0.10" stroke-width="2"/>
  <circle cx="148" cy="52" r="20" fill="none" stroke="url(#la{$s})" stroke-width="4" opacity="0.95"/>
  <circle cx="148" cy="52" r="11" fill="url(#la{$s})"/>
  <path d="M142 52 l5 5 l9 -9" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
  <g transform="translate(40, 48)">
    <rect x="0" y="6" width="80" height="98" rx="18" fill="none" stroke="url(#lc{$s})" stroke-width="6"/>
    <rect x="14" y="20" width="52" height="8" rx="3" fill="#ffffff" opacity="0.88"/>
    <circle cx="58" cy="70" r="4" fill="#ffffff"/>
    <line x1="22" y1="32" x2="22" y2="96" stroke="#ffffff" stroke-width="4" stroke-linecap="round" opacity="0.78"/>
    <g transform="translate(6, 78)">
      <path d="M16 20 Q20 2 40 2 Q60 2 64 20 L72 22 L72 36 L8 36 L8 22 Z" fill="url(#la{$s})" opacity="0.98"/>
      <line x1="24" y1="20" x2="24" y2="36" stroke="{$bpkp_navy}" stroke-width="2" stroke-linecap="round" opacity="0.5"/>
      <rect x="2" y="32" width="84" height="10" rx="4" fill="#ffffff"/>
      <circle cx="22" cy="46" r="8" fill="#0B1C48"/><circle cx="22" cy="46" r="3" fill="#ffffff"/>
      <circle cx="66" cy="46" r="8" fill="#0B1C48"/><circle cx="66" cy="46" r="3" fill="#ffffff"/>
    </g>
  </g>
  <circle cx="34" cy="154" r="3" fill="#ffffff" opacity="0.9"/>
  <circle cx="28" cy="146" r="1.5" fill="#ffffff" opacity="0.85"/>
  <circle cx="44" cy="160" r="2" fill="#ffffff" opacity="0.65"/>
</svg>
SVG;
}
?>
