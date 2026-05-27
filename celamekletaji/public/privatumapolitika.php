<?php
$lapa  = "Privātuma politika";
$title = "Privātuma politika | Ceļa meklētāji";

require __DIR__ . "/../includes/templates/header.php";
?>

<style>
/* ===============================
   PRIVĀTUMA POLITIKA
================================ */

.privacy-hero {
    position: relative;
    overflow: hidden;
    padding: 4.5rem 0 3.5rem;
    background:
        radial-gradient(circle at top left, rgba(244, 197, 66, 0.25), transparent 35%),
        linear-gradient(135deg, #10241b 0%, #173626 60%, #224e38 100%);
    color: #fff;
}

.privacy-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: 0.45;
}

.privacy-hero .container {
    position: relative;
    z-index: 1;
}

.privacy-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.45rem 0.85rem;
    margin-bottom: 1.1rem;
    border-radius: 999px;
    background: rgba(255,255,255,0.12);
    color: #f4c542;
    font-weight: 800;
    backdrop-filter: blur(10px);
}

.privacy-hero h1 {
    margin: 0;
    font-size: clamp(2.4rem, 5vw, 4.4rem);
    line-height: 1;
    letter-spacing: -0.05em;
}

.privacy-hero p {
    max-width: 760px;
    margin: 1.2rem 0 0;
    color: rgba(255,255,255,0.86);
    font-size: 1.08rem;
    line-height: 1.75;
}

.privacy-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    align-items: start;
}

.privacy-sidebar {
    position: sticky;
    top: 100px;
    padding: 1.2rem;
    border-radius: 1.5rem;
    background: #ffffff;
    border: 1px solid rgba(23,54,38,0.08);
    box-shadow: 0 16px 45px rgba(0,0,0,0.07);
}

.privacy-sidebar h3 {
    margin: 0 0 0.9rem;
    color: #173626;
    font-size: 1rem;
}

.privacy-sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.privacy-sidebar li {
    margin-bottom: 0.45rem;
}

.privacy-sidebar a {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.62rem 0.75rem;
    border-radius: 0.9rem;
    color: #526358;
    text-decoration: none;
    font-size: 0.92rem;
    font-weight: 700;
    transition: 0.2s ease;
}

.privacy-sidebar a:hover {
    color: #173626;
    background: #f4f0df;
}

.privacy-sidebar i {
    color: #d6a823;
    font-size: 0.8rem;
}

.privacy-card {
    padding: 2.4rem;
    border-radius: 2rem;
    background: #ffffff;
    border: 1px solid rgba(23,54,38,0.08);
    box-shadow: 0 18px 55px rgba(0,0,0,0.07);
}

.privacy-notice {
    display: flex;
    gap: 1rem;
    padding: 1.2rem;
    margin-bottom: 2rem;
    border-radius: 1.3rem;
    background: #f8f4e7;
    border: 1px solid rgba(214,168,35,0.22);
}

.privacy-notice-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    display: grid;
    place-items: center;
    border-radius: 1rem;
    background: #173626;
    color: #f4c542;
}

.privacy-notice p {
    margin: 0;
    color: #526358;
    line-height: 1.7;
}

.privacy-block {
    scroll-margin-top: 110px;
    padding: 1.4rem 0;
    border-bottom: 1px solid rgba(23,54,38,0.08);
}

.privacy-block:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.privacy-block h2 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 1rem;
    color: #173626;
    font-size: clamp(1.35rem, 3vw, 1.75rem);
    letter-spacing: -0.025em;
}

.privacy-block h2 span {
    width: 38px;
    height: 38px;
    min-width: 38px;
    display: grid;
    place-items: center;
    border-radius: 0.9rem;
    background: #173626;
    color: #f4c542;
    font-size: 0.95rem;
}

.privacy-block p {
    margin: 0 0 1rem;
    color: #526358;
    line-height: 1.85;
}

.privacy-block p:last-child {
    margin-bottom: 0;
}

.privacy-block a {
    color: #173626;
    font-weight: 800;
}

