<?php
declare(strict_types=1);

$lapa  = "Klubi Latvijā";
$title = "Klubi | Ceļa meklētāji";

$selectedClubId = isset($_GET['id']) ? (string)$_GET['id'] : '';

require __DIR__ . "/../includes/templates/header.php";
require __DIR__ . "/../data/clubs-data.php";

$clubCount = is_array($clubs ?? null) ? count($clubs) : 0;
?>

<!-- Leaflet CSS -->
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
  integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
  crossorigin=""
>

<!-- Leaflet MarkerCluster CSS -->
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"
>
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"
>

<style>
/* ===============================
   CLUBS PAGE
================================ */

.clubs-hero {
    position: relative;
    overflow: hidden;
    padding: 5rem 0 4rem;
    background:
        radial-gradient(circle at top left, rgba(244, 197, 66, 0.28), transparent 35%),
        radial-gradient(circle at bottom right, rgba(45, 106, 79, 0.45), transparent 42%),
        linear-gradient(135deg, #10241b 0%, #173626 58%, #224e38 100%);
    color: #fff;
}

.clubs-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.045) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.045) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: 0.45;
}

.clubs-hero-inner {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: 3rem;
    align-items: center;
}

.clubs-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.48rem 0.9rem;
    margin-bottom: 1.1rem;
    border-radius: 999px;
    background: rgba(255,255,255,0.13);
    color: #f4c542;
    font-weight: 850;
    backdrop-filter: blur(10px);
}

.clubs-hero h1 {
    margin: 0;
    font-size: clamp(2.5rem, 5vw, 4.8rem);
    line-height: 1;
    letter-spacing: -0.055em;
}

.clubs-hero p {
    max-width: 760px;
    margin: 1.2rem 0 0;
    color: rgba(255,255,255,0.86);
    font-size: 1.1rem;
    line-height: 1.75;
}

.clubs-hero-card {
    padding: 2rem;
    border-radius: 2rem;
    background: rgba(255,255,255,0.9);
    color: #173626;
    border: 1px solid rgba(255,255,255,0.5);
    box-shadow: 0 28px 70px rgba(0,0,0,0.22);
    backdrop-filter: blur(14px);
}

.clubs-hero-card-icon {
    width: 62px;
    height: 62px;
    display: grid;
    place-items: center;
    margin-bottom: 1rem;
    border-radius: 1.2rem;
    background: #173626;
    color: #f4c542;
    font-size: 1.55rem;
}

.clubs-hero-card h3 {
    margin: 0 0 0.8rem;
    font-size: 1.55rem;
    letter-spacing: -0.03em;
}

.clubs-hero-card p {
    margin: 0;
    color: #526358;
}

.clubs-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.8rem;
    margin-top: 1.3rem;
}

.clubs-stat {
    padding: 1rem;
    border-radius: 1.1rem;
    background: #fff;
    box-shadow: 0 12px 28px rgba(0,0,0,0.08);
}

.clubs-stat strong {
    display: block;
    font-size: 1.45rem;
    color: #173626;
}

.clubs-stat span {
    color: #6c7a70;
    font-size: 0.86rem;
}

/* Toolbar */

.clubs-panel {
    display: grid;
    gap: 1rem;
    margin-bottom: 1.4rem;
}

.map-tools {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    gap: 1rem;
    padding: 1rem;
    border-radius: 1.5rem;
    background: #fff;
    border: 1px solid rgba(23,54,38,0.08);
    box-shadow: 0 14px 36px rgba(0,0,0,0.06);
}

.map-tools-main {
    align-items: center;
}

.radius {
    min-width: 230px;
}

.radius label {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.45rem;
    color: #526358;
}

.radius input {
    width: 100%;
    accent-color: #173626;
}

.loc-status {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    margin-left: auto;
    padding: 0.55rem 0.8rem;
    border-radius: 999px;
    background: #f8f6ef;
    color: #526358;
    font-weight: 750;
}

