<?php
    $lapa = "Par mums";
    $title = "Par mums | Ceļa meklētāji";
    require "assets/header.php";
?>

<section class="hero">
    <div class="container">
        <div class="hero-inner">
            <div class="hero-content">
                <h1>Par mums</h1>
                <p class="lead">
                    Mēs veidojam vidi, kur bērni un jaunieši aug prasmēs, raksturā un vērtībās.
                    Izvēlies programmu un atver aprakstu.
                </p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="#programmas">Skatīt programmas</a>
                    <a class="btn btn-outline" href="kontakti.php">Sazināties</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="programmas" class="section section-alt">
    <div class="container">
        <header class="section-title">
            <h2>Mūsu programmas</h2>
            <p class="muted">Uzspied uz kartītes — atvērsies detalizēts apraksts.</p>
        </header>

        <div class="cards program-cards" id="programCards">

            <!-- Ceļa meklētāji -->
            <button class="card program-card" type="button"
                aria-label="Atvērt aprakstu: Ceļa meklētāji"
                data-title="Ceļa meklētāji"
                data-subtitle="10–15 gadi"
                data-img="images/CM.png"
                data-text="Ceļa meklētāji ir vieta, kur jaunieši ne tikai pavada laiku, bet atrod virzienu. Tā ir Septītās dienas adventistu jauniešu organizācija pusaudžiem (aptuveni 10–15 g.), kur piedzīvojums satiekas ar vērtībām, draudzība ar atbildību, un jautājumi par dzīvi – ar jēgpilnām atbildēm. Šeit katrs jaunietis ir gaidīts, pamanīts un iedrošināts augt.

Ceļa meklētāju nodarbības ir dzīvas un daudzpusīgas – pārgājieni, nometnes, komandas izaicinājumi, praktiskas prasmes, radoši uzdevumi un kalpošana citiem. Tas viss notiek vidē, kur jaunietis mācās sadarboties, uzticēties, uzņemties atbildību un attīstīt līdera dotības. Tā nav tikai “aktivitāšu kopa” – tā ir pieredze, kas veido raksturu un pašapziņu.

Organizācijas galvenais mērķis ir palīdzēt jauniešiem izaugt par nobriedušām, domājošām un līdzjūtīgām personībām, balstītām kristīgās vērtībās. Ceļa meklētāji iedrošina veidot personīgas attiecības ar Dievu, būt aktīviem savā draudzē un sabiedrībā, un ar drosmi meklēt savu vietu pasaulē. Ja meklē vidi, kur jaunietis var augt gan sirdī, gan prātā – Ceļa meklētāji ir īstā vieta."
                data-goals="Rakstura un pašapziņas attīstīšana|Praktiskas prasmes un piedzīvojumi|Līderība, komanda un atbildība|Kristīgas vērtības un kalpošana">

                <div class="program-head">
                    <div class="program-logo">
                        <img src="images/CM.png" alt="Ceļa meklētāji">
                    </div>
                    <span class="program-badge">10–15 gadi</span>
                </div>

                <h3>Ceļa meklētāji</h3>
                <p class="muted">Pusaudžiem, kur piedzīvojums satiekas ar vērtībām un raksturu.</p>
                <span class="link">Atvērt aprakstu →</span>
            </button>

            <!-- Piedzīvojumu meklētāji -->
            <button class="card program-card" type="button"
                aria-label="Atvērt aprakstu: Piedzīvojumu meklētāji"
                data-title="Piedzīvojumu meklētāji"
                data-subtitle="5–9 gadi"
                data-img="images/PM.png"
                data-text="Piedzīvojumu meklētāji ir droša un draudzīga vide bērniem, kur mācīšanās notiek caur spēli, radošumu un kopā būšanu. Šī programma palīdz bērnam veidot labus ieradumus, attīstīt prasmes un augt ar pārliecību, ka viņš ir vērtīgs un mīlēts.

Nodarbībās bērni iepazīst dabu, mācās vienkāršas dzīves prasmes, veido draudzību, piedalās komandas uzdevumos un attīsta radošumu. Tas viss notiek kopā ar ģimeni un vadītājiem, kuri palīdz bērnam ieraudzīt lielo bildi — ka rūpes, laipnība un atbildība ir spēks.

Programmas mērķis ir stiprināt bērna raksturu, ģimenes vērtības un ielikt pamatu veselīgai attieksmei pret dzīvi: cieņa pret citiem, disciplīna, pateicība un vēlme palīdzēt."
                data-goals="Draudzība un droša vide bērniem|Ģimenes vērtību stiprināšana|Radošums, daba un spēle|Laipnība, disciplīna un ieradumi">

                <div class="program-head">
                    <div class="program-logo">
                        <img src="images/PM.png" alt="Piedzīvojumu meklētāji">
                    </div>
                    <span class="program-badge">5–9 gadi</span>
                </div>

                <h3>Piedzīvojumu meklētāji</h3>
                <p class="muted">Bērniem — spēle, draudzība, prasmes un ģimenes vērtības.</p>
                <span class="link">Atvērt aprakstu →</span>
            </button>

            <!-- Mastergaidi -->
            <button class="card program-card" type="button"
                aria-label="Atvērt aprakstu: Mastergaidi"
                data-title="Mastergaidi"
                data-subtitle="Vadītāju programma"
                data-img="images/MG.png"
                data-text="Mastergaidi ir vadītāju izaugsmes un apmācību programma tiem, kas vēlas ne tikai piedalīties, bet arī vadīt, iedvesmot un kalpot. Tā palīdz attīstīt līdera prasmes, darbu ar jauniešiem un spēju organizēt jēgpilnas aktivitātes — ar skaidru vērtību pamatu.

