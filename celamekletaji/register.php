<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reģistrācija | Ceļa meklētāji</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="prof-header">
    <div class="header-container">
        <div class="logo">Ceļa meklētāji</div>
        <nav class="main-nav">
            <a href="index.html" class="nav-link">Sākums</a>
            <a href="login.html" class="nav-link">Pieslēgties</a>
        </nav>
    </div>
</header>

<main>
    <section class="section">
        <h2>Reģistrēties</h2>

        <div class="box" style="max-width: 450px; margin: 2rem auto;">
            <form>
                <label>Vārds</label>
                <input type="text" placeholder="Ievadi vārdu">

                <label>Uzvārds</label>
                <input type="text" placeholder="Ievadi uzvārdu">

                <label>E-pasts</label>
                <input type="email" placeholder="Ievadi e-pastu">

                <label>Parole</label>
                <input type="password" placeholder="Izveido paroli">

                <label>Atkārtot paroli</label>
                <input type="password" placeholder="Atkārto paroli">

                <button type="submit" class="btn">Reģistrēties</button>
            </form>

            <p style="margin-top:1rem;">
                Jau ir konts?
                <a href="login.html" style="color: var(--red); font-weight:700;">
                    Pieslēgties
                </a>
            </p>
        </div>
    </section>
</main>

<footer>
    © 2026 Ceļa meklētāji
</footer>

</body>
</html>
