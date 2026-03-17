<?php
declare(strict_types=1);

$lapa  = "Ceļa meklētāju klubi Latvijā";
$title = "Klubi | Ceļa meklētāji";

// NOTE: Linux hosting is case-sensitive. Use the exact folder name.
require __DIR__ . "/assets/header.php";
require __DIR__ . "/Data/clubs-data.php"; // change to "/Data/clubs-data.php" if your folder is "Data"
?>

<!-- Leaflet CSS -->
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
  integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
  crossorigin=""
/>

<!-- Leaflet MarkerCluster CSS -->
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"
/>
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"
/>

<!-- Small page-specific tweaks (so you don't have to edit your big CSS right now) -->
<style>
  /* distance between closest cards and map */
  .closest-list { margin-bottom: 2rem; }
  .map-card { margin-top: 1.25rem; }
</style>

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

    <!-- Manual location (fallback / override) -->
    <div class="map-tools" style="margin-top:.25rem">
      <div style="flex:1; min-width:260px;">
        <label class="small muted" for="manualAddress"><strong>Ja lokācija nav pareiza:</strong> ievadi adresi</label>
        <input
          id="manualAddress"
          type="text"
          placeholder="Piem.: Latvija, Rīga, Brīvības iela 1"
          style="width:100%; padding:.7rem .9rem; border-radius:14px; border:1px solid rgba(0,0,0,.12);"
          autocomplete="street-address"
        >
      </div>

      <button class="btn btn-outline btn-sm" id="addrBtn" type="button">
        Meklēt adresi
      </button>

      <button class="btn btn-outline btn-sm" id="clearLocBtn" type="button" title="Atgriezties uz sākotnējo skatu">
        Notīrīt
      </button>
    </div>

    <!-- Address display -->
    <div class="card" style="margin-top:1rem; padding:1rem;">
      <div class="muted small">Izvēlētā lokācija</div>
      <div id="addrLine" style="font-weight:1000; margin-top:.25rem;">
        —
      </div>
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
// Bulletproof: keep JSON out of JS context so </script> can’t break anything
$clubsJson    = json_encode($clubs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

<!-- Leaflet MarkerCluster JS -->
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
(function () {
  "use strict";

  // ---------- Safe JSON read ----------
  function readJsonScript(id, fallback) {
    const el = document.getElementById(id);
    if (!el) return fallback;
    try { return JSON.parse(el.textContent || ""); }
    catch (e) { console.error("Failed to parse JSON from", id, e); return fallback; }
  }

  const CLUBS = readJsonScript("clubsData", []);
  const PROGRAMS = readJsonScript("programsData", {});

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

  function buildHoverHTML(c) {
    const title = escapeHtml(c.name || "Klubs");
    return `
      <div class="club-tooltip club-tooltip--hover">
        <div class="tt-title">${title}</div>
        ${programsHTML(c.programs || [], { compact: true })}
      </div>
    `;
  }

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
      (leadP && leadP !== "N/A") ? `<div class="tt-muted"><strong>Tel.:</strong> <a href="tel:${escapeAttr(leadP)}">${escapeHtml(leadP)}</a></div>` : "",
      (leadE && leadE !== "N/A") ? `<div class="tt-muted"><strong>E-pasts:</strong> <a href="mailto:${escapeAttr(leadE)}">${escapeHtml(leadE)}</a></div>` : ""
    ].filter(Boolean).join("");

    const hasCoords = isValidCoord(Number(c.lat)) && isValidCoord(Number(c.lng));
    const gmaps = hasCoords ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(c.lat + "," + c.lng)}` : "";

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

  // MarkerCluster group
  const cluster = L.markerClusterGroup({
    showCoverageOnHover: false,
    spiderfyOnMaxZoom: true,
    disableClusteringAtZoom: 15
  });

  const markerItems = [];

  CLUBS.forEach(c => {
    const lat = Number(c.lat);
    const lng = Number(c.lng);
    if (!isValidCoord(lat) || !isValidCoord(lng)) return;

    const cls = primaryProgramClass(c.programs || []);
    const m = L.marker([lat, lng], { icon: triangleIcon(cls) });

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

    cluster.addLayer(m);
    markerItems.push({ club: c, marker: m });
  });

  map.addLayer(cluster);

  // ---------- Location + Geocoding (manual fallback) ----------
  const addrLine = document.getElementById("addrLine");
  const locBtn = document.getElementById("locBtn");
  const addrBtn = document.getElementById("addrBtn");
  const clearLocBtn = document.getElementById("clearLocBtn");
  const manualAddress = document.getElementById("manualAddress");

  const radiusEl = document.getElementById("radiusKm");
  const radiusLabel = document.getElementById("radiusLabel");
  const locStatus = document.getElementById("locStatus");
  const closestList = document.getElementById("closestList");

  let userLatLng = null;
  let radiusKm = 50;

  let userMarker = null;

  function setActivePoint(lat, lng, labelText) {
    userLatLng = { lat, lng };

    if (userMarker) { map.removeLayer(userMarker); userMarker = null; }

    userMarker = L.circleMarker([lat, lng], {
      radius: 7, weight: 2, opacity: 1, fillOpacity: 0.35
    }).addTo(map).bindTooltip("Izvēlētā lokācija", { direction: "top" });

    addrLine.textContent = labelText || "—";
    map.panTo([lat, lng]);
    applyProximity();
  }

  async function reverseGeocode(lat, lng) {
    const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=18&addressdetails=1`;
    const res = await fetch(url, { method: "GET" });
    if (!res.ok) throw new Error("Reverse geocode failed");
    const data = await res.json();
    return data.display_name || "";
  }

  async function geocodeAddress(query) {
    const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=lv&q=${encodeURIComponent(query)}`;
    const res = await fetch(url, { method: "GET" });
    if (!res.ok) throw new Error("Geocode failed");
    const list = await res.json();
    if (!Array.isArray(list) || !list.length) return null;
    return {
      lat: Number(list[0].lat),
      lng: Number(list[0].lon),
      label: list[0].display_name || query
    };
  }

  // ---------- Proximity ----------
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

    // ✅ ONLY 4 closest (not 5)
    const top = results.slice(0, 4);

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

  async function requestLocation() {
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
      async (pos) => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        locStatus.textContent = `Lokācija ieslēgta (precizitāte ~${Math.round(pos.coords.accuracy)} m)`;

        let label = `Koordinātes: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        try {
          const addr = await reverseGeocode(lat, lng);
          if (addr) label = addr;
        } catch (e) {}

        setActivePoint(lat, lng, label);
      },
      (err) => {
        let msg = "Lokācija nav pieejama.";
        if (err.code === 1) msg = "Lokācija atteikta (permission denied).";
        if (err.code === 2) msg = "Lokācija nav pieejama (position unavailable).";
        if (err.code === 3) msg = "Lokācijas pieprasījums noilgts (timeout).";
        locStatus.textContent = msg;
      },
      { enableHighAccuracy: false, timeout: 12000, maximumAge: 60000 }
    );
  }

  async function useManualAddress() {
    const q = (manualAddress.value || "").trim();
    if (!q) {
      locStatus.textContent = "Ievadi adresi (piem. Latvija, Rīga, Brīvības iela 1).";
      manualAddress.focus();
      return;
    }

    locStatus.textContent = "Meklēju adresi…";
    try {
      const hit = await geocodeAddress(q);
      if (!hit || !isValidCoord(hit.lat) || !isValidCoord(hit.lng)) {
        locStatus.textContent = "Adrese netika atrasta. Pamēģini precīzāk (pilsēta + iela + nr.).";
        return;
      }

      locStatus.textContent = "Adrese atrasta. Rēķinu tuvākos klubus…";
      setActivePoint(hit.lat, hit.lng, hit.label || q);

      map.setView([hit.lat, hit.lng], 12);
    } catch (e) {
      console.error(e);
      locStatus.textContent = "Neizdevās meklēt adresi. Pamēģini vēlreiz.";
    }
  }

  function clearLocation() {
    userLatLng = null;
    addrLine.textContent = "—";
    locStatus.textContent = "Lokācija: nav ieslēgta";
    closestList.innerHTML = "";

    markerItems.forEach(it => setMarkerNear(it.marker, false));

    if (userMarker) { map.removeLayer(userMarker); userMarker = null; }

    map.setView([56.8796, 24.6032], 7);
  }

  locBtn.addEventListener("click", requestLocation);
  addrBtn.addEventListener("click", useManualAddress);
  clearLocBtn.addEventListener("click", clearLocation);

  manualAddress.addEventListener("keydown", (e) => {
    if (e.key === "Enter") useManualAddress();
  });

})();
</script>

<?php require __DIR__ . "/assets/footer.php"; ?>
