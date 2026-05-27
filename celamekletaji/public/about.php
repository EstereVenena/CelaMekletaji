<?php
$lapa  = "Par mums";
$title = "Par mums | Ceļa meklētāji";

require __DIR__ . "/../includes/templates/header.php";

$base = BASE_URL;
?>

<style>
/* ===============================
   ABOUT PAGE
================================ */

.about-hero {
    position: relative;
    overflow: hidden;
    padding: 5.5rem 0 4.5rem;
    background:
        radial-gradient(circle at top left, rgba(244, 197, 66, 0.26), transparent 35%),
        radial-gradient(circle at bottom right, rgba(45, 106, 79, 0.45), transparent 40%),
        linear-gradient(135deg, #10241b 0%, #173626 58%, #224e38 100%);
    color: #ffffff;
}

.about-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.045) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.045) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: 0.45;
}

.about-hero-inner {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: 3rem;
    align-items: center;
}

.about-kicker {
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

.about-hero h1 {
    margin: 0;
    font-size: clamp(2.6rem, 6vw, 5rem);
    line-height: 0.95;
    letter-spacing: -0.055em;
}

.about-hero .lead {
    max-width: 680px;
    margin: 1.4rem 0 0;
    color: rgba(255,255,255,0.87);
    font-size: 1.16rem;
    line-height: 1.8;
}

.about-hero-card {
    padding: 2rem;
    border-radius: 2rem;
    background: rgba(255,255,255,0.9);
    color: #173626;
    border: 1px solid rgba(255,255,255,0.5);
    box-shadow: 0 28px 70px rgba(0,0,0,0.22);
    backdrop-filter: blur(14px);
}

.about-hero-card-icon {
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

.about-hero-card h3 {
    margin: 0 0 0.8rem;
    font-size: 1.55rem;
    letter-spacing: -0.03em;
}

.about-hero-card p {
    margin: 0;
    color: #526358;
    line-height: 1.75;
}

.about-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 2rem;
}

/* Intro */
.about-intro-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1.2rem;
}

.about-feature {
    position: relative;
    overflow: hidden;
    padding: 1.5rem;
    border-radius: 1.6rem;
    background: #ffffff;
    border: 1px solid rgba(23,54,38,0.08);
    box-shadow: 0 16px 45px rgba(0,0,0,0.07);
}

.about-feature::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 5px;
    background: linear-gradient(90deg, #173626, #f4c542);
}

.about-feature-icon {
    width: 50px;
    height: 50px;
    display: grid;
    place-items: center;
    margin-bottom: 1rem;
    border-radius: 1rem;
    background: #f8f4e7;
    color: #d6a823;
    font-size: 1.25rem;
}

.about-feature h3 {
    margin: 0 0 0.65rem;
    color: #173626;
}

.about-feature p {
    margin: 0;
    line-height: 1.7;
}

/* Programs */
.program-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
    margin-top: 2rem;
}

.program-card {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    text-align: left;
    width: 100%;
    min-height: 280px;
    padding: 1.5rem;
    border: 1px solid rgba(23,54,38,0.08);
    border-radius: 1.8rem;
    background:
        linear-gradient(135deg, rgba(255,255,255,0.98), rgba(255,250,235,0.96));
    box-shadow: 0 18px 50px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: 0.25s ease;
}

.program-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 28px 70px rgba(0,0,0,0.13);
}

.program-card::after {
    content: "";
    position: absolute;
    right: -55px;
    bottom: -55px;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: rgba(244, 197, 66, 0.16);
    transition: 0.25s ease;
}

.program-card:hover::after {
    transform: scale(1.18);
}

.program-head {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.1rem;
}

.program-logo {
    width: 72px;
    height: 72px;
    display: grid;
    place-items: center;
    border-radius: 1.3rem;
    background: #ffffff;
    border: 1px solid rgba(23,54,38,0.08);
    box-shadow: 0 12px 26px rgba(0,0,0,0.08);
}

.program-logo img {
    max-width: 56px;
    max-height: 56px;
    object-fit: contain;
}

.program-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.42rem 0.75rem;
    border-radius: 999px;
    background: rgba(244,197,66,0.18);
    border: 1px solid rgba(244,197,66,0.38);
    color: #8a650b;
    font-size: 0.82rem;
    font-weight: 900;
}

