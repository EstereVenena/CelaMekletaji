<?php
declare(strict_types=1);

$lapa  = "Ceļa meklētāju klubi Latvijā";
$title = "Klubi | Ceļa meklētāji";

require __DIR__ . "/assets/header.php";
require __DIR__ . "/Data/clubs-data.php";
?>

<!-- Leaflet CSS -->
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
  integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
  crossorigin=""
/>

<section class="section section-alt">
  <div class="container">

    <header class="section-title">
      <h2>Klubi Latvijā</h2>
      <p class="muted">
        Uzliec peli uz marķiera — redzēsi kluba nosaukumu un programmas.
        Uzklikšķini — redzēsi kontaktus un detalizētu informāciju.
      </p>
    </header>

    <div class="map-tools">
      <button class="btn btn-primary btn-sm" id="locBtn" type="button">
        Atrast tuvākos klubus
      </button>

      <div class="radius">
        <label for="radiusKm"><strong>Rādiuss:</strong> <span id="radiusLabel">50</span> km</label>
        <input id="radiusKm" type="range" min="5" max="200" value="50" step="5">
      </div>

      <div class="muted small" id="locStatus">Lokācija: nav ieslēgta</div>
    </div>

    <div class="closest-list" id="closestList" aria-live="polite"></div>

    <div class="map-card">
      <div id="clubsMap" class="leaflet-map" aria-label="Klubu karte"></div>
    </div>

  </div>
</section>

<!-- Modal (details) -->
<div class="cm-modal" id="clubModal" aria-hidden="true">
  <div class="cm-modal__backdrop" data-close></div>

  <div class="cm-modal__panel" role="dialog" aria-modal="true" aria-labelledby="clubModalTitle">
    <button class="cm-modal__close" type="button" data-close aria-label="Aizvērt">×</button>
    <div id="clubModalContent"></div>
  </div>
</div>