.loc-status i {
    color: #d6a823;
}

.address-field {
    flex: 1;
    min-width: 260px;
}

.address-field label {
    display: block;
    margin-bottom: 0.4rem;
    color: #526358;
}

.address-field input {
    width: 100%;
    padding: 0.8rem 0.95rem;
    border-radius: 1rem;
    border: 1px solid rgba(23,54,38,0.14);
    outline: none;
    transition: 0.2s ease;
}

.address-field input:focus {
    border-color: #d6a823;
    box-shadow: 0 0 0 4px rgba(244,197,66,0.16);
}

.location-card {
    padding: 1.1rem;
    border-radius: 1.4rem;
    background:
        linear-gradient(135deg, #fff, #f8f6ef);
    border: 1px solid rgba(23,54,38,0.08);
    box-shadow: 0 14px 36px rgba(0,0,0,0.06);
}

.location-card .small {
    color: #7a887d;
}

.location-card strong {
    display: block;
    margin-top: 0.25rem;
    color: #173626;
}

/* Closest list */

.closest-list {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.9rem;
    margin: 1.4rem 0;
}

.closest-list:empty {
    display: none;
}

.closest-card {
    padding: 1rem;
    border-radius: 1.3rem;
    background: #ffffff;
    border: 1px solid rgba(23,54,38,0.08);
    box-shadow: 0 14px 36px rgba(0,0,0,0.06);
}

.closest-card h4 {
    margin: 0 0 0.45rem;
    color: #173626;
    font-size: 1rem;
}

.closest-card .km {
    display: inline-flex;
    margin-top: 0.65rem;
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    background: #173626;
    color: #f4c542;
    font-weight: 900;
    font-size: 0.86rem;
}

.closest-card a {
    color: #173626;
    font-weight: 900;
    text-decoration: none;
}

.closest-card a:hover {
    color: #d6a823;
}

/* Map */

.map-card {
    overflow: hidden;
    border-radius: 2rem;
    background: #fff;
    border: 1px solid rgba(23,54,38,0.08);
    box-shadow: 0 22px 60px rgba(0,0,0,0.1);
}

.leaflet-map {
    width: 100%;
    height: 620px;
    min-height: 420px;
}

.leaflet-container {
    font-family: inherit;
}

/* Markers */

.tri-marker {
    background: transparent;
    border: none;
}

.tri-marker .tri {
    display: block;
    width: 0;
    height: 0;
    border-left: 11px solid transparent;
    border-right: 11px solid transparent;
    border-bottom: 22px solid #173626;
    filter: drop-shadow(0 6px 8px rgba(0,0,0,0.25));
    transform: rotate(180deg);
}

.tri-marker.pm .tri {
    border-bottom-color: #d6a823;
}

.tri-marker.cm .tri {
    border-bottom-color: #173626;
}

.tri-marker.mg .tri {
    border-bottom-color: #1e4fa1;
}

.tri-marker.v .tri {
    border-bottom-color: #c62828;
}

.tri-marker.is-near .tri {
    filter: drop-shadow(0 0 10px rgba(244,197,66,0.95));
}

.tri-marker.is-far {
    opacity: 0.78;
}

/* Tooltip */

.club-tooltip-wrap {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
}

.club-tooltip {
    min-width: 190px;
    padding: 0.8rem;
    border-radius: 1rem;
    background: #ffffff;
    border: 1px solid rgba(23,54,38,0.1);
    box-shadow: 0 16px 42px rgba(0,0,0,0.18);
}

.tt-title {
    color: #173626;
    font-weight: 950;
    margin-bottom: 0.5rem;
}

.tt-list {
    display: grid;
    gap: 0.55rem;
}

.tt-list--compact {
    gap: 0.35rem;
}

.tt-row {
    display: flex;
    align-items: center;
    gap: 0.55rem;
}

.tt-ico {
    width: 28px;
    height: 28px;
    object-fit: contain;
}

.tt-ico--compact {
    width: 22px;
    height: 22px;
}

.tt-label {
    color: #526358;
    font-weight: 750;
}

.tt-label--compact {
    font-size: 0.9rem;
}

.tt-muted {
    color: #526358;
    line-height: 1.55;
    margin-bottom: 0.28rem;
}

.tt-muted a {
    color: #173626;
    font-weight: 850;
}

/* Modal */

.cm-modal {
    position: fixed;
    inset: 0;
    z-index: 2600;
    display: none;
}

.cm-modal.is-open {
    display: block;
}

.cm-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(8,18,13,0.74);
    backdrop-filter: blur(8px);
}