.program-card h3 {
    position: relative;
    z-index: 1;
    margin: 0 0 0.75rem;
    color: #173626;
    font-size: 1.35rem;
    letter-spacing: -0.03em;
}

.program-card p {
    position: relative;
    z-index: 1;
    margin-bottom: 1.2rem;
    line-height: 1.7;
}

.program-card .link {
    position: relative;
    z-index: 1;
    margin-top: auto;
    color: #173626;
    font-weight: 900;
    transition: 0.2s ease;
}

.program-card:hover .link {
    color: #d6a823;
}

/* Modal */
.modal {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: none;
}

.modal.open {
    display: block;
}

.modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(8, 18, 13, 0.72);
    backdrop-filter: blur(7px);
}

.modal-panel {
    position: relative;
    z-index: 1;
    width: min(920px, calc(100% - 2rem));
    max-height: calc(100vh - 2rem);
    overflow: auto;
    margin: 1rem auto;
    top: 50%;
    transform: translateY(-50%);
    border-radius: 2rem;
    background: #ffffff;
    box-shadow: 0 30px 90px rgba(0,0,0,0.35);
    animation: modalIn 0.2s ease;
}

@keyframes modalIn {
    from {
        opacity: 0;
        transform: translateY(-47%) scale(0.97);
    }
    to {
        opacity: 1;
        transform: translateY(-50%) scale(1);
    }
}

.modal-close {
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
    cursor: pointer;
    font-size: 1.1rem;
}