<?php
// Put JSON into NON-JS script tags (bulletproof against </script> breaking JS)
$clubsJson = json_encode($clubs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$programsJson = json_encode($programs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<script id="clubsData" type="application/json"><?php echo htmlspecialchars($clubsJson ?? '[]', ENT_NOQUOTES, 'UTF-8'); ?></script>
<script id="programsData" type="application/json"><?php echo htmlspecialchars($programsJson ?? '{}', ENT_NOQUOTES, 'UTF-8'); ?></script>

<!-- Leaflet JS -->
<script
  src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
  integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
  crossorigin=""
></script>

<script>
(function () {
  "use strict";

  // ---------- Safe JSON read ----------
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

  if (!Array.isArray(CLUBS) || !CLUBS.length) {
    console.warn("No clubs data found or invalid.");
  }

  // ---------- Helpers ----------
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

  // compact hover list: icon + label
  function programsHTML(programCodes, { compact = false } = {}) {
    if (!Array.isArray(programCodes) || !programCodes.length) return "";

    const wrapClass = compact ? "tt-list tt-list--compact" : "tt-list";

    const items = programCodes.map(code => {
      const p = PROGRAMS[code];
      if (!p) return "";

      const icon = escapeAttr(p.icon || "");
      const label = escapeHtml(p.label || code);

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

  // Hover tooltip: ONLY name + programs (right side)
  function buildHoverHTML(c) {
    const title = escapeHtml(c.name || "Klubs");
    return `
      <div class="club-tooltip club-tooltip--hover">
        <div class="tt-title">${title}</div>
        ${programsHTML(c.programs || [], { compact: true })}
      </div>
    `;
  }

  // Modal details
  function buildDetailsHTML(c) {
    const leader = c.leader || {};

    const title  = escapeHtml(c.name || "Klubs");
    const church = c.church ? escapeHtml(c.church) : "";
    const addr   = c.address ? escapeHtml(c.address) : "";
    const leadN  = leader.name ? escapeHtml(leader.name) : "";
    const leadP  = leader.phone ? String(leader.phone) : "";
    const leadE  = leader.email ? String(leader.email) : "";

    const infoLines = [
      church ? `<div class="tt-muted">${church}</div>` : "",
      addr   ? `<div class="tt-muted">${addr}</div>` : "",
      leadN  ? `<div class="tt-muted"><strong>Vadītājs / MG:</strong> ${leadN}</div>` : "",
      leadP  ? `<div class="tt-muted"><strong>Tel.:</strong> <a href="tel:${escapeAttr(leadP)}">${escapeHtml(leadP)}</a></div>` : "",
      leadE  ? `<div class="tt-muted"><strong>E-pasts:</strong> <a href="mailto:${escapeAttr(leadE)}">${escapeHtml(leadE)}</a></div>` : ""
    ].filter(Boolean).join("");

    const hasCoords = isValidCoord(Number(c.lat)) && isValidCoord(Number(c.lng));
    const gmaps = hasCoords
      ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(c.lat + "," + c.lng)}`
      : "";

    return `
      <div class="club-details">
        <h3 id="clubModalTitle" class="club-details__title">${title}</h3>

        ${infoLines ? `<div class="club-details__meta">${infoLines}</div>` : ""}

        <div class="club-details__section">
          <div class="tt-sub" style="font-weight:900;margin:.25rem 0 .4rem">Programmas</div>
          ${programsHTML(c.programs || [], { compact: false })}
        </div>

        ${gmaps ? `
          <div class="club-details__actions">
            <a class="btn btn-outline btn-sm" href="${escapeAttr(gmaps)}" target="_blank" rel="noopener">Maršruts →</a>
          </div>
        ` : ""}
      </div>
    `;
  }

  // ---------- Modal logic ----------
  const modal = document.getElementById("clubModal");
  const modalPanel = modal.querySelector(".cm-modal__panel");
  const modalContent = document.getElementById("clubModalContent");
  let lastFocusEl = null;

  function openModal(html) {
    lastFocusEl = document.activeElement;

    modalContent.innerHTML = html;
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");

    document.body.style.overflow = "hidden";

    const closeBtn = modal.querySelector("[data-close]");
    if (closeBtn) closeBtn.focus();
  }

  function closeModal() {
    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
    modalContent.innerHTML = "";
    document.body.style.overflow = "";

    if (lastFocusEl && typeof lastFocusEl.focus === "function") lastFocusEl.focus();
    lastFocusEl = null;
  }

  modal.addEventListener("click", (e) => {
    if (e.target && e.target.hasAttribute("data-close")) closeModal();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.classList.contains("is-open")) closeModal();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key !== "Tab") return;
    if (!modal.classList.contains("is-open")) return;

    const focusables = modalPanel.querySelectorAll('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (!focusables.length) return;

    const first = focusables[0];
    const last  = focusables[focusables.length - 1];

    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault(); last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault(); first.focus();
    }
  });

  // ---------- Map init ----------
  const map = L.map("clubsMap", { zoomControl: true, scrollWheelZoom: true })
    .setView([56.8796, 24.6032], 7);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 18,
    attribution: "&copy; OpenStreetMap"
  }).addTo(map);

  const markerItems = [];

  CLUBS.forEach(c => {
    const lat = Number(c.lat);
    const lng = Number(c.lng);

    // Skip invalid coords instead of placing marker at [0,0]
    if (!isValidCoord(lat) || !isValidCoord(lng)) return;

    const cls = primaryProgramClass(c.programs || []);
    const m = L.marker([lat, lng], { icon: triangleIcon(cls) }).addTo(map);

    m.bindTooltip(buildHoverHTML(c), {
      direction: "right",
      offset: [14, 0],
      opacity: 1,
      className: "club-tooltip-wrap club-tooltip-wrap--hover",
      sticky: true
    });

    m.on("click", () => {
      m.closeTooltip();
      openModal(buildDetailsHTML(c));
    });

    markerItems.push({ club: c, marker: m });
  });

  // ---------- Proximity ----------
  let userLatLng = null;
  let radiusKm = 50;

  const locBtn = document.getElementById("locBtn");
  const radiusEl = document.getElementById("radiusKm");
  const radiusLabel = document.getElementById("radiusLabel");
  const locStatus = document.getElementById("locStatus");
  const closestList = document.getElementById("closestList");

  function distanceKm(aLat, aLng, bLat, bLng) {
    const R = 6371;
    const dLat = (bLat - aLat) * Math.PI / 180;
    const dLng = (bLng - aLng) * Math.PI / 180;
    const sLat1 = aLat * Math.PI / 180;
    const sLat2 = bLat * Math.PI / 180;

    const h = Math.sin(dLat/2) ** 2 +
      Math.cos(sLat1) * Math.cos(sLat2) * (Math.sin(dLng/2) ** 2);

    return 2 * R * Math.asin(Math.sqrt(h));
  }

  function setMarkerNear(marker, isNear) {
    const el = marker.getElement && marker.getElement();
    if (!el) return;
    el.classList.toggle("is-near", isNear);
    el.classList.toggle("is-far", !isNear);
  }

  function applyProximity() {
    if (!userLatLng) return;

    const results = markerItems.map(item => {
      const d = distanceKm(userLatLng.lat, userLatLng.lng, Number(item.club.lat), Number(item.club.lng));
      return { ...item, d };
    }).sort((a, b) => a.d - b.d);

    results.forEach(r => setMarkerNear(r.marker, r.d <= radiusKm));

    const top = results.slice(0, 5);
    closestList.innerHTML = top.map(r => {
      const gmaps = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(r.club.lat + "," + r.club.lng)}`;
      return `
        <div class="closest-card">
          <h4>${escapeHtml(r.club.name || "Klubs")}</h4>
          <div class="muted">${escapeHtml(r.club.address || "")}</div>
          <div class="km">${r.d.toFixed(1)} km</div>
          <div style="margin-top:.5rem">
            <a href="${escapeAttr(gmaps)}" target="_blank" rel="noopener">Maršruts →</a>
          </div>
        </div>
      `;
    }).join("");
  }

  function updateRadius() {
    radiusKm = Number(radiusEl.value);
    radiusLabel.textContent = String(radiusKm);
    if (userLatLng) applyProximity();
  }
  radiusEl.addEventListener("input", updateRadius);

  let userMarker = null;

  function requestLocation() {
    if (!window.isSecureContext) {
      locStatus.textContent = "Lokācija strādā tikai ar HTTPS.";
      return;
    }
    if (!navigator.geolocation) {
      locStatus.textContent = "Šī ierīce neatbalsta lokāciju.";
      return;
    }

    locStatus.textContent = "Meklēju lokāciju…";

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        userLatLng = { lat: pos.coords.latitude, lng: pos.coords.longitude };
        locStatus.textContent = `Lokācija ieslēgta (precizitāte ~${Math.round(pos.coords.accuracy)} m)`;

        if (userMarker) map.removeLayer(userMarker);

        userMarker = L.circleMarker([userLatLng.lat, userLatLng.lng], {
          radius: 7,
          weight: 2,
          opacity: 1,
          fillOpacity: 0.35
        }).addTo(map).bindTooltip("Tu esi šeit", { direction: "top" });

        map.panTo([userLatLng.lat, userLatLng.lng]);
        applyProximity();
      },
      (err) => {
        let msg = "Lokācija nav pieejama.";
        if (err.code === 1) msg = "Lokācija atteikta (permission denied).";
        if (err.code === 2) msg = "Lokācija nav pieejama (position unavailable).";
        if (err.code === 3) msg = "Lokācijas pieprasījums noilgts (timeout).";
        locStatus.textContent = msg;
        console.warn("Geolocation error:", err);
      },
      { enableHighAccuracy: false, timeout: 12000, maximumAge: 60000 }
    );
  }

  locBtn.addEventListener("click", requestLocation);

})();
</script>

<?php require __DIR__ . "/assets/footer.php"; ?>