.cm-modal__panel {
    position: relative;
    z-index: 1;
    width: min(680px, calc(100% - 2rem));
    max-height: calc(100vh - 2rem);
    overflow: auto;
    margin: 1rem auto;
    top: 50%;
    transform: translateY(-50%);
    border-radius: 2rem;
    background: #ffffff;
    box-shadow: 0 34px 100px rgba(0,0,0,0.38);
}

.cm-modal__close {
    position: sticky;
    top: 1rem;
    float: right;
    z-index: 3;
    width: 44px;
    height: 44px;
    margin: 1rem 1rem 0 0;
    border: none;
    border-radius: 1rem;
    background: #173626;
    color: #f4c542;
    font-size: 1.4rem;
    cursor: pointer;
}

.club-details {
    padding: 2.2rem;
}

.club-details__title {
    margin: 0 3rem 1rem 0;
    color: #173626;
    font-size: clamp(1.6rem, 4vw, 2.25rem);
    letter-spacing: -0.04em;
}

.club-details__meta {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 1.2rem;
    background: #f8f6ef;
}

.club-details__section {
    padding: 1rem;
    border-radius: 1.2rem;
    border: 1px solid rgba(23,54,38,0.08);
}

.club-details__actions {
    margin-top: 1rem;
}

/* Responsive */

@media (max-width: 980px) {
    .clubs-hero-inner {
        grid-template-columns: 1fr;
    }

    .closest-list {
        grid-template-columns: repeat(2, 1fr);
    }

    .leaflet-map {
        height: 540px;
    }
}

@media (max-width: 640px) {
    .clubs-hero {
        padding: 3.6rem 0 3rem;
    }

    .map-tools {
        align-items: stretch;
    }

    .map-tools .btn {
        width: 100%;
        justify-content: center;
    }

    .loc-status {
        width: 100%;
        margin-left: 0;
        justify-content: center;
        border-radius: 1rem;
    }

    .radius {
        width: 100%;
    }

    .closest-list {
        grid-template-columns: 1fr;
    }

    .leaflet-map {
        height: 460px;
    }

    .cm-modal__panel {
        width: calc(100% - 1rem);
        border-radius: 1.4rem;
    }

    .club-details {
        padding: 1.4rem;
    }
}
</style>