Programma ietver apmācības, praktiskus uzdevumus, projektu vadību, komandas darbu un personīgo disciplīnu. Mastergaidi mācās plānot nometnes, pārgājienus, nodarbības, kā arī attīstīt komunikāciju un atbildību — lai vadība būtu droša, profesionāla un cilvēcīga.

Mērķis ir sagatavot nobriedušus un uzticamus vadītājus, kuri prot iedrošināt jauniešus, vadīt ar piemēru un būt par stabilu atbalstu draudzē un sabiedrībā."
                data-goals="Līdera prasmes un komandas vadība|Darbs ar jauniešiem un mentoring|Projektu un pasākumu organizēšana|Kalpošana un atbildība">

                <div class="program-head">
                    <div class="program-logo">
                        <img src="images/MG.png" alt="Mastergaidi">
                    </div>
                    <span class="program-badge" style="background: rgba(30,79,161,.14); border-color: rgba(30,79,161,.28); color: var(--blue-800);">Vadītāji</span>
                </div>

                <h3>Mastergaidi</h3>
                <p class="muted">Vadītājiem — izaugsme, disciplīna un kalpošana ar piemēru.</p>
                <span class="link">Atvērt aprakstu →</span>
            </button>

            <!-- Vēstneši -->
            <button class="card program-card" type="button"
                aria-label="Atvērt aprakstu: Vēstneši"
                data-title="Vēstneši"
                data-subtitle="16+"
                data-img="images/vestnesi.png"
                data-text="Vēstneši ir jauniešu programma tiem, kas ir gatavi spert nākamo soli: kļūt patstāvīgākiem, stiprākiem un apzinātākiem. Tā ir vide, kur sarunas kļūst dziļākas, atbildība lielāka un mērķi — skaidrāki.

Šeit jaunieši attīsta dzīves prasmes, mācās komandas darbu, līderību, kalpošanu un savu talantu pielietošanu. Vēstneši bieži iesaistās projektos, palīdz organizēt pasākumus, atbalsta jaunākās grupas un veido iniciatīvas, kas ietekmē kopienu.

Programmas mērķis ir palīdzēt jaunietim atrast savu balsi un virzienu, iemācīties dzīvot ar vērtībām un veidot attiecības — ar Dievu, ģimeni, draugiem un sabiedrību."
                data-goals="Dzīves prasmes un patstāvība|Kalpošana un iniciatīvas|Līderība un projektu darbs|Dziļākas sarunas par vērtībām">

                <div class="program-head">
                    <div class="program-logo">
                        <img src="images/vestnesi.png" alt="Vēstneši">
                    </div>
                    <span class="program-badge" style="background: rgba(198,40,40,.16); border-color: rgba(198,40,40,.28); color: var(--red);">16+</span>
                </div>

                <h3>Vēstneši</h3>
                <p class="muted">Jauniešiem — prasmes, iniciatīva un stabilas vērtības.</p>
                <span class="link">Atvērt aprakstu →</span>
            </button>

            <!-- Mazie jēriņi -->
            <button class="card program-card" type="button"
                aria-label="Atvērt aprakstu: Mazie jēriņi"
                data-title="Mazie jēriņi"
                data-subtitle="4+"
                data-img="images/jerini.png"
                data-text="Mazie jēriņi ir programma pašiem mazākajiem, kur galvenais ir siltums, drošība un prieks mācīties. Tā palīdz bērniem attīstīt zinātkāri, valodu, uztveri un vienkāršas prasmes, kas noder ikdienā.

Nodarbības notiek rotaļīgi: dziesmas, stāsti, radoši darbi, kustības un vienkārši uzdevumi. Bērni mācās sadarboties, dalīties, klausīties un veidot labus ieradumus — bez spiediena, bet ar iedrošinājumu.

Programmas mērķis ir ielikt pamatu: ka pasaule ir izzināma, cilvēki ir mīlestības cienīgi un ka labas vērtības var sākties ļoti agri — ģimenē un kopienā."
                data-goals="Droša vide mazajiem|Rotaļīga mācīšanās un radošums|Labie ieradumi un sadarbība|Pasaules izzināšana caur stāstiem">

                <div class="program-head">
                    <div class="program-logo">
                        <img src="images/jerini.png" alt="Mazie jēriņi">
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
            <div class="modal-icon" id="modalIconWrap">
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

    function openModal(data){
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
        const goals = (data.goals || '').split('|').map(g => g.trim()).filter(Boolean);
        goals.forEach(g => {
            const li = document.createElement('li');
            li.textContent = g;
            goalsEl.appendChild(li);
        });

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('nav-lock');
    }

    function closeModal(){
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('nav-lock');
    }

    function escapeHtml(str){
        return str
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .replaceAll('"', '&quot;')
          .replaceAll("'", '&#039;');
    }

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            openModal({
                title: btn.dataset.title,
                subtitle: btn.dataset.subtitle,
                img: btn.dataset.img,
                text: btn.dataset.text,
                goals: btn.dataset.goals
            });
        });
    });

    overlay.addEventListener('click', closeModal);
    closeBtn.addEventListener('click', closeModal);

    document.addEventListener('keydown', (e) => {
        if (!modal.classList.contains('open')) return;
        if (e.key === 'Escape') closeModal();
    });
})();
</script>

<?php
    require "assets/footer.php";
?>
