<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title;?></title>

    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>

<header class="prof-header">
    <div class="header-container">

        <a href="index.php" class="logo-link">
            <img src="images/logo.png" class="logo" alt="Ceļa meklētāji">
        </a>

        <h1 class="header-title"><?php echo $lapa;?></h1>

       <nav class="main-nav">
            <a href="about.php" class="nav-link">Par mums</a>
            <a href="gallery.php" class="nav-link">Galerija</a>
            <a href="clubs.php" class="nav-link">Klubi</a>
            <a href="login.php" class="nav-link" aria-label="Pievienoties">
                <i class="fas fa-user"></i>
            </a>
        </nav>
        
        <button id="menu-btn">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</header>