<section class="clubs-hero">
  <div class="container">
    <div class="clubs-hero-inner">
      <div>
        <div class="clubs-kicker">
          <i class="fa-solid fa-location-dot"></i>
          Klubi un atrašanās vietas
        </div>

        <h1>Klubi Latvijā</h1>

        <p>
          Atrodi tuvāko “Ceļa meklētāju” klubu, apskati programmas, kontaktinformāciju
          un atver maršrutu kartē. Beidzot karte, kas nedzen cilvēku eksistenciālā izmisumā.
        </p>
      </div>

      <aside class="clubs-hero-card">
        <div class="clubs-hero-card-icon">
          <i class="fa-solid fa-map-location-dot"></i>
        </div>

        <h3>Atrodi savu klubu</h3>

        <p>
          Vari izmantot ierīces lokāciju vai ievadīt adresi manuāli.
          Sistēma parādīs tuvākos klubus un aprēķinās attālumu.
        </p>

        <div class="clubs-stats">
          <div class="clubs-stat">
            <strong><?= (int)$clubCount; ?></strong>
            <span>klubi sarakstā</span>
          </div>

          <div class="clubs-stat">
            <strong>LV</strong>
            <span>visā Latvijā</span>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">

    <div class="clubs-panel">

      <div class="map-tools map-tools-main">
        <button class="btn btn-primary btn-sm" id="locBtn" type="button">
          <i class="fa-solid fa-location-crosshairs"></i>
          Atrast tuvākos klubus
        </button>

        <div class="radius">
          <label for="radiusKm">
            <strong>Rādiuss</strong>
            <span><span id="radiusLabel">50</span> km</span>
          </label>
          <input id="radiusKm" type="range" min="5" max="200" value="50" step="5">
        </div>

        <div class="loc-status" id="locStatus">
          <i class="fa-solid fa-circle-info"></i>
          <span>Lokācija: nav ieslēgta</span>
        </div>
      </div>

      <div class="map-tools">
        <div class="address-field">
          <label for="manualAddress">
            <strong>Ja lokācija nav pareiza:</strong> ievadi adresi
          </label>

          <input
            id="manualAddress"
            type="text"
            placeholder="Piem.: Latvija, Rīga, Brīvības iela 1"
            autocomplete="street-address"
          >
        </div>

        <button class="btn btn-outline btn-sm" id="addrBtn" type="button">
          <i class="fa-solid fa-magnifying-glass"></i>
          Meklēt adresi
        </button>

        <button class="btn btn-outline btn-sm" id="clearLocBtn" type="button">
          <i class="fa-solid fa-rotate-left"></i>
          Notīrīt
        </button>
      </div>

      <div class="location-card">
        <div class="small">Izvēlētā lokācija</div>
        <strong id="addrLine">—</strong>
      </div>

    </div>

    <div class="closest-list" id="closestList" aria-live="polite"></div>

    <div class="map-card">
      <div id="clubsMap" class="leaflet-map" aria-label="Klubu karte"></div>
    </div>

  </div>
</section>

<div class="cm-modal" id="clubModal" aria-hidden="true">
  <div class="cm-modal__backdrop" data-close></div>

  <div class="cm-modal__panel" role="dialog" aria-modal="true" aria-labelledby="clubModalTitle">
    <button class="cm-modal__close" type="button" data-close aria-label="Aizvērt">×</button>
    <div id="clubModalContent"></div>
  </div>
</div>

