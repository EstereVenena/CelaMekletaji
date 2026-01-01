<?php
    # Savienojums ar datubāzi:
    $serveris = "localhost";
    $lietotajs = "grobina1_venena"; # jānomaina uz savu lietotājsvārdu
    $parole = "rpAKFVnbOZV1@"; # jānomaina uz savu paroli
    $datubaze = "grobina1_venena"; # jānomaina uz savu DB

    $savienojums = mysqli_connect($serveris, $lietotajs, $parole, $datubaze);

    if(!$savienojums){
        echo "Nav izveidots savienojums ar datubāzi!";
    }else{
       # echo "Ir izveidots savienojums ar datubāzi!";
    }

    # Pievienošana:
    if(isset($_POST["add"])){
        $prece = mysqli_real_escape_string($savienojums, $_POST["precesIevade"]);
        $skaits = mysqli_real_escape_string($savienojums, $_POST["skaitaIevade"]);
        $cena = mysqli_real_escape_string($savienojums, $_POST["cenasIevade"]);
        $pievienotSQL = "INSERT INTO php1_prece(prece_nosaukums, prece_daudzums, prece_cena) VALUES('$prece', $skaits, $cena)";
        mysqli_query($savienojums, $pievienotSQL);
        echo "
                <div class='notification'>
                    Prece veiksmīgi pievienota!
                </div>
            ";
        header("Refresh:1");
    }

    # Dzēšana:
    if(isset($_POST["delete"])){
        echo $id = $_POST["delete"];
        $dzestSQL = "DELETE FROM php1_prece WHERE prece_id = $id";
        mysqli_query($savienojums, $dzestSQL);
        echo "
                <div class='notification'>
                    Prece veiksmīgi dzēsta!
                </div>
            ";
        header("Refresh:1");
    }

    # Rediģēšana:
    if(isset($_POST["edit"])){
        $id = $_GET["id"];
        $prece = mysqli_real_escape_string($savienojums, $_POST["precesIevade"]);
        $skaits = mysqli_real_escape_string($savienojums, $_POST["skaitaIevade"]);
        $cena = mysqli_real_escape_string($savienojums, $_POST["cenasIevade"]);
        $redigetSQL = "UPDATE php1_prece SET prece_nosaukums = '$prece', prece_daudzums = '$skaits', prece_cena = '$cena' WHERE prece_id = $id";
        mysqli_query($savienojums, $redigetSQL);

        echo "
                <div class='notification'>
                    Prece veiksmīgi rediģēta!
                </div>
            ";
        header("Refresh:1; url=mysql.php");
    }

    # Ielogošanās:
    if(isset($_POST["ielogoties"])){
        session_start();
        $lietotajvards = mysqli_real_escape_string($savienojums, $_POST["lietotajs"]);
        $parole = mysqli_real_escape_string($savienojums, $_POST["parole"]);
        $ielogotiesSQL = "SELECT * FROM php1_lietotaji WHERE lietotajvards = '$lietotajvards'";
        $ielogoties = mysqli_query($savienojums, $ielogotiesSQL);

        if(mysqli_num_rows($ielogoties) == 1){
            # echo "Lietotājs atrasts!";
            while($lietotajs = mysqli_fetch_assoc($ielogoties)){
                if(password_verify($parole, $lietotajs["parole"])){
                    # echo "Ielogošanās veiksmīga!";
                    $_SESSION["lietotajvards_YLQM"] = $lietotajs["lietotajvards"];
                    header("location:mysql.php");
                }else{
                    echo "Nepareizs lietotājvārds vai parole!"; # Šeit uzsvars tieši uz paroli
                }
            }
        }else{
            echo "Nepareizs lietotājvārds vai parole!"; # Nedrīkst būt vairāki vienādi lietotājvārdi datubāzes tabulā!
        }
    }



    if(isset($_POST["noraidit"])){
        echo $id = $_POST["noraidit"];
        $noraiditLietSQL = "UPDATE php1_lietotaji SET statuss = 'Neapstiprinats' WHERE lietotajs_id = $id";
        mysqli_query($savienojums, $noraiditLietSQL);
        echo "
                <div class='notification'>
                   Lietotājs veiksmīgi noraidīts!
                </div>
            ";
        header("Refresh:1");
    }

    if(isset($_POST["apstiprinat"])){
        echo $id = $_POST["apstiprinat"];
        $apstiprinatLietSQL = "UPDATE php1_lietotaji SET statuss = 'Apstiprinats' WHERE lietotajs_id = $id";
        mysqli_query($savienojums,  $apstiprinatLietSQL);
        echo "
                <div class='notification'>
                   Lietotājs veiksmīgi apstiprinats!
                </div>
            ";
        header("Refresh:1");
    }
?>