.privacy-block ul {
    display: grid;
    gap: 0.75rem;
    margin: 1rem 0;
    padding: 0;
    list-style: none;
}

.privacy-block li {
    position: relative;
    padding: 0.9rem 1rem 0.9rem 2.75rem;
    border-radius: 1rem;
    background: #f8f6ef;
    color: #526358;
    line-height: 1.65;
}

.privacy-block li::before {
    content: "\f00c";
    position: absolute;
    left: 1rem;
    top: 1rem;
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    color: #d6a823;
}

.privacy-contact-box {
    margin-top: 1rem;
    padding: 1.3rem;
    border-radius: 1.3rem;
    background:
        linear-gradient(135deg, #173626, #224e38);
    color: #fff;
}

.privacy-contact-box p {
    color: rgba(255,255,255,0.86);
}

.privacy-contact-box a {
    color: #f4c542;
}

@media (max-width: 980px) {
    .privacy-layout {
        grid-template-columns: 1fr;
    }

    .privacy-sidebar {
        position: static;
    }

    .privacy-sidebar ul {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.45rem;
    }
}

@media (max-width: 640px) {
    .privacy-hero {
        padding: 3.5rem 0 2.8rem;
    }

    .privacy-card {
        padding: 1.4rem;
        border-radius: 1.5rem;
    }

    .privacy-sidebar ul {
        grid-template-columns: 1fr;
    }

    .privacy-notice {
        flex-direction: column;
    }

    .privacy-block h2 {
        align-items: flex-start;
    }
}
</style>

<section class="privacy-hero">
    <div class="container">
        <div class="privacy-kicker">
            <i class="fa-solid fa-shield-halved"></i>
            Datu drošība un privātums
        </div>

        <h1>Privātuma politika</h1>

        <p>
            Šajā lapā skaidrots, kā biedrības “Ceļa meklētāji” tīmekļa vietnē tiek apstrādāti
            personas dati, kādam nolūkam tie tiek izmantoti un kādas tiesības ir lietotājiem.
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="privacy-layout">

            <aside class="privacy-sidebar">
                <h3>Sadaļas</h3>

                <ul>
                    <li><a href="#nozime"><i class="fa-solid fa-angle-right"></i> Nozīme</a></li>
                    <li><a href="#parzinis"><i class="fa-solid fa-angle-right"></i> Pārzinis</a></li>
                    <li><a href="#dati"><i class="fa-solid fa-angle-right"></i> Dati</a></li>
                    <li><a href="#noluki"><i class="fa-solid fa-angle-right"></i> Nolūki</a></li>
                    <li><a href="#sikdatnes"><i class="fa-solid fa-angle-right"></i> Sīkdatnes</a></li>
                    <li><a href="#tresas-personas"><i class="fa-solid fa-angle-right"></i> Trešās personas</a></li>
                    <li><a href="#glabasana"><i class="fa-solid fa-angle-right"></i> Glabāšana</a></li>
                    <li><a href="#tiesibas"><i class="fa-solid fa-angle-right"></i> Tiesības</a></li>
                    <li><a href="#drosiba"><i class="fa-solid fa-angle-right"></i> Drošība</a></li>
                    <li><a href="#kontakti"><i class="fa-solid fa-angle-right"></i> Kontakti</a></li>
                </ul>
            </aside>

            <div class="privacy-card">

                <div class="privacy-notice">
                    <div class="privacy-notice-icon">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>

                    <p>
                        Spēkā no: <strong><?= date('Y-m-d'); ?></strong>.
                        Šī politika ir sagatavota informatīvam mērķim un var tikt papildināta,
                        attīstot tīmekļa vietnes funkcionalitāti.
                    </p>
                </div>

                <div class="privacy-block" id="nozime">
                    <h2><span>1</span> Ko nozīmē šī privātuma politika?</h2>

                    <p>
                        Mēs rūpējamies par lietotāju privātumu un personas datu drošību. Personas dati tiek
                        apstrādāti saskaņā ar Eiropas Parlamenta un Padomes Regulu (ES) 2016/679
                        jeb Vispārīgo datu aizsardzības regulu, kā arī Latvijas Republikas normatīvajiem aktiem.
                    </p>

                    <p>
                        Šī privātuma politika attiecas uz tīmekļa vietni
                        <strong>celamekletaji.lv</strong> un tās apmeklētājiem.
                    </p>
                </div>

                <div class="privacy-block" id="parzinis">
                    <h2><span>2</span> Pārzinis un kontaktinformācija</h2>

                    <p><strong>Biedrība “CEĻA MEKLĒTĀJI”</strong></p>

                    <p>
                        Meža iela 6, Inčukalns, Inčukalna nov., LV-2141<br>
                        Reģ. Nr. 40008092554<br>
                        E-pasts:
                        <a href="mailto:info@celamekletaji.lv">info@celamekletaji.lv</a><br>
                        Tālrunis:
                        <a href="tel:+37129000000">+371 29 000 000</a>
                    </p>

                    <p>
                        Organizācija darbojas kristīgas bērnu un jauniešu kustības ietvarā,
                        nodrošinot informāciju par nodarbībām, nometnēm, klubiem un pasākumiem.
                    </p>
                </div>

                <div class="privacy-block" id="dati">
                    <h2><span>3</span> Kādus datus mēs apkopojam?</h2>

                    <p>
                        Mēs apkopojam tikai tos personas datus, kas nepieciešami vietnes darbībai,
                        lietotāju saziņai un pakalpojumu nodrošināšanai.
                    </p>

                    <ul>
                        <li>
                            <strong>Kontaktinformācija:</strong>
                            vārds, e-pasts, tālruņa numurs un ziņas saturs, ja lietotājs sazinās ar biedrību.
                        </li>

                        <li>
                            <strong>Lietotāja konta dati:</strong>
                            lietotājvārds, vārds, uzvārds, e-pasts, parole šifrētā veidā un lietotāja loma.
                        </li>

                        <li>
                            <strong>Tehniskie dati:</strong>
                            IP adrese, pārlūka tips, ierīces informācija, apmeklējuma datums un laiks.
                        </li>

                        <li>
                            <strong>Pieteikumu dati:</strong>
                            informācija, kas nepieciešama pieteikšanās procesam nodarbībām, klubiem,
                            nometnēm vai citiem pasākumiem.
                        </li>
                    </ul>
                </div>

                <div class="privacy-block" id="noluki">
                    <h2><span>4</span> Kādam nolūkam mēs izmantojam datus?</h2>

                    <ul>
                        <li>Lai atbildētu uz lietotāju jautājumiem un nodrošinātu saziņu.</li>
                        <li>Lai nodrošinātu lietotāju kontu izveidi, pieslēgšanos un pārvaldību.</li>
                        <li>Lai organizētu pieteikšanos nodarbībām, klubiem, nometnēm vai pasākumiem.</li>
                        <li>Lai uzlabotu tīmekļa vietnes funkcionalitāti, drošību un lietošanas ērtumu.</li>
                        <li>Lai nodrošinātu normatīvo aktu prasību izpildi, ja tas ir nepieciešams.</li>
                    </ul>
                </div>

                <div class="privacy-block" id="sikdatnes">
                    <h2><span>5</span> Sīkdatnes un analītika</h2>

                    <p>
                        Vietnē var tikt izmantotas sīkdatnes, kas palīdz nodrošināt vietnes darbību,
                        saglabāt lietotāja izvēles un uzlabot lietošanas pieredzi.
                    </p>

                    <p>
                        Vietne var izmantot analītikas rīkus, piemēram, Google Analytics, lai iegūtu
                        apkopotu statistiku par vietnes apmeklējumu. Šī informācija parasti tiek apstrādāta
                        anonimizētā vai pseidonimizētā veidā.
                    </p>

                    <p>
                        Lietotājs var ierobežot vai dzēst sīkdatnes savas pārlūkprogrammas iestatījumos.
                        Tomēr sīkdatņu bloķēšana var ietekmēt atsevišķu vietnes funkciju darbību.
                    </p>
                </div>

                <div class="privacy-block" id="tresas-personas">
                    <h2><span>6</span> Datu nodošana trešajām personām</h2>

                    <p>
                        Personas dati var tikt nodoti tikai nepieciešamības gadījumā un tikai tiem pakalpojumu
                        sniedzējiem, kas palīdz nodrošināt vietnes darbību, piemēram, hostinga pakalpojumu,
                        e-pasta piegādes vai analītikas nodrošinātājiem.
                    </p>

                    <p>
                        Mēs nepārdodam lietotāju personas datus. Dati var tikt izpausti tikai tad,
                        ja to prasa normatīvie akti vai tas nepieciešams biedrības tiesību aizsardzībai.
                    </p>
                </div>

                <div class="privacy-block" id="glabasana">
                    <h2><span>7</span> Datu glabāšanas termiņš</h2>

                    <p>
                        Personas dati netiek glabāti ilgāk, nekā tas nepieciešams to apstrādes nolūkiem.
                        Kontaktformas dati tiek glabāti tik ilgi, cik nepieciešams saziņai un jautājuma risināšanai.
                    </p>

                    <p>
                        Lietotāja konta dati tiek glabāti līdz konta dzēšanai, ja vien normatīvie akti
                        neparedz citu glabāšanas termiņu.
                    </p>
                </div>

                <div class="privacy-block" id="tiesibas">
                    <h2><span>8</span> Lietotāja tiesības</h2>

                    <p>Lietotājam ir tiesības:</p>

                    <ul>
                        <li>pieprasīt piekļuvi saviem personas datiem;</li>
                        <li>pieprasīt labot neprecīzus vai nepilnīgus datus;</li>
                        <li>pieprasīt datu dzēšanu, ja tas ir piemērojams;</li>
                        <li>ierobežot datu apstrādi vai iebilst pret apstrādi;</li>
                        <li>atsaukt piekrišanu, ja apstrāde balstīta uz piekrišanu;</li>
                        <li>iesniegt sūdzību Datu valsts inspekcijā.</li>
                    </ul>

                    <p>
                        Pieprasījumus par personas datu apstrādi var nosūtīt uz:
                        <a href="mailto:info@celamekletaji.lv">info@celamekletaji.lv</a>
                    </p>
                </div>

                <div class="privacy-block" id="drosiba">
                    <h2><span>9</span> Datu drošība</h2>

                    <p>
                        Mēs izmantojam saprātīgus tehniskus un organizatoriskus pasākumus, lai aizsargātu
                        personas datus pret nesankcionētu piekļuvi, izmaiņām, izpaušanu vai iznīcināšanu.
                    </p>

                    <p>
                        Jāņem vērā, ka datu pārsūtīšana internetā nekad nav pilnībā droša,
                        tomēr tiek veikti pasākumi, lai riskus samazinātu.
                    </p>
                </div>

                <div class="privacy-block" id="izmainas">
                    <h2><span>10</span> Izmaiņas privātuma politikā</h2>

                    <p>
                        Biedrībai ir tiesības atjaunot šo privātuma politiku. Atjauninātā versija stājas spēkā
                        brīdī, kad tā tiek publicēta tīmekļa vietnē.
                    </p>
                </div>

                <div class="privacy-block" id="kontakti">
                    <h2><span>11</span> Kontakti</h2>

                    <div class="privacy-contact-box">
                        <p>
                            Ja rodas jautājumi par personas datu apstrādi, sazinieties ar mums:
                        </p>

                        <p>
                            <strong>Biedrība “CEĻA MEKLĒTĀJI”</strong><br>
                            Meža iela 6, Inčukalns, Inčukalna nov., LV-2141<br>
                            Reģ. Nr. 40008092554<br>
                            E-pasts:
                            <a href="mailto:info@celamekletaji.lv">info@celamekletaji.lv</a><br>
                            Tālrunis:
                            <a href="tel:+37129000000">+371 29 000 000</a>
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>