<?php
$clubsJson = json_encode($clubs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$programsJson = json_encode($programs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<script id="clubsData" type="application/json"><?= htmlspecialchars($clubsJson ?: '[]', ENT_NOQUOTES, 'UTF-8'); ?></script>
<script id="programsData" type="application/json"><?= htmlspecialchars($programsJson ?: '{}', ENT_NOQUOTES, 'UTF-8'); ?></script>
<script id="selectedClubId" type="application/json"><?= json_encode($selectedClubId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

<!-- Leaflet JS -->
<script
  src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
  integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
  crossorigin=""
></script>

<!-- Leaflet MarkerCluster JS -->
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
(function () {
  "use strict";

  function readJsonScript(id, fallback) {
    const el = document.getElementById(id);
    if (!el) return fallback;

    try {
      return JSON.parse(el.textContent || "");
    } catch (e) {
      console.error("Failed to parse JSON from", id, e);
      return fallback;
    }
  }

  const CLUBS = readJsonScript("clubsData", []);
  const PROGRAMS = readJsonScript("programsData", {});
  const SELECTED_CLUB_ID = readJsonScript("selectedClubId", "");

  function escapeHtml(str) {
    return String(str ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function escapeAttr(str) {
    return String(str ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function isValidCoord(n) {
    return Number.isFinite(n) && Math.abs(n) > 0.000001;
  }

  function setStatus(text, icon = "fa-circle-info") {
    const locStatus = document.getElementById("locStatus");
    if (!locStatus) return;

    locStatus.innerHTML = `
      <i class="fa-solid ${escapeAttr(icon)}"></i>
      <span>${escapeHtml(text)}</span>
    `;
  }

  function primaryProgramClass(programCodes) {
    const set = new Set(programCodes || []);

    if (set.has("CM")) return "cm";
    if (set.has("PM")) return "pm";
    if (set.has("MG")) return "mg";
    if (set.has("V"))  return "v";

    return "pm";
  }

  function triangleIcon(cls) {
    return L.divIcon({
      className: `tri-marker ${cls} is-far`,
      html: `<span class="tri"></span>`,
      iconSize: [22, 22],
      iconAnchor: [11, 22]
    });
  }

  function programsHTML(programCodes, { compact = false } = {}) {
    if (!Array.isArray(programCodes) || !programCodes.length) {
      return `<div class="tt-muted">Programmas nav norādītas.</div>`;
    }

    const wrapClass = compact ? "tt-list tt-list--compact" : "tt-list";

    const items = programCodes.map(code => {
      const program = PROGRAMS[code];

      if (!program) return "";

      const icon = escapeAttr(program.icon || "");
      const label = escapeHtml(program.label || code);

      return `
        <div class="tt-row ${compact ? "tt-row--compact" : ""}">
          ${icon
            ? `<img class="tt-ico ${compact ? "tt-ico--compact" : ""}" src="${icon}" alt="${escapeHtml(code)}">`
            : `<span class="tt-ico-fallback" aria-hidden="true"></span>`
          }
          <div class="tt-label ${compact ? "tt-label--compact" : ""}">${label}</div>
        </div>
      `;
    }).join("");

    return `<div class="${wrapClass}">${items}</div>`;
  }

  function buildHoverHTML(club) {
    const title = escapeHtml(club.name || "Klubs");

    return `
      <div class="club-tooltip club-tooltip--hover">
        <div class="tt-title">${title}</div>
        ${programsHTML(club.programs || [], { compact: true })}
      </div>
    `;
  }

  function buildDetailsHTML(club) {
    const leader = club.leader || {};
    const title  = escapeHtml(club.name || "Klubs");
    const church = club.church ? escapeHtml(club.church) : "";
    const addr   = club.address ? escapeHtml(club.address) : "";

    const leadName  = leader.name ? escapeHtml(leader.name) : "";
    const leadPhone = leader.phone ? String(leader.phone) : "";
    const leadEmail = leader.email ? String(leader.email) : "";

    const infoLines = [
      church ? `<div class="tt-muted"><strong>Draudze:</strong> ${church}</div>` : "",
      addr   ? `<div class="tt-muted"><strong>Adrese:</strong> ${addr}</div>` : "",
      leadName ? `<div class="tt-muted"><strong>Vadītājs / MG:</strong> ${leadName}</div>` : "",
      (leadPhone && leadPhone !== "N/A")
        ? `<div class="tt-muted"><strong>Tel.:</strong> <a href="tel:${escapeAttr(leadPhone)}">${escapeHtml(leadPhone)}</a></div>`
        : "",
      (leadEmail && leadEmail !== "N/A")
        ? `<div class="tt-muted"><strong>E-pasts:</strong> <a href="mailto:${escapeAttr(leadEmail)}">${escapeHtml(leadEmail)}</a></div>`
        : ""
    ].filter(Boolean).join("");

    const hasCoords = isValidCoord(Number(club.lat)) && isValidCoord(Number(club.lng));

    const gmaps = hasCoords
      ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(club.lat + "," + club.lng)}`
      : "";

    return `
      <div class="club-details">
        <h3 id="clubModalTitle" class="club-details__title">${title}</h3>

        ${infoLines ? `<div class="club-details__meta">${infoLines}</div>` : ""}

        <div class="club-details__section">
          <div class="tt-sub" style="font-weight:900;margin:.25rem 0 .6rem;color:#173626;">
            Programmas
          </div>
          ${programsHTML(club.programs || [], { compact: false })}
        </div>

        ${gmaps ? `
          <div class="club-details__actions">
            <a class="btn btn-primary btn-sm" href="${escapeAttr(gmaps)}" target="_blank" rel="noopener">
              Atvērt maršrutu
              <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </a>
          </div>
        ` : ""}
      </div>
    `;
  }

  const modal = document.getElementById("clubModal");
  const modalPanel = modal ? modal.querySelector(".cm-modal__panel") : null;
  const modalContent = document.getElementById("clubModalContent");

  let lastFocusEl = null;

  function openModal(html) {
    if (!modal || !modalContent) return;

    lastFocusEl = document.activeElement;
    modalContent.innerHTML = html;
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("nav-lock");

    const closeBtn = modal.querySelector("[data-close]");

    if (closeBtn) {
      closeBtn.focus();
    }
  }

  function closeModal() {
    if (!modal || !modalContent) return;

    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
    modalContent.innerHTML = "";
    document.body.classList.remove("nav-lock");

    if (lastFocusEl && typeof lastFocusEl.focus === "function") {
      lastFocusEl.focus();
    }

    lastFocusEl = null;
  }

  if (modal) {
    modal.addEventListener("click", (event) => {
      if (event.target && event.target.hasAttribute("data-close")) {
        closeModal();
      }
    });
  }

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && modal && modal.classList.contains("is-open")) {
      closeModal();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key !== "Tab") return;
    if (!modal || !modal.classList.contains("is-open") || !modalPanel) return;

    const focusables = modalPanel.querySelectorAll(
      'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );

    if (!focusables.length) return;

    const first = focusables[0];
    const last  = focusables[focusables.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  const map = L.map("clubsMap", {
    zoomControl: true,
    scrollWheelZoom: true
  }).setView([56.8796, 24.6032], 7);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 18,
    attribution: "&copy; OpenStreetMap"
  }).addTo(map);

  const cluster = L.markerClusterGroup({
    showCoverageOnHover: false,
    spiderfyOnMaxZoom: true,
    disableClusteringAtZoom: 15
  });

  const markerItems = [];
  const markerByClubId = new Map();

  let focusCircle = null;

  CLUBS.forEach(club => {
    const lat = Number(club.lat);
    const lng = Number(club.lng);

    if (!isValidCoord(lat) || !isValidCoord(lng)) return;

    const markerClass = primaryProgramClass(club.programs || []);
    const marker = L.marker([lat, lng], {
      icon: triangleIcon(markerClass)
    });

    marker.bindTooltip(buildHoverHTML(club), {
      direction: "right",
      offset: [14, 0],
      opacity: 1,
      className: "club-tooltip-wrap club-tooltip-wrap--hover",
      sticky: true
    });

    marker.on("click", () => {
      marker.closeTooltip();
      openModal(buildDetailsHTML(club));
    });

    cluster.addLayer(marker);
    markerItems.push({ club, marker });

    if (club.id != null && club.id !== "") {
      markerByClubId.set(String(club.id), { club, marker });
    }
  });

  map.addLayer(cluster);

  function focusSelectedClub(clubId) {
    if (!clubId || !markerByClubId.has(String(clubId))) return;

    const item = markerByClubId.get(String(clubId));
    const lat = Number(item.club.lat);
    const lng = Number(item.club.lng);

    if (!isValidCoord(lat) || !isValidCoord(lng)) return;

    map.setView([lat, lng], 14);

    if (focusCircle) {
      map.removeLayer(focusCircle);
    }

    focusCircle = L.circle([lat, lng], {
      radius: 2000,
      weight: 2,
      opacity: 0.7,
      fillOpacity: 0.08
    }).addTo(map);

    setTimeout(() => {
      openModal(buildDetailsHTML(item.club));
      item.marker.openTooltip();
    }, 300);

    setTimeout(() => {
      item.marker.closeTooltip();
    }, 2500);
  }

  const addrLine = document.getElementById("addrLine");
  const locBtn = document.getElementById("locBtn");
  const addrBtn = document.getElementById("addrBtn");
  const clearLocBtn = document.getElementById("clearLocBtn");
  const manualAddress = document.getElementById("manualAddress");

  const radiusEl = document.getElementById("radiusKm");
  const radiusLabel = document.getElementById("radiusLabel");
  const closestList = document.getElementById("closestList");

  let userLatLng = null;
  let radiusKm = 50;
  let userMarker = null;

  function setActivePoint(lat, lng, labelText) {
    userLatLng = { lat, lng };

    if (userMarker) {
      map.removeLayer(userMarker);
      userMarker = null;
    }

    userMarker = L.circleMarker([lat, lng], {
      radius: 8,
      weight: 2,
      color: "#173626",
      fillColor: "#f4c542",
      opacity: 1,
      fillOpacity: 0.6
    }).addTo(map).bindTooltip("Izvēlētā lokācija", {
      direction: "top"
    });

    if (addrLine) {
      addrLine.textContent = labelText || "—";
    }

    map.panTo([lat, lng]);
    applyProximity();
  }

  async function reverseGeocode(lat, lng) {
    const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=18&addressdetails=1`;

    const response = await fetch(url, {
      method: "GET"
    });

    if (!response.ok) {
      throw new Error("Reverse geocode failed");
    }

    const data = await response.json();

    return data.display_name || "";
  }

  async function geocodeAddress(query) {
    const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=lv&q=${encodeURIComponent(query)}`;

    const response = await fetch(url, {
      method: "GET"
    });

    if (!response.ok) {
      throw new Error("Geocode failed");
    }

    const list = await response.json();

    if (!Array.isArray(list) || !list.length) {
      return null;
    }

    return {
      lat: Number(list[0].lat),
      lng: Number(list[0].lon),
      label: list[0].display_name || query
    };
  }

  function distanceKm(aLat, aLng, bLat, bLng) {
    const R = 6371;
    const dLat = (bLat - aLat) * Math.PI / 180;
    const dLng = (bLng - aLng) * Math.PI / 180;
    const sLat1 = aLat * Math.PI / 180;
    const sLat2 = bLat * Math.PI / 180;

    const h = Math.sin(dLat / 2) ** 2 +
      Math.cos(sLat1) * Math.cos(sLat2) * (Math.sin(dLng / 2) ** 2);

    return 2 * R * Math.asin(Math.sqrt(h));
  }

  function setMarkerNear(marker, isNear) {
    const el = marker.getElement && marker.getElement();

    if (!el) return;

    el.classList.toggle("is-near", isNear);
    el.classList.toggle("is-far", !isNear);
  }

  function applyProximity() {
    if (!userLatLng || !closestList) return;

    const results = markerItems.map(item => {
      const distance = distanceKm(
        userLatLng.lat,
        userLatLng.lng,
        Number(item.club.lat),
        Number(item.club.lng)
      );

      return {
        ...item,
        distance
      };
    }).sort((a, b) => a.distance - b.distance);

    results.forEach(result => {
      setMarkerNear(result.marker, result.distance <= radiusKm);
    });

    const top = results.slice(0, 4);

    closestList.innerHTML = top.map(result => {
      const club = result.club;
      const gmaps = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(club.lat + "," + club.lng)}`;

      return `
        <div class="closest-card">
          <h4>${escapeHtml(club.name || "Klubs")}</h4>
          <div class="muted">${escapeHtml(club.address || "")}</div>
          <div class="km">${result.distance.toFixed(1)} km</div>
          <div style="margin-top:.65rem">
            <a href="${escapeAttr(gmaps)}" target="_blank" rel="noopener">
              Maršruts →
            </a>
          </div>
        </div>
      `;
    }).join("");
  }

  function updateRadius() {
    if (!radiusEl || !radiusLabel) return;

    radiusKm = Number(radiusEl.value);
    radiusLabel.textContent = String(radiusKm);

    if (userLatLng) {
      applyProximity();
    }
  }

  if (radiusEl) {
    radiusEl.addEventListener("input", updateRadius);
  }

  async function requestLocation() {
    if (!window.isSecureContext) {
      setStatus("Lokācija strādā tikai ar HTTPS.", "fa-lock");
      return;
    }

    if (!navigator.geolocation) {
      setStatus("Šī ierīce neatbalsta lokāciju.", "fa-triangle-exclamation");
      return;
    }

    setStatus("Meklēju lokāciju…", "fa-spinner");

    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        setStatus(`Lokācija ieslēgta (~${Math.round(pos.coords.accuracy)} m)`, "fa-check");

        let label = `Koordinātes: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;

        try {
          const addr = await reverseGeocode(lat, lng);

          if (addr) {
            label = addr;
          }
        } catch (e) {}

        setActivePoint(lat, lng, label);
      },
      (err) => {
        let msg = "Lokācija nav pieejama.";

        if (err.code === 1) msg = "Lokācija atteikta.";
        if (err.code === 2) msg = "Lokācija nav pieejama.";
        if (err.code === 3) msg = "Lokācijas pieprasījums noilgts.";

        setStatus(msg, "fa-triangle-exclamation");
      },
      {
        enableHighAccuracy: false,
        timeout: 12000,
        maximumAge: 60000
      }
    );
  }

  async function useManualAddress() {
    if (!manualAddress) return;

    const query = (manualAddress.value || "").trim();

    if (!query) {
      setStatus("Ievadi adresi.", "fa-keyboard");
      manualAddress.focus();
      return;
    }

    setStatus("Meklēju adresi…", "fa-spinner");

    try {
      const hit = await geocodeAddress(query);

      if (!hit || !isValidCoord(hit.lat) || !isValidCoord(hit.lng)) {
        setStatus("Adrese netika atrasta. Pamēģini precīzāk.", "fa-triangle-exclamation");
        return;
      }

      setStatus("Adrese atrasta. Rēķinu tuvākos klubus…", "fa-check");

      setActivePoint(hit.lat, hit.lng, hit.label || query);
      map.setView([hit.lat, hit.lng], 12);
    } catch (e) {
      console.error(e);
      setStatus("Neizdevās meklēt adresi. Pamēģini vēlreiz.", "fa-triangle-exclamation");
    }
  }

  function clearLocation() {
    userLatLng = null;

    if (addrLine) {
      addrLine.textContent = "—";
    }

    setStatus("Lokācija: nav ieslēgta", "fa-circle-info");

    if (closestList) {
      closestList.innerHTML = "";
    }

    markerItems.forEach(item => {
      setMarkerNear(item.marker, false);
    });

    if (userMarker) {
      map.removeLayer(userMarker);
      userMarker = null;
    }

    if (focusCircle) {
      map.removeLayer(focusCircle);
      focusCircle = null;
    }

    if (manualAddress) {
      manualAddress.value = "";
    }

    map.setView([56.8796, 24.6032], 7);
    closeModal();
  }

  if (locBtn) {
    locBtn.addEventListener("click", requestLocation);
  }

  if (addrBtn) {
    addrBtn.addEventListener("click", useManualAddress);
  }

  if (clearLocBtn) {
    clearLocBtn.addEventListener("click", clearLocation);
  }

  if (manualAddress) {
    manualAddress.addEventListener("keydown", (event) => {
      if (event.key === "Enter") {
        useManualAddress();
      }
    });
  }

  if (SELECTED_CLUB_ID) {
    focusSelectedClub(SELECTED_CLUB_ID);
  }
})();
</script>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>