.modal-head {
    display: flex;
    align-items: center;
    gap: 1.2rem;
    padding: 2.2rem 2.2rem 1.3rem;
    background:
        radial-gradient(circle at top right, rgba(244,197,66,0.28), transparent 34%),
        linear-gradient(135deg, #f8f6ef, #ffffff);
}

.modal-icon {
    width: 86px;
    height: 86px;
    min-width: 86px;
    display: grid;
    place-items: center;
    border-radius: 1.5rem;
    background: #ffffff;
    border: 1px solid rgba(23,54,38,0.08);
    box-shadow: 0 14px 32px rgba(0,0,0,0.09);
}

.modal-icon img {
    max-width: 68px;
    max-height: 68px;
    object-fit: contain;
}

.modal-titlewrap h3 {
    margin: 0;
    color: #173626;
    font-size: clamp(1.7rem, 4vw, 2.4rem);
    letter-spacing: -0.04em;
}

.modal-sub {
    margin: 0.4rem 0 0;
    color: #7b887e;
    font-weight: 800;
}

.modal-body {
    padding: 0 2.2rem 2.2rem;
}

.modal-text p {
    line-height: 1.85;
    margin-bottom: 1rem;
}

.modal-h {
    margin: 1.2rem 0 0.9rem;
    color: #173626;
    font-size: 1.2rem;
}

.modal-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.modal-list li {
    position: relative;
    padding: 0.9rem 1rem 0.9rem 2.7rem;
    border-radius: 1rem;
    background: #f8f6ef;
    color: #526358;
    line-height: 1.55;
}

.modal-list li::before {
    content: "\f00c";
    position: absolute;
    left: 1rem;
    top: 0.95rem;
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    color: #d6a823;
}

/* Responsive */
@media (max-width: 980px) {
    .about-hero-inner {
        grid-template-columns: 1fr;
    }

    .program-cards {
        grid-template-columns: repeat(2, 1fr);
    }

    .about-intro-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .about-hero {
        padding: 3.6rem 0 3rem;
    }

    .about-actions {
        flex-direction: column;
    }

    .about-actions .btn {
        width: 100%;
        justify-content: center;
    }

    .program-cards {
        grid-template-columns: 1fr;
    }

    .modal-panel {
        width: calc(100% - 1rem);
        border-radius: 1.4rem;
    }

    .modal-head {
        flex-direction: column;
        align-items: flex-start;
        padding: 1.5rem;
    }

    .modal-body {
        padding: 0 1.5rem 1.5rem;
    }

    .modal-list {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="about-hero">
    <div class="container">
        <div class="about-hero-inner">
            <div>
                <div class="about-kicker">
                    <i class="fa-solid fa-compass"></i>
                    Par mūsu kustību
                </div>

                <h1>Par mums</h1>

                <p class="lead">
                    Mēs veidojam vidi, kur bērni un jaunieši aug prasmēs, raksturā un vērtībās.
                    Šeit piedzīvojumi nav tikai jautrība — tie palīdz kļūt drosmīgākiem,
                    atbildīgākiem un gatavākiem dzīvei.
                </p>

                <div class="about-actions">
                    <a class="btn btn-primary" href="#programmas">
                        Skatīt programmas
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>

                    <a class="btn btn-outline" href="<?= $base ?>public/kontakti.php">
                        Sazināties
                    </a>
                </div>
            </div>

            <aside class="about-hero-card">
                <div class="about-hero-card-icon">
                    <i class="fa-solid fa-people-group"></i>
                </div>

                <h3>Kopiena ar virzienu</h3>

                <p>
                    “Ceļa meklētāji” apvieno bērnus, jauniešus, vecākus un vadītājus,
                    lai kopā mācītos, kalpotu, dotos piedzīvojumos un augtu kristīgās vērtībās.
                </p>
            </aside>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <header class="section-title">
            <h2>Ko mēs dodam jauniešiem?</h2>
            <p class="muted">
                Ne tikai nodarbības. Drīzāk tāda dzīves skola, tikai bez garlaicīgā zvana starpbrīdī.
            </p>
        </header>

        <div class="about-intro-grid">
            <article class="about-feature">
                <div class="about-feature-icon">
                    <i class="fa-solid fa-campground"></i>
                </div>
                <h3>Piedzīvojumi</h3>
                <p class="muted">
                    Nometnes, pārgājieni, praktiski uzdevumi un komandas izaicinājumi.
                </p>
            </article>

            <article class="about-feature">
                <div class="about-feature-icon">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                </div>
                <h3>Vērtības</h3>
                <p class="muted">
                    Draudzība, atbildība, cieņa, kalpošana un kristīgs skatījums uz dzīvi.
                </p>
            </article>

            <article class="about-feature">
                <div class="about-feature-icon">
                    <i class="fa-solid fa-seedling"></i>
                </div>
                <h3>Izaugsme</h3>
                <p class="muted">
                    Prasmes, pašapziņa, līderība un drosme uzņemties iniciatīvu.
                </p>
            </article>
        </div>
    </div>
</section>

<section id="programmas" class="section section-alt">
    <div class="container">
        <header class="section-title">
            <h2>Mūsu programmas</h2>
            <p class="muted">
                Uzspied uz kartītes — atvērsies detalizēts apraksts ar mērķiem un ieguvumiem.
            </p>
        </header>

        <div class="program-cards" id="programCards">

            <button class="program-card" type="button"
                aria-label="Atvērt aprakstu: Ceļa meklētāji"
                data-title="Ceļa meklētāji"
                data-subtitle="10–15 gadi"
                data-img="<?= $base ?>assets/images/logos/CM.png"
                data-text="Ceļa meklētāji ir vieta, kur jaunieši ne tikai pavada laiku, bet atrod virzienu. Tā ir Septītās dienas adventistu jauniešu organizācija pusaudžiem, kur piedzīvojums satiekas ar vērtībām, draudzība ar atbildību, un jautājumi par dzīvi — ar jēgpilnām atbildēm. Šeit katrs jaunietis ir gaidīts, pamanīts un iedrošināts augt.

Ceļa meklētāju nodarbības ir dzīvas un daudzpusīgas — pārgājieni, nometnes, komandas izaicinājumi, praktiskas prasmes, radoši uzdevumi un kalpošana citiem. Tas viss notiek vidē, kur jaunietis mācās sadarboties, uzticēties, uzņemties atbildību un attīstīt līdera dotības.

Organizācijas galvenais mērķis ir palīdzēt jauniešiem izaugt par nobriedušām, domājošām un līdzjūtīgām personībām, balstītām kristīgās vērtībās."
                data-goals="Rakstura un pašapziņas attīstīšana|Praktiskas prasmes un piedzīvojumi|Līderība, komanda un atbildība|Kristīgas vērtības un kalpošana">

                <div class="program-head">
                    <div class="program-logo">
                        <img src="<?= $base ?>assets/images/logos/CM.png" alt="Ceļa meklētāji">
                    </div>
                    <span class="program-badge">10–15 gadi</span>
                </div>

                <h3>Ceļa meklētāji</h3>
                <p class="muted">Pusaudžiem, kur piedzīvojums satiekas ar vērtībām un raksturu.</p>
                <span class="link">Atvērt aprakstu →</span>
            </button>

            <button class="program-card" type="button"
                aria-label="Atvērt aprakstu: Piedzīvojumu meklētāji"
                data-title="Piedzīvojumu meklētāji"
                data-subtitle="5–9 gadi"
                data-img="<?= $base ?>assets/images/logos/PM.png"
                data-text="Piedzīvojumu meklētāji ir droša un draudzīga vide bērniem, kur mācīšanās notiek caur spēli, radošumu un kopā būšanu. Šī programma palīdz bērnam veidot labus ieradumus, attīstīt prasmes un augt ar pārliecību, ka viņš ir vērtīgs un mīlēts.

Nodarbībās bērni iepazīst dabu, mācās vienkāršas dzīves prasmes, veido draudzību, piedalās komandas uzdevumos un attīsta radošumu.

Programmas mērķis ir stiprināt bērna raksturu, ģimenes vērtības un ielikt pamatu veselīgai attieksmei pret dzīvi."
                data-goals="Draudzība un droša vide bērniem|Ģimenes vērtību stiprināšana|Radošums, daba un spēle|Laipnība, disciplīna un ieradumi">

                <div class="program-head">
                    <div class="program-logo">
                        <img src="<?= $base ?>assets/images/logos/PM.png" alt="Piedzīvojumu meklētāji">
                    </div>
                    <span class="program-badge">5–9 gadi</span>
                </div>

                <h3>Piedzīvojumu meklētāji</h3>
                <p class="muted">Bērniem — spēle, draudzība, prasmes un ģimenes vērtības.</p>
                <span class="link">Atvērt aprakstu →</span>
            </button>

            <button class="program-card" type="button"
                aria-label="Atvērt aprakstu: Mastergaidi"
                data-title="Mastergaidi"
                data-subtitle="Vadītāju programma"
                data-img="<?= $base ?>assets/images/logos/MG.png"
                data-text="Mastergaidi ir vadītāju izaugsmes un apmācību programma tiem, kas vēlas ne tikai piedalīties, bet arī vadīt, iedvesmot un kalpot. Tā palīdz attīstīt līdera prasmes, darbu ar jauniešiem un spēju organizēt jēgpilnas aktivitātes.

Programma ietver apmācības, praktiskus uzdevumus, projektu vadību, komandas darbu un personīgo disciplīnu.

Mērķis ir sagatavot nobriedušus un uzticamus vadītājus, kuri prot iedrošināt jauniešus, vadīt ar piemēru un būt par stabilu atbalstu."
                data-goals="Līdera prasmes un komandas vadība|Darbs ar jauniešiem un mentoring|Projektu un pasākumu organizēšana|Kalpošana un atbildība">

                <div class="program-head">
                    <div class="program-logo">
                        <img src="<?= $base ?>assets/images/logos/MG.png" alt="Mastergaidi">
                    </div>
                    <span class="program-badge">Vadītāji</span>
                </div>

                <h3>Mastergaidi</h3>
                <p class="muted">Vadītājiem — izaugsme, disciplīna un kalpošana ar piemēru.</p>
                <span class="link">Atvērt aprakstu →</span>
            </button>

            <button class="program-card" type="button"
                aria-label="Atvērt aprakstu: Vēstneši"
                data-title="Vēstneši"
                data-subtitle="16+"
                data-img="<?= $base ?>assets/images/logos/vestnesi.png"
                data-text="Vēstneši ir jauniešu programma tiem, kas ir gatavi spert nākamo soli: kļūt patstāvīgākiem, stiprākiem un apzinātākiem. Tā ir vide, kur sarunas kļūst dziļākas, atbildība lielāka un mērķi — skaidrāki.

Šeit jaunieši attīsta dzīves prasmes, mācās komandas darbu, līderību, kalpošanu un savu talantu pielietošanu.

Programmas mērķis ir palīdzēt jaunietim atrast savu balsi un virzienu, iemācīties dzīvot ar vērtībām un veidot attiecības."
                data-goals="Dzīves prasmes un patstāvība|Kalpošana un iniciatīvas|Līderība un projektu darbs|Dziļākas sarunas par vērtībām">

                <div class="program-head">
                    <div class="program-logo">
                        <img src="<?= $base ?>assets/images/logos/vestnesi.png" alt="Vēstneši">
                    </div>
                    <span class="program-badge">16+</span>
                </div>

                <h3>Vēstneši</h3>
                <p class="muted">Jauniešiem — prasmes, iniciatīva un stabilas vērtības.</p>
                <span class="link">Atvērt aprakstu →</span>
            </button>

            <button class="program-card" type="button"
                aria-label="Atvērt aprakstu: Mazie jēriņi"
                data-title="Mazie jēriņi"
                data-subtitle="4+"
                data-img="<?= $base ?>assets/images/logos/jerini.png"
                data-text="Mazie jēriņi ir programma pašiem mazākajiem, kur galvenais ir siltums, drošība un prieks mācīties. Tā palīdz bērniem attīstīt zinātkāri, valodu, uztveri un vienkāršas prasmes, kas noder ikdienā.

Nodarbības notiek rotaļīgi: dziesmas, stāsti, radoši darbi, kustības un vienkārši uzdevumi. Bērni mācās sadarboties, dalīties, klausīties un veidot labus ieradumus.

Programmas mērķis ir ielikt pamatu: ka pasaule ir izzināma, cilvēki ir mīlestības cienīgi un labas vērtības var sākties ļoti agri."
                data-goals="Droša vide mazajiem|Rotaļīga mācīšanās un radošums|Labie ieradumi un sadarbība|Pasaules izzināšana caur stāstiem">

                <div class="program-head">
                    <div class="program-logo">
                        <img src="<?= $base ?>assets/images/logos/jerini.png" alt="Mazie jēriņi">
                    </div>
                    <span class="program-badge">4+</span>
                </div>

                <h3>Mazie jēriņi</h3>
                <p class="muted">Mazajiem — droša vide, stāsti, dziesmas un izzināšana.</p>
                <span class="link">Atvērt aprakstu →</span>
            </button>

        </div>
    </div>
</section>

<!-- LIGHTBOX MODAL -->
<div class="modal" id="programModal" aria-hidden="true">
    <div class="modal-overlay" id="modalOverlay"></div>

    <div class="modal-panel" role="dialog" aria-modal="true" aria-label="Programmas apraksts">
        <button class="modal-close" id="modalClose" type="button" aria-label="Aizvērt">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>

        <div class="modal-head">
            <div class="modal-icon">
                <img id="modalIcon" src="" alt="">
            </div>

            <div class="modal-titlewrap">
                <h3 id="modalTitle"></h3>
                <p class="modal-sub" id="modalSub"></p>
            </div>
        </div>

        <div class="modal-body">
            <div class="modal-text" id="modalText"></div>

            <div class="divider"></div>

            <h4 class="modal-h">Mērķi un ieguvumi</h4>
            <ul class="modal-list" id="modalGoals"></ul>
        </div>
    </div>
</div>

<script>
(function () {
    const buttons = Array.from(document.querySelectorAll('.program-card'));

    const modal = document.getElementById('programModal');
    const overlay = document.getElementById('modalOverlay');
    const closeBtn = document.getElementById('modalClose');

    const titleEl = document.getElementById('modalTitle');
    const subEl   = document.getElementById('modalSub');
    const textEl  = document.getElementById('modalText');
    const goalsEl = document.getElementById('modalGoals');
    const iconEl  = document.getElementById('modalIcon');

    if (!modal || !overlay || !closeBtn) return;

    function openModal(data) {
        titleEl.textContent = data.title || '';
        subEl.textContent = data.subtitle || '';
        iconEl.src = data.img || '';
        iconEl.alt = data.title || '';

        const paragraphs = (data.text || '')
            .split(/\n\s*\n/)
            .map(p => p.trim())
            .filter(Boolean);

        textEl.innerHTML = paragraphs
            .map(p => `<p class="muted">${escapeHtml(p)}</p>`)
            .join('');

        goalsEl.innerHTML = '';

        const goals = (data.goals || '')
            .split('|')
            .map(g => g.trim())
            .filter(Boolean);

        goals.forEach(goal => {
            const li = document.createElement('li');
            li.textContent = goal;
            goalsEl.appendChild(li);
        });

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('nav-lock');
    }

    function closeModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('nav-lock');
    }

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            openModal({
                title: button.dataset.title,
                subtitle: button.dataset.subtitle,
                img: button.dataset.img,
                text: button.dataset.text,
                goals: button.dataset.goals
            });
        });
    });

    overlay.addEventListener('click', closeModal);
    closeBtn.addEventListener('click', closeModal);

    document.addEventListener('keydown', event => {
        if (!modal.classList.contains('open')) return;

        if (event.key === 'Escape') {
            closeModal();
        }
    });
})();
</script>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>