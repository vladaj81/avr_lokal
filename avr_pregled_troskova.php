<?php

//PODESAVANJE DEFAULT VREMENSKE ZONE
date_default_timezone_set('UTC');

/******
 * -FUNKCIJA ZA DOBIJANJE POSLEDNJEG DATUMA IZ PRETHODNOG KVARTALA,U ODNOSU NA TRENUTNI DATUM I PROVERU DA LI JE UNET KOEFICIJENT 
 *  ZA TAJ DATUM U TABELI DEV_KLJUC
 * -U SUPROTNOM VRACA FALSE
 * -PARAMETAR JE TRENUTNI DATUM
*******/
function proveri_koeficijente_i_prenosnu_premiju($datum) {

    //UKLJUCIVANJE KONEKCIJE KA AMSO BAZI
    require_once 'dodaci/konekcija_amso.php';
	
	//UPIT ZA DOBIJANJE POSLEDNJEG DATUMA IZ PRETHODNOG KVARTALA,U ODNOSU NA TRENUTNI DATUM
	$upit_za_godine = "SELECT (godina::date -'1 day'::interval)::date as kvartal FROM generate_series('2016-04-01'::date, '$datum', '3 months'::interval) as godina 
					   ORDER BY kvartal DESC LIMIT 1";

	//IZVRSAVANJE UPITA I UPIS REZULTATA U PROMENJIVU
	$rezultat_za_godine = pg_query($amso_konekcija, $upit_za_godine);
    $red_godine = pg_fetch_array($rezultat_za_godine);

    //UPISIVANJE DOBIJENOG DATUMA U PROMENJIVU
    $poslednji_datum = $red_godine['kvartal'];
    
    //UPIT ZA PROVERU DA LI SU UNETI KOEFICIJENTI ZA POSLEDNJI DATUM PRETHODNOG KVARTALA
    $upit_koeficijent = "SELECT koef_kljuc, dan FROM dev_kljuc WHERE dan = CAST('$poslednji_datum' AS DATE)";

    //IZVRSAVANJE UPITA
    $rezultat_koeficijent = pg_query($amso_konekcija, $upit_koeficijent);

    //AKO DODJE DO GRESKE U IZVRSAVANJU UPITA,OBAVESTI KORISNIKA
    if (!$rezultat_koeficijent) {

        return 'Greška pri izvršavanju upita';
    }

    //AKO JE KOEFICIJENT UNET,VRATI DATUM
    if (pg_num_rows($rezultat_koeficijent) > 0) {

        //INICIJALIZACIJA BROJACA
        $brojac = 0;

        //GENERISANJE REDOVA U HTML TABELI ZA DOBIJENE REDOVE IZ BAZE
        while ($red_koeficijent = pg_fetch_array($rezultat_koeficijent)) {

            $brojac++;

            //USLOV JE DODAT DA BI SE U PROMENJIVU UPISAO SAMO JEDAN DATUM(POLJA IZ SVA 4 REDA SU JEDNAKA)
            if ($brojac == 1) {

                //IZDVAJANJE DELA DATUMA SA DANOM I MESECOM
                $podaci_kvartal = substr($red_koeficijent['dan'], 5, 9);

                //ODREDJIVANJE KVARTALA NA OSNOVU DANA I MESECA
                switch ($podaci_kvartal) {
    
                    case '03-31':
                        $kvartal = 'I';
                        break;
                        
                    case '06-30':
                        $kvartal = 'II';
                        break;
                        
                    case '09-30':
                        $kvartal = 'III';
                        break;
                        
                    case '12-31':
                        $kvartal = 'IV';
                        break;
                }
                
                //UPIS PUNOG DATUMA U VARIJABLU
                $pun_datum = $red_koeficijent['dan'] .' ('. $kvartal .' kvartal)';
            }

        }
        
        //DOBIJANJE DATUMA BEZ KVARTALA I RASTAVLJANJE U NIZ
        $datum_bez_kvartala = substr($pun_datum, 0, 10);
        $datum_bez_kvartala = explode('-', $datum_bez_kvartala);

        //FORMIRANJE IMENA TABELE
        $ime_tabele = 'pp_' .substr($datum_bez_kvartala[0], 2, 2) .$datum_bez_kvartala[1] . $datum_bez_kvartala[2];

        //PROVERA DA LI TABELA POSTOJI U SCHEMI PRENOSNA
        $upit_provera_tabele = "SELECT EXISTS (
                                    SELECT * FROM pg_catalog.pg_class c
                                    JOIN   pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                                    WHERE  n.nspname = 'prenosna'
                                    AND    c.relname = '$ime_tabele'
                                    AND    c.relkind = 'r'    -- only tables
                                )";

        //IZVRSAVANJE UPITA
        $rezultat_provera_tabele = pg_query($amso_konekcija, $upit_provera_tabele);

        //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA I PREKINI IZVRSAVANJE SKRIPTE
        if (!$rezultat_provera_tabele) {

            echo json_encode('Greška pri izvršavanju upita. Pokušajte ponovo.');
            die();
        }

        //AKO UPIT VRATI REZULTATE
        if (pg_num_rows($rezultat_provera_tabele) > 0) {

            //UPIS REZULTATA U NIZ
            $red_provera_tabele = pg_fetch_array($rezultat_provera_tabele);
            
            //AKO TABELA SA ZADATIM IMENOM POSTOJI,VRATI TRUE
            if ($red_provera_tabele['exists'] == 't') {

                //VRATI PUN DATUM
                return $pun_datum; 
            }

            //U SUPROTNOM VRATI FALSE
            else {
                return false;
            }
        }
    }

    //U SUPROTNOM VRATI FALSE
    else {

        return false;
    }
}

//DOBIJANJE DANASNJEG DATUMA
//$danasnji_datum = date('Y-m-d');
$danasnji_datum = '2020-07-01';

//POZIVANJE FUNKCIJE I UPIS DATUMA U VARIJABLU
$datum_za_prikaz = proveri_koeficijente_i_prenosnu_premiju($danasnji_datum);


//PRETVARANJE DOBIJENOG DATUMA U FORMAT ZA PRIKAZ
$datum_polje = substr($datum_za_prikaz, 0, 10);
$kvartal = substr($datum_za_prikaz, 11);
$datum_polje = explode('-', $datum_polje);
$datum_string = $datum_polje[2] .'.'. $datum_polje[1] .'.'.  $datum_polje[0] .'. '. $kvartal;

?>
<html>
<head>
	<title>Aktivna vremenska razgraničenja</title>
	<meta name="naslov" content="Aktivna vremenska razgraničenja">
    <meta http-equiv="Content-Type" content="text/html; charset=utf8">
    
    <!--UKLJUCIVANJE JQUERY UI CSS-A I CSS-A ZA KOMPENZACIJE-->
    <link rel="stylesheet" type="text/css" href="css/avr.css"/>
    
    <!--UKLJUCIVANJE POTREBNIH SKRIPTI-->
    <script type="text/javascript" language="javascript" src="js/jquery.js"></script>
    <script type="text/javascript" language="javascript" src="js/jquery.alphanum.js"></script>
</head>

<body>
    
    <div id="pretraga">
        <div id="container">

            <!--GORNJE ZAGLAVLJE CELE FORME POCETAK-->
            <div id="okolog">
                <img src="images/icg/tb2_l.gif" alt="left-icon" class="levo" />
                <img src="images/icg/tb2_r.gif" alt="right-icon" class="desno" />
                <span id="natpis">Aktivna vremenska razgraničenja</span>
            </div>
            <!--GORNJE ZAGLAVLJE CELE FORME KRAJ-->

            <!--DIV SA SADRZAJEM POCETAK-->
            <div id="content">

            <!--NOSECI DIV SA FORMOM ZA PRIKAZ POCETAK-->
            <div class="pocetni_wrapper">
                <div id="trazi_podatke">

                    <!--POLJE ZA PRIKAZ DATUMA POZIVANJEM PHP FUNKCIJE-->
                    <label id="datum_label" for="datum">Datum:</label>
                    
                    <?php  
                        //AKO SU UNETI KOEFICIJENTI U TABELI_DEV KLJUC,PRIKAZI DATUM U INPUT POLJU
                        if ($datum_za_prikaz) {

                            echo '<input id="datum" value="' .$datum_string. '" disabled/>';

                        }
                        //U SUPROTNOM,OBAVESTI KORISNIKA DA NISU UNETI KOEFICIJENTI
                        else {
                            echo '<input id="datum" value="" disabled/>';
                            echo "<script type='text/javascript'>alert('Još uvek nisu uneti prenosna premija i koeficijenti za prethodni kvartal.');</script>"; 
                        }
                    ?>

                    <!--DUGME ZA POTVRDU SLANJA DATUMA-->
                    <button type="button" id="posalji_datum">Prikaži troškove</button>
                    <button id="export_excel">Export to excel</a>

                </div>

                <!--DIV SA BUTTON-IMA ZA PROMENU TABA POCETAK-->
                <div class="tabovi">
                    
                </div>
                <!--DIV SA BUTTON-IMA ZA PROMENU TABA POCETAK-->

            </div>
                <br/>
               
                <!--DIV SA DUGMETOM ZA OBRACUN I CHECKBOXOM CEKIRAJ SVE POCETAK-->
                <div class="obracun">
                    <div class="prvi_item">
                        <button type="button" id="obracunaj">Obračunaj troškove</button>
                    </div>

                    <div class="div_pretraga">
                        <input type="text" id="pretraga_tabele" placeholder="Unesite pojam za pretragu"/>
                    </div>
                    <div class="div_cekiraj_sve">
                        <label for="check_all">Čekiraj sve</label>
                        <input type="checkbox" id="check_all">
                    </div>
                </div>
                <!--DIV SA DUGMETOM ZA OBRACUN I CHECKBOXOM CEKIRAJ SVE KRAJ-->

                <div class="troskovi_wrapper">

                    <!--DIV ZA PRIKAZ TABELE SA TROSKOVIMA POCETAK-->
                    <div class="div_tab1 konta prva_tabela">

                    </div>
                

                    <!--DIV ZA PRIKAZ TROSKOVA PO MESTU POCETAK-->
                    <div id="troskovi_po_mestu">

                    </div>
                    <!--DIV ZA PRIKAZ PRIKAZ TROSKOVA PO MESTU KRAJ-->

                </div>

                <div class="pretraga_pribava">

                    <div class="div_pretraga_pribava">
                        <input type="text" id="pretraga_tabele2" placeholder="Unesite pojam za pretragu"/>
                    </div>

                    <div class="div_pretraga_kljuc">
                        <input type="text" id="pretraga_tabele6" placeholder="Unesite pojam za pretragu"/>
                    </div>
                </div>

                <div class="tab_pribava_wrapper">
                
                    <!--DIV ZA PRIKAZ TROSKOVA PRIBAVE POCETAK-->
                    <div class="pribava_pocetna_wrapper">
                    
                    </div>
                    <!--DIV ZA PRIKAZ TROSKOVA PRIBAVE KRAJ-->
                    
                    <!--DIV ZA PRIKAZ SUME PO VO POCETAK-->
                    <div class="suma_po_vo">
                    
                    </div>
                    <!--DIV ZA PRIKAZ SUME PO VO KRAJ-->

                    <!--DIV ZA PRIKAZ TROSKOVA SA KLJUCA POCETAK-->
                    <div class="troskovi_kljuc">
                    
                    </div>
                    <!--DIV ZA PRIKAZ TROSKOVA SA KLJUCA KRAJ-->

                    <!--DIV ZA PRIKAZ OBRACUNA PRIBAVE POCETAK-->
                    <div class="obracun_pribave">
                    
                    </div>
                    <!--DIV ZA PRIKAZ OBRACUNA PRIBAVE KRAJ-->

                </div>

                <!--DIV ZA PRIKAZ OBRACUN AVR-A POCETAK--> 
                <div class="avr_obracun">
                
                </div>
                <!--DIV ZA PRIKAZ OBRACUN AVR-A KRAJ--> 

            </div>   
            <!--DIV SA SADRZAJEM KRAJ--> 

            <!--DONJE ZAGLAVLJE CELE FORME POCETAK-->
            <div id="okolod" class="noprint">
                <img class="levo" alt="" src="images/icg/tb1_leftr.gif">
                <img class="desno" alt="" src="images/icg/tb1_r.gif">
            </div>
            <!--DONJE ZAGLAVLJE CELE FORME KRAJ-->
        </div>
    </div>

    <script>

    //SACEKAJ DA SE DOKUMENT UCITA
    $(document).ready(function() {

        //RESETOVANJE GORNJE MARGINE DIVA SA SUMAMA PO VO
        $('.suma_po_vo').css({'margin-top' : '0'});

        //RESETOVANJE MARGINE I VISINE DIVA ZA PRIBAVU
        $('.pribava_pocetna_wrapper').css({'height' : '0', 'margin' : '0'});

        //RESETOVANJE MARGINE I VISINE DIVA ZA TROSKOVE SA KLJUCA
        $('.troskovi_kljuc').css({'height' : '0', 'margin' : '0'});

        //RESETOVANJE MARGINE DIVU ZA OBRACUN AVR-A
        $('.avr_obracun').css('margin-top', '0');

        //DEKLARISANJE PROMENJIVIH ZA CUVANJE TABELA SA TROSKOVIMA
        var tabela1_tab1;
        var tabela1_tab4;

        //PROMENJIVA ZA UTVRDJIVANJE DA LI JE GODINA KLIZNA
        var godina_klizna = false;

        //DEKLARISANJE GLOBALNIH PROMENJIVIH ZA DATUME KOEFICIJENATA
        var datum_pocetnog_koeficijenta;
        var datum_krajnjeg_koeficijenta;
        var datum_za_koeficijente;

        //FUNKCIJA NA KLIK DUGMETA PRIKAZI STAVKE
        $(document).on('click', '#posalji_datum', function() {

            //SAKRIVANJE ZELJENIH HTML ELEMENATA
            $('.pretraga_pribava').css({'display' : 'none'});
            $('.pribava_pocetna_wrapper').hide();
            $('.suma_po_vo').hide();
            $('.troskovi_kljuc').hide();
            $('.obracun_pribave').hide();
            $('.avr_obracun').hide();

            //SAKRIVANJE DIVA SA TROSKOVIMA PO MESTU NASTANKA I DIVA SA TABOVIMA
            $('#troskovi_po_mestu').hide();
            $('.tabovi').hide();

            //RESETOVANJE HTML-A DIVA ZA PRIKAZ TABELE SA TROSKOVIMA
            $('.konta').html('');

            //RESETOVANJE GORNJE MARGINE I VISINE DIVA SA HTML TABELOM
            $('.konta').css({'margin-top' : '0', 'height' : '0'});

            //SAKRIVANJE DIVA SA DUGMETOM OBRACUNAJ TROSKOVE
            $('.obracun').hide();

            //RESETOVANJE VREDNOSTI POLJA ZA PRETRAGU
            $("#pretraga_tabele").val('');

            //IZDVAJANJE GODINE IZ DATUMA,ZBOG DINAMICKE PROVERE DATUMA
            var krajnja_godina = '<?php echo substr($datum_za_prikaz, 0, 4) ?>';

            //UPIS PUNOG KRAJNJEG DATUMA U PROMENJIVU ZA SLANJE
            var krajnji_datum = '<?php echo substr($datum_za_prikaz, 0, 10) ?>';

            //DEKLARISANJE POCETNOG DATUMA ZA KVARTALE
            var pocetni_datum;

            //ODREDJIVANJE POCETNOG DATUMA U ZAVISNOSTI OD KRAJNJEG DATUMA
            switch (krajnji_datum) {

                case krajnja_godina + '-12-31':
                    pocetni_datum = krajnja_godina + '-01-01';
                    break;

                case krajnja_godina + '-03-31':
                    pocetni_datum = (krajnja_godina - 1) + '-04-01';
                    break;

                case krajnja_godina + '-06-30':
                    pocetni_datum = (krajnja_godina - 1) + '-07-01';
                    break;

                case krajnja_godina + '-09-30':
                    pocetni_datum = (krajnja_godina - 1) + '-10-01';
                    break;

                case krajnja_godina + '-12-31':
                    pocetni_datum = (krajnja_godina - 1) + '-01-01';
                    break;
            }
            
            //SLANJE AJAX POZIVA  U FAJL ZA DOBIJANJE TROSKOVA IZ GLAVNE KNJIGE
            $.ajax({

                url: 'ajax/prikazi_troskove.php',
                method: 'POST',
                dataType: 'json',

                data: {pocetni_datum:pocetni_datum, krajnji_datum:krajnji_datum},

                success: function(data) {

                    //console.log(data);
                    $('.troskovi_wrapper').show();

                    //PRIKAZ DIVA SA DUGMETOM OBRACUNAJ TROSKOVE
                    $('.obracun').show();
                    $('.obracun').css('display', 'flex');

                    //UPIS TABELE SA TROSKOVIMA ZA PRVU GODINU U HTML
                    $('.konta').html(data[0]);

                    //UPIS TABELE ZA PRVU GODINU U PROMENJIVU ZA PRVI TAB
                    tabela1_tab1 = data[0];

                    //AKO POSTOJI TABELA ZA DRUGU GODINU,UPISI JE U PROMENJIVU ZA 4 TAB I SETUJ PROMENJIVU ZA KLIZNU GODINU NA TRUE
                    if (data[1]) {

                        godina_klizna = true;

                        tabela1_tab4 = data[1];
                    }

                    //UPIS BUTTONA U DIV ZA PROMENU TABOVA I PRIKAZ
                    $('.tabovi').html(data[2]);
                    $('.tabovi').show();

                    //DODAVANJE GORNJE MARGINE DIVU SA HTML TABELOM
                    $('.konta').css('height', '660px');

                    //ZADAVANJE GORNJE I DONJE MARGINE DIVU SA DUGMETOM OBRACUNAJ TROSKOVE
                    $('.obracun').css({'margin-top' : '30px' , 'margin-bottom' : '15px'});
             
                    //PODESAVANJE CHECKBOXA CHECK ALL DA BUDE CEKIRAN
                    $("#check_all").prop('checked', true);

                    
                    //ZA SVAKO EDITABILNO INPUT POLJE
                    $(".input_iznos").each(function() {  

                        //OGRANICENJE NA NUMERICKU VREDNOST
                        $(this).numeric();

                        //UPIS DEFAULT-NE VREDNOSTI POLJA U PROMENJIVU ZA POVRATAK NA POCETNU VREDNOST
                        var stara_vrednost = $(this).val();


                        //FUNKCIJA NA IZMENU VREDNOSTI INPUT POLJA
                        $(this).bind('input', function() {

                            //FORMATIRANJE PROMENJENOG IZNOSA NA DVE DECIMALE
                            izmenjen_iznos = $(this).val().split(",").join('');

                            //AKO JE KORISNIK UNEO MINUS ILI PRVU CIFRU NULA,OBAVESTI GA O GRESCI I VRATI VREDNOST NA POCETNU
                            if (izmenjen_iznos == ''|| izmenjen_iznos[0] == 0) {

                                alert('Neispravna vrednost');
                                $(this).val(stara_vrednost);
                            }
                            /*
                            //AKO JE KORISNIK PREKORACIO DEFAULT-NU VREDNOST,OBAVESTI GA O GRESCI I VRATI VREDNOST NA POCETNU
                            if (izmenjen_iznos > stara_vrednost.split(",").join('')) {

                                alert('Prekoračili ste vrednost računa');
                                $(this).val(stara_vrednost);
                            }
                            */
                        })
                    }); 

                    //ENABLE-OVANJE DUGMETA ZA DOBIJANJE PRIBAVE PRVE GODINE
                    $('#drugi_tab').prop('disabled', false);
                }
            })
        });

        
        //FUNKCIJA NA KLIK BILO KOG CHECKBOXA
        $(document).on('click', '.konto_checkbox', function() {  

            //PODESAVANJE CHECKBOXA CHECK ALL DA BUDE ODCEKIRAN
            $("#check_all").prop('checked', false);

            //AKO JE CHECKBOX CEKIRAN
            if ($(this).is(':checked')) {

                //UZIMANJE ID-JA CHECKBOXA I ENABLE-OVANJE ODGOVARAJUCEG INPUT POLJA
                var id = $(this).attr('id');
                $("#iznos" + id).prop('disabled', false);

                //SETOVANJE CSS-A CEKIRANOG INPUT POLJA
                $("#iznos" + id).css('font-weight', '600');
            }

            else {

                //UZIMANJE ID-JA CHECKBOXA I DISABLE-OVANJE ODGOVARAJUCEG INPUT POLJA
                var id = $(this).attr('id');
                $("#iznos" + id).prop('disabled', true);

                //VRACANJE POLJA NA DEFAULT-NU VREDNOST
                var pocetna_vrednost =  $("#original_iznos" + id).html();
                $("#iznos" + id).val(pocetna_vrednost);

                //SETOVANJE CSS-A ODCEKIRANOG INPUT POLJA
                $("#iznos" + id).css('font-weight', 'normal');
            }
        });



        //FUNKCIJA NA KLIK CHECKBOXA CEKIRAJ SVE
        $("#check_all").click(function () {

            //SETUJ SVE CHECKBOX-OVE DA BUDU CEKIRANI ILI ODCEKIRANI
            $('input:checkbox').not(this).prop('checked', this.checked);

            //ZA SVAKI CHECKBOX
            $('.konto_checkbox').each(function() {  

                //AKO JE CHECKBOX CEKIRAN
                if ($(this).is(':checked')) {

                    //UZIMANJE ID-JA CHECKBOXA I ENABLE-OVANJE ODGOVARAJUCEG INPUT POLJA
                    var id = $(this).attr('id');
                    $("#iznos" + id).prop('disabled', false);

                    //SETOVANJE CSS-A CEKIRANOG INPUT POLJA
                    $("#iznos" + id).css('font-weight', '600');
                }

                else {

                    //UZIMANJE ID-JA CHECKBOXA I DISABLE-OVANJE ODGOVARAJUCEG INPUT POLJA
                    var id = $(this).attr('id');
                    $("#iznos" + id).prop('disabled', true);

                    //VRACANJE POLJA NA DEFAULT-NU VREDNOST
                    var pocetna_vrednost =  $("#original_iznos" + id).html();
                    $("#iznos" + id).val(pocetna_vrednost);

                    //SETOVANJE CSS-A ODCEKIRANOG INPUT POLJA
                    $("#iznos" + id).css('font-weight', 'normal');
                }
            });                
        });

        //DEKLARISANJE PROMENJIVIH ZA UPIS HTML-A TABELA KOJE SE IZVOZE U EXCEL
        var tabela_troskovi_pocetna;
        var tabela_troskovi_krajnja;
        var tabela;
        var tabela2;
        var obracun_pocetna;
        var obracun_krajnja;
        var koeficijenti_pocetna;
        var koeficijenti_krajnja;
        var obracun_prva = false;
        var obracun_druga = false;


        //FUNKCIJA NA KLIK DUGMETA OBRACUNAJ TROSKOVE
        $(document).on('click', '#obracunaj', function() { 

            //DEKLARISANJE NIZA ZA SLANJE PODATAKA
            var niz_slanje = [];

            //UZIMANJE VREDNOSTI DATUMA I FORMATIRANJE
            datum_krajnjeg_koeficijenta = '<?php echo substr($datum_za_prikaz, 0, 10) ?>';

            //PRETVARANJE KRAJNJEG DATUMA U NIZ
            var niz_datum = datum_krajnjeg_koeficijenta.split('-');

            //DOBIJANJE DATUMA KOEFICIJENTA ZA POCETNU GODINU
            datum_pocetnog_koeficijenta = [niz_datum[0] - 1, '-12-31'];
            datum_pocetnog_koeficijenta = datum_pocetnog_koeficijenta.join('');

            //PROLAZAK KROZ SVE ELEMENTE SA KLASOM DUGME TAB
            $('.dugme_tab').each(function() {

                //AKO JE AKTIVAN PRVI TAB,PODESI DATUM ZA KOEFICIJENTE
                if ($(this).hasClass('active') && $(this).attr('id') == 'prvi_tab') {

                    datum_za_koeficijente = datum_pocetnog_koeficijenta;

                    //UPIS HTML-A TABELE U PROMENJIVU
                    //tabela_troskovi_pocetna = $('.troskovi_pocetna').html();
                    tabela = document.getElementById('tabela_prva_god');
                    //tabela_troskovi_krajnja = '';
                }

                //AKO JE AKTIVAN CETVRTI TAB,PODESI DATUM ZA KOEFICIJENTE
                if ($(this).hasClass('active') && $(this).attr('id') == 'cetvrti_tab') {

                    datum_za_koeficijente = datum_krajnjeg_koeficijenta;

                    //UPIS HTML-A TABELE U PROMENJIVU
                    //DOHVATANJE TABELE SA TROSKOVIMA ZA KRAJNJU GODINU PO ID-JU
                    tabela2 = document.getElementById('tabela_krajnja_god');
                    //tabela_troskovi_pocetna = '';
                }
            });

            //SAKRIVANJE DIVA SA TROSKOVIMA PO MESTU
            $('#troskovi_po_mestu').hide();

            //SAKRIVANJE DIVA SA SUMAMA PO VO
            $('.suma_po_vo').hide();

            //FUNKCIJA ZA IZRACUNAVANJE SUME SVIH INPUTA,KOJI SU CEKIRANI
            $(".input_iznos").each(function() {  

                //AKO JE INPUT ENABLE-OVAN
                if (!$(this).prop("disabled")) {

                    //UPIS ID-JA INPUT POLJA U PROMENJIVU I IZDVAJANJE NUMERICKOG DELA
                    var id_inputa = $(this).attr('id');
                    var id_broj = id_inputa.substr(5);

                    //DOBIJANJE GRUPE I SEKTORA ZA ODGOVARAJUCI RED U TABELI I UPIS U PROMENJIVE
                    var grupa = $('#grupa' + id_broj).html();
                    var sektor = $('#sektor' + id_broj).html();
                    var grupa_plus_sektor = $('#konto' + id_broj).html().substr(0,4);

                    //UPIS EDITABILNOG IZNOSA U PROMENJIVU
                    var izmenjen_iznos = $(this).val();

                    //FORMATIRANJE VREDNOSTI NA DVE DECIMALE
                    izmenjen_iznos = izmenjen_iznos.split(",").join('');
                    izmenjen_iznos = Math.round(izmenjen_iznos * 100.0) / 100.0;

                    //DODAVANJE PODATAKA IZ CEKIRANOG REDA U NIZ SA PODACIMA(GRUPA,SEKTOR,IZNOS)
                    niz_podaci_slanje = [grupa, sektor, grupa_plus_sektor, izmenjen_iznos];

                    //DODAVANJE SVIH NIZOVA SA PODACIMA U NIZ ZA SLANJE
                    niz_slanje.push(niz_podaci_slanje);
                }
            });

            //AKO NIJE CEKIRANA NIJEDNA STAVKA OBAVESTI KORISNIKA
            if (niz_slanje.length == 0) {

                alert('Niste odabrali nijednu stavku');
            } 

            //AKO JE SVE OK,NASTAVI DALJE
            else { 

                //SERIJALIZACIJA NIZA ZA SLANJE
                niz_slanje = JSON.stringify(niz_slanje);
                //console.log(niz_slanje);

                //AKO SE RADI OBRACUN TROSKOVA ZA POCETNU GODINU,SETUJ PERIOD NA POCETNU GODINU
                if (tabela) {

                    var period = 'pocetna godina';
                }

                //AKO SE RADI OBRACUN TROSKOVA ZA KRAJNJU GODINU,SETUJ PERIOD NA KRAJNJU GDDINU
                if (tabela2) {

                    var period = 'krajnja godina';
                }

                //SLANJE AJAX POZIVA U FAJL ZA OBRACUN TROSKOVA
                $.ajax({

                    url: 'ajax/obracun_troskova.php',
                    method: 'POST',
                    dataType: 'json',

                    data: {niz_slanje:niz_slanje, datum_za_koeficijente:datum_za_koeficijente, period:period},

                    success: function(data) {
                        
                        //console.log(data);

                        $('#troskovi_po_mestu').html(data);

                        //PRIKAZ DIVA SA TROSKOVIMA PO MESTU
                        $('#troskovi_po_mestu').show();

                        if (godina_klizna === false) {

                            if (tabela) {

                                obracun_prva = true;
                                
                                //DOHVATANJE TABELE SA OBRACUNATIM TROSKOVIMA ZA POCETNU GODINU PO ID-JU
                                obracun_pocetna = document.getElementById('obracun_pocetna');

                                //DOHVATANJE TABELE SA KOEFICIJENTIMA ZA POCETNU GODINU PO ID-JU
                                koeficijenti_pocetna = document.getElementById('koeficijenti_pocetna');
                            }

                        }
                        else {
                            
                            if (tabela) {

                                obracun_prva = true;

                                //DOHVATANJE TABELE SA OBRACUNATIM TROSKOVIMA ZA POCETNU GODINU PO ID-JU
                                obracun_pocetna = document.getElementById('obracun_pocetna');

                                //DOHVATANJE TABELE SA KOEFICIJENTIMA ZA POCETNU GODINU PO ID-JU
                                koeficijenti_pocetna = document.getElementById('koeficijenti_pocetna');

                                console.log(obracun_pocetna);


                            }
                            
                            if (tabela2) {


                                obracun_druga = true;

                                //DOHVATANJE TABELE SA OBRACUNATIM TROSKOVIMA ZA KRAJNJU GODINU PO ID-JU
                                obracun_krajnja = document.getElementById('obracun_krajnja');

                                //DOHVATANJE TABELE SA KOEFICIJENTIMA ZA KRAJNJU GODINU PO ID-JU
                                koeficijenti_krajnja = document.getElementById('koeficijenti_krajnja');

                                //console.log(tabela2);
                                console.log(obracun_krajnja);
                                //console.log(koeficijenti_krajnja);
                            }
                        }
                    }
                });
                //alert(obracun);
                
            }
        });

        //FUNKCIJA NA KLIK TAB DUGMETA
        $(document).on('click', '.dugme_tab', function() {

            //PROLAZAK KROZ SVE ELEMENTE SA KLASOM DUGME TAB
            $('.dugme_tab').each(function() {

                //AKO DUGME IMA KLASU ACTIVE,UKLONI JE
                if ($(this).hasClass('active')) {

                    $(this).removeClass('active');
                }
            });

            //DODAJ KLASU ACTIVE,KLIKNUTOM DUGMETU
            $(this).addClass('active');

            //UPIS ID-JA DUGMETA U PROMENJIVU
            var id_dugmeta = $(this).attr('id');

            //AKO JE KLIKNUT PRVI TAB
            if (id_dugmeta == 'prvi_tab') {

                //RESETOVANJE VREDNOSTI POLJA ZA PRETRAGU
                $("#pretraga_tabele").val('');

                //SAKRIVANJE DIVA ZA PRETRAGU U TABU ZA PRIBAVU
                $('.pretraga_pribava').css({'display' : 'none'});

                //SAKRIVANJE I PRIKAZ ODGOVARAJUCIH HTML ELEMENATA
                $('.pribava_pocetna_wrapper').hide();
                $('.suma_po_vo').hide();
                $('.troskovi_kljuc').hide();
                $('.obracun_pribave').hide();
                $('.avr_obracun').hide();

                $('.obracun').show();
                $('.obracun').css('display', 'flex');

                $('.troskovi_wrapper').show();

                $('#troskovi_po_mestu').show();

                //ENABLE-UJ DUGME ZA DOBIJANJE PRIBAVE POCETNE GODINE
                $('#drugi_tab').prop('disabled', false);

                //SAKRIJ TABELE ZA DRUGU GODINU I PRIKAZI TABELU ZA PRVU GODINU
                $('.tabela_stavke').hide();
                $('#troskovi_po_mestu').hide();
                $('.konta').html(tabela1_tab1);
            }
            else {

                //DISABLE-OVANJE TABA ZA POCETNU GODINU
                $('#drugi_tab').prop('disabled', true);
            }

            //AKO JE KLIKNUT CETVRTI TAB
            if (id_dugmeta == 'cetvrti_tab') {

                //RESETOVANJE VREDNOSTI POLJA ZA PRETRAGU
                $("#pretraga_tabele").val('');

                //SAKRIVANJE DIVA ZA PRETRAGU U TABU ZA PRIBAVU
                $('.pretraga_pribava').css({'display' : 'none'});

                //SAKRIVANJE I PRIKAZ ODGOVARAJUCIH HTML ELEMENATA
                $('.pribava_pocetna_wrapper').hide();
                $('.suma_po_vo').hide();
                $('.troskovi_kljuc').hide();
                $('.obracun_pribave').hide();
                $('.avr_obracun').hide();

                $('.obracun').show();
                $('.obracun').css('display', 'flex');

                $('.troskovi_wrapper').show();

                //ENABLE-UJ DUGME ZA DOBIJANJE PRIBAVE KRAJNJE GODINE
                $('#peti_tab').prop('disabled', false);

                //SAKRIJ TABELE ZA PRVU GODINU I PRIKAZI TABELU ZA DRUGU GODINU
                $('.tabela_stavke').hide();
                $('#troskovi_po_mestu').hide();
                $('.konta').html(tabela1_tab4);
            }
            else {

                //DISABLE-OVANJE TABA ZA KRAJNJU GODINU
                $('#peti_tab').prop('disabled', true);
            }
        });

            //DEKLARISANJE NIZOVA ZA UPIS VREDNOSTI POLJA DIREKTNO + KLJUC IZ TABELA ZA PRIBAVU OBE GODINE
            var zbir_pocetna_god = [];
            var zbir_krajnja_god = [];
            var sve_vo = [];

            //FUNKCIJA NA KLIK TABA PRIBAVA ZA PRVU GODINU
            $(document).on('click', '#drugi_tab', function() {

                //RESETOVANJE NIZA
                zbir_pocetna_god = [];

                //UPIS PERIODA IZ PRVOG TABA U PROMENJIVU
                var period_pocetna_godina = $('#prvi_tab').html().substring(14);
                
                //INICIJALIZACIJA NIZOVA ZA SLANJE PODATAKA
                var niz_sektor2 = [];
                var niz_sektor6 = [];

                //FUNKCIJA ZA UZIMANJE PODATAKA SVIH INPUTA,KOJI SU CEKIRANI
                $(".pocetna_godina").each(function() {  

                    //AKO JE INPUT ENABLE-OVAN
                    if (!$(this).prop("disabled")) {

                        //UPIS ID-JA INPUT POLJA U PROMENJIVU I IZDVAJANJE NUMERICKOG DELA
                        var id_inputa = $(this).attr('id');
                        var id_broj = id_inputa.substr(5);

                        //DOBIJANJE GRUPE,SEKTORA,KONTA I OPISA ZA ODGOVARAJUCI RED U TABELI I UPIS U PROMENJIVE
                        var grupa = $('#grupa' + id_broj).html();
                        var sektor = $('#sektor' + id_broj).html();
                        var konto = $('#konto' + id_broj).html();
                        var opis = $('#opis' + id_broj).html();

                        //UPIS EDITABILNOG IZNOSA U PROMENJIVU
                        var izmenjen_iznos = $(this).val();

                        //FORMATIRANJE VREDNOSTI NA DVE DECIMALE
                        izmenjen_iznos = izmenjen_iznos.split(",").join('');
                        izmenjen_iznos = Math.round(izmenjen_iznos * 100.0) / 100.0;

                        //AKO JE SEKTOR KONTA 2,UPISI RED U NIZ ZA SEKTOR 2
                        if (sektor == '2') {

                            podaci_sektor2 = [grupa, sektor, konto, opis, izmenjen_iznos];
                            niz_sektor2.push(podaci_sektor2);
                        }

                        //AKO JE SEKTOR KONTA 6,UPISI RED U NIZ ZA SEKTOR 6
                        if (sektor == '6') {

                            podaci_sektor6 = [konto, opis, izmenjen_iznos];
                            niz_sektor6.push(podaci_sektor6);
                        }
                    }
                });

                //AKO NIZ ZA SEKTOR 2,I NIZ ZA SEKTOR 6 IMAJU BAR JEDAN CLAN
                if (niz_sektor2.length > 0 && niz_sektor6.length > 0) {

                    //console.log(niz_sektor6);
                    //console.log(niz_sektor2);

                    //DEKLARISANJE PROMENJIVE SA NAZIVOM FUNKCIJE
                    var funkcija = 'troskovi pribave';
                    
                    //SERIJALIZACIJA NIZOVA ZA SLANJE
                    niz_sektor2 = JSON.stringify(niz_sektor2);
                    niz_sektor6 = JSON.stringify(niz_sektor6);

                    //SLANJE AJAX POZIVA U FAJL ZA OBRACUN TROSKOVA PO MESTU NASTANKA
                    $.ajax({

                        url: 'ajax/obracun_troskova.php',
                        method: 'POST',
                        dataType: 'json',

                        data: {niz_sektor2:niz_sektor2, niz_sektor6:niz_sektor6, period_pocetna_godina:period_pocetna_godina, funkcija:funkcija},

                        success: function(data) {

                            //console.log(data);
                            
                            //SAKRIVANJE ZELJENIH DIVOVA
                            $('.obracun').hide();
                            $('.troskovi_wrapper').hide();

                            //PRIKAZIVANJE DIVA ZA PRETRAGU U TABU ZA PRIBAVU
                            $('.pretraga_pribava').css({'display' : 'flex'});

                            //RESETOVANJE POLJA ZA PRETRAGU
                            $("#pretraga_tabele2").val('');
                            $("#pretraga_tabele6").val('');

                            //ZADAVANJE VISINE I MARGINA DIVU ZA PRIBAVU I PRIKAZIVANJE
                            $('.pribava_pocetna_wrapper').css({'height' : '660px', 'margin' : '25px 50px 0 50px'});
                            $('.pribava_pocetna_wrapper').show();

                            //ZADAVANJE GORNJE MARGINE I PRIKAZ DIVA SA SUMAMA PO VO
                            $('.suma_po_vo').css({'margin-top' : '25px'});
                            $('.suma_po_vo').show();

                            //ZADAVANJE GORNJE MARGINE I PRIKAZ DIVA ZA TROSKOVE SA KLJUCA
                            $('.troskovi_kljuc').css({'height' : '660px', 'margin' : '25px 50px 0 0'});
                            $('.troskovi_kljuc').show();

                            //ZADAVANJE MARGINA I PRIKAZ DIVA ZA OBRACUN PRIBAVE
                            $('.obracun_pribave').css({'height' : '660px', 'margin' : '25px 50px 0 0'});
                            $('.obracun_pribave').show();

                            //UPIS TABELA U ODGOVARAJUCE DIVOVE
                            $('.pribava_pocetna_wrapper').html(data[0]);
                            $('.suma_po_vo').html(data[1]);
                            $('.troskovi_kljuc').html(data[2]);
                            $('.obracun_pribave').html(data[3]);

                            //RESETOVANJE NIZA
                            sve_vo = [];

                            //PROMENA KLASE POLJA DIREKTNO + KLJUC,U ZAVISNOSTI OD GODINE PRIBAVE
                            $(".direktno_plus_kljuc_krajnja").each(function() {  

                                $(this).removeClass('direktno_plus_kljuc_krajnja');
                                $(this).addClass('direktno_plus_kljuc_pocetna');
                            });

                            //UPIS SVIH VRSTA OSIGURANJA U NIZ
                            $(".vrsta_osiguranja_pocetna").each(function() {  

                                var vrsta_osiguranja_pocetna = $(this).html();
                                sve_vo.push(vrsta_osiguranja_pocetna);
                            });

                            //DEKLARISANJE NIZA ZA UPIS VREDNOSTI POLJA TABELE DIREKTNO + KLJUC
                            var zbir_pocetna = [];

                            //UZIMANJE VREDNOSTI POLJA TABELE DIREKTNO + KLJUC I UPIS U NIZ
                            $(".direktno_plus_kljuc_pocetna").each(function() {  

                                var iznos_pocetna = $(this).html();
                                zbir_pocetna.push(iznos_pocetna);
                            });

                            //PROLAZAK KROZ NIZ SA SVIM VRSTAMA OSIGURANJA
                            for (var i = 0; i < sve_vo.length-1; i++) {

                                //PROLAZAK KROZ NIZ SA SVIM IZNOSIMA DIREKTNO + KLJUC
                                for (var j = 0; j < zbir_pocetna.length-1; j++) {

                                    //AKO SU INDEKSI JEDNAKI
                                    if (i == j) {

                                        //KREIRAJ NIZ SA VRSTOM OSIGURANJA I IZNOSOM
                                        $niz_pocetna = [sve_vo[i], zbir_pocetna[j]];

                                        //DODAJ NIZ U NIZ ZA POCETNU GODINU
                                        zbir_pocetna_god.push($niz_pocetna);
                                    }
                                }
                            }

                            //console.log(zbir_pocetna_god);
                        }
                    });
                }
                //AKO NIJE CEKIRANA NIJEDNA STAVKA
                else {
                    alert('Niste odabrali nijednu stavku.');
                    location.reload();
                }
            });


            //FUNKCIJA NA KLIK TABA PRIBAVA ZA KRAJNJU GODINU
            $(document).on('click', '#peti_tab', function() {

                //RESETOVANJE NIZA
                zbir_krajnja_god = [];

                //UPIS PERIODA IZ CETVRTOG TABA U PROMENJIVU
                var period_krajnja_godina = $('#cetvrti_tab').html().substring(14);

                //INICIJALIZACIJA NIZOVA ZA SLANJE PODATAKA
                var niz_sektor2 = [];
                var niz_sektor6 = [];

                //FUNKCIJA ZA UZIMANJE PODATAKA SVIH INPUTA,KOJI SU CEKIRANI
                $(".krajnja_godina").each(function() {  

                    //AKO JE INPUT ENABLE-OVAN
                    if (!$(this).prop("disabled")) {

                        //UPIS ID-JA INPUT POLJA U PROMENJIVU I IZDVAJANJE NUMERICKOG DELA
                        var id_inputa = $(this).attr('id');
                        var id_broj = id_inputa.substr(5);

                        //DOBIJANJE GRUPE,SEKTORA,KONTA I OPISA ZA ODGOVARAJUCI RED U TABELI I UPIS U PROMENJIVE
                        var grupa = $('#grupa' + id_broj).html();
                        var sektor = $('#sektor' + id_broj).html();
                        var konto = $('#konto' + id_broj).html();
                        var opis = $('#opis' + id_broj).html();

                        //UPIS EDITABILNOG IZNOSA U PROMENJIVU
                        var izmenjen_iznos = $(this).val();

                        //FORMATIRANJE VREDNOSTI NA DVE DECIMALE
                        izmenjen_iznos = izmenjen_iznos.split(",").join('');
                        izmenjen_iznos = Math.round(izmenjen_iznos * 100.0) / 100.0;

                        //AKO JE SEKTOR KONTA 2,UPISI RED U NIZ ZA SEKTOR 2
                        if (sektor == '2') {

                            podaci_sektor2 = [grupa, sektor, konto, opis, izmenjen_iznos];
                            niz_sektor2.push(podaci_sektor2);
                        }

                        //AKO JE SEKTOR KONTA 6,UPISI RED U NIZ ZA SEKTOR 6
                        if (sektor == '6') {

                            podaci_sektor6 = [konto, opis, izmenjen_iznos];
                            niz_sektor6.push(podaci_sektor6);
                        }
                    }
                });

                //AKO NIZ ZA SEKTOR 2,I NIZ ZA SEKTOR 6 IMAJU BAR JEDAN CLAN
                if (niz_sektor2.length > 0 && niz_sektor6.length > 0) {

                    //console.log(niz_sektor6);
                    //console.log(niz_sektor2);

                    //DEKLARISANJE PROMENJIVE SA NAZIVOM FUNKCIJE
                    var funkcija = 'troskovi pribave';
                    
                    //SERIJALIZACIJA NIZOVA ZA SLANJE
                    niz_sektor2 = JSON.stringify(niz_sektor2);
                    niz_sektor6 = JSON.stringify(niz_sektor6);

                    //SLANJE AJAX POZIVA U FAJL ZA OBRACUN TROSKOVA PO MESTU NASTANKA
                    $.ajax({

                        url: 'ajax/obracun_troskova.php',
                        method: 'POST',
                        dataType: 'json',

                        data: {niz_sektor2:niz_sektor2, niz_sektor6:niz_sektor6, period_krajnja_godina:period_krajnja_godina, funkcija:funkcija},

                        success: function(data) {

                            //console.log(data);

                            //SAKRIVANJE ZELJENIH DIVOVA
                            $('.obracun').hide();
                            $('.troskovi_wrapper').hide();

                            //PRIKAZIVANJE DIVA ZA PRETRAGU U TABU ZA PRIBAVU
                            $('.pretraga_pribava').css({'display' : 'flex'});

                            //RESETOVANJE POLJA ZA PRETRAGU
                            $("#pretraga_tabele2").val('');
                            $("#pretraga_tabele6").val('');

                            //ZADAVANJE VISINE I MARGINA DIVU ZA PRIBAVU I PRIKAZIVANJE
                            $('.pribava_pocetna_wrapper').css({'height' : '660px', 'margin' : '25px 50px 0 50px'});
                            $('.pribava_pocetna_wrapper').show();
                            
                            //ZADAVANJE GORNJE MARGINE I PRIKAZ DIVA SA SUMAMA PO VO
                            $('.suma_po_vo').css({'margin-top' : '25px'});
                            $('.suma_po_vo').show();

                            //ZADAVANJE MARGINA I PRIKAZ DIVA ZA TROSKOVE SA KLJUCA
                            $('.troskovi_kljuc').css({'height' : '660px', 'margin' : '25px 50px 0 0'});
                            $('.troskovi_kljuc').show();

                            //ZADAVANJE MARGINA I PRIKAZ DIVA ZA OBRACUN PRIBAVE
                            $('.obracun_pribave').css({'height' : '660px', 'margin' : '25px 50px 0 0'});
                            $('.obracun_pribave').show();

                            //UPIS TABELA U ODGOVARAJUCE DIVOVE
                            $('.pribava_pocetna_wrapper').html(data[0]);
                            $('.suma_po_vo').html(data[1]);
                            $('.troskovi_kljuc').html(data[2]);
                            $('.obracun_pribave').html(data[3]);

                            //PROMENA KLASE POLJA DIREKTNO + KLJUC,U ZAVISNOSTI OD GODINE PRIBAVE
                            $(".direktno_plus_kljuc_pocetna").each(function() {  

                                $(this).removeClass('direktno_plus_kljuc_pocetna');
                                $(this).addClass('direktno_plus_kljuc_krajnja');
                            });

                            //RESETOVANJE NIZA 
                            sve_vo = [];

                            //UPIS SVIH VRSTA OSIGURANJA U NIZ
                            $(".vrsta_osiguranja_pocetna").each(function() {  

                                var vrsta_osiguranja_pocetna = $(this).html();
                                sve_vo.push(vrsta_osiguranja_pocetna);
                            });

                            //DEKLARISANJE NIZA ZA UPIS VREDNOSTI POLJA TABELE DIREKTNO + KLJUC
                            var zbir_krajnja = [];

                            //UZIMANJE VREDNOSTI POLJA TABELE DIREKTNO + KLJUC I UPIS U NIZ
                            $(".direktno_plus_kljuc_krajnja").each(function() {  

                                var iznos_krajnja = $(this).html();
                                zbir_krajnja.push(iznos_krajnja);
                            });

                            //PROLAZAK KROZ NIZ SA SVIM VRSTAMA OSIGURANJA
                            for (var i = 0; i < sve_vo.length-1; i++) {

                                //PROLAZAK KROZ NIZ SA SVIM IZNOSIMA DIREKTNO + KLJUC
                                for (var j = 0; j < zbir_krajnja.length-1; j++) {

                                    //AKO SU INDEKSI JEDNAKI
                                    if (i == j) {

                                        //KREIRAJ NIZ SA VRSTOM OSIGURANJA I IZNOSOM
                                        $niz_krajnja = [sve_vo[i], zbir_krajnja[j]];

                                        //DODAJ NIZ U NIZ ZA POCETNU GODINU
                                        zbir_krajnja_god.push($niz_krajnja);
                                    }
                                }
                            }

                            //console.log(zbir_krajnja_god);
                        }
                    });
                }
                //AKO NIJE CEKIRANA NIJEDNA STAVKA
                else {
                    alert('Niste odabrali nijednu stavku.');
                    location.reload();
                }
            });

            //FUNKCIJA NA KLIK TABA UKUPNO,ZA OBRACUN AVR-A
            $(document).on('click', '#ukupno_tab', function() {

                //UZIMANJE DATUMA IZ INPUT POLJA I PRETVARANJE U NIZ
                var datum_input = $('#datum').val().substring(0, 10);
                datum_input = datum_input.split(".");

                //DINAMICKO KREIRANJE KRAJNJEG DATUMA ZA UPIT NAD TABELOM PP_DATUM
                var datum_za_upit = datum_input[2].substring(2) + datum_input[1] + datum_input[0];

                //UZIMANJE DATUMA ZA ZAGLAVLJE TABELE U TABU UKUPNO
                var tabela_pocetni_datum = $('#prvi_tab').html();

                //AKO JE GODINA KLIZNA
                if (godina_klizna === true) {

                    //AKO JE OBRACUNATA PRIBAVA ZA OBE GODINE
                    if (zbir_pocetna_god.length > 0 && zbir_krajnja_god.length > 0) {

                        //console.log(sve_vo);
                        //console.log(zbir_pocetna_god);
                        //console.log(zbir_krajnja_god);

                        //UZIMANJE DATUMA ZA ZAGLAVLJE TABELE U TABU UKUPNO
                        var tabela_krajnji_datum = $('#cetvrti_tab').html();

                        //DINAMICKO DOBIJANJE PRETHODNE GODINE ZA AVR STANJE
                        var godina_avr_stanja = datum_input[2] - 1;

                        //KREIRANJE PROMENJIVE SA NAZIVOM FUNKCIJE
                        var naziv_funkcije = 'ukupni_obracun_avr';

                        //SLANJE AJAX POZIVA U FAJL ZA OBRACUN TROSKOVA
                        $.ajax({

                            url: 'ajax/obracun_troskova.php',
                            method: 'POST',
                            dataType: 'json',

                            data: {
                                sve_vo:sve_vo, 
                                zbir_pocetna_god:zbir_pocetna_god, 
                                zbir_krajnja_god:zbir_krajnja_god, 
                                datum_za_upit:datum_za_upit, 
                                godina_avr_stanja:godina_avr_stanja,
                                tabela_pocetni_datum:tabela_pocetni_datum, 
                                tabela_krajnji_datum:tabela_krajnji_datum,
                                naziv_funkcije:naziv_funkcije},

                            success: function(data) {

                                console.log(data);

                                //SAKRIVANJE I PRIKAZ ODGOVARAJUCIH HTML ELEMENATA
                                $('.pretraga_pribava').css({'display' : 'none'});
                                $('.pribava_pocetna_wrapper').hide();
                                $('.suma_po_vo').hide();
                                $('.troskovi_kljuc').hide();
                                $('.obracun_pribave').hide();
                                $('.obracun').hide();
                                $('.troskovi_wrapper').hide();

                                $('.avr_obracun').css('margin-top', '30px');
                                $('.avr_obracun').show();
                                $('.avr_obracun').html(data);
                                $('#export_excel').show();
                            }
                        });
                    }
                    //AKO NIJE OBRACUNATA PRIBAVA ZA OBE GODINE
                    else {
                        alert('Niste obračunali pribavu za obe godine');
                    }
                }

                //AKO GODINA NIJE KLIZNA
                if (godina_klizna === false) {

                    //AKO JE OBRACUNATA PRIBAVA
                    if (zbir_pocetna_god.length > 0) {

                        //console.log(sve_vo);
                        //console.log(zbir_pocetna_god);

                        //KREIRANJE PROMENJIVE SA NAZIVOM FUNKCIJE
                        var naziv_funkcije = 'ukupni_obracun_avr';
                        
                        //SLANJE AJAX POZIVA U FAJL ZA OBRACUN TROSKOVA PO MESTU NASTANKA
                        $.ajax({

                            url: 'ajax/obracun_troskova.php',
                            method: 'POST',
                            dataType: 'json',

                            data: {
                                sve_vo:sve_vo, 
                                zbir_pocetna_god:zbir_pocetna_god, 
                                datum_za_upit:datum_za_upit,
                                tabela_pocetni_datum:tabela_pocetni_datum,  
                                naziv_funkcije:naziv_funkcije},

                            success: function(data) {

                                console.log(data);

                                //SAKRIVANJE I PRIKAZ ODGOVARAJUCIH HTML ELEMENATA
                                $('.pretraga_pribava').css({'display' : 'none'});
                                $('.pribava_pocetna_wrapper').hide();
                                $('.suma_po_vo').hide();
                                $('.troskovi_kljuc').hide();
                                $('.obracun_pribave').hide();
                                $('.obracun').hide();
                                $('.troskovi_wrapper').hide();
                                $('.avr_obracun').show();
                                $('.avr_obracun').html(data);
                                $('#export_excel').show();
                            }
                        });
                        
                    }
                    //AKO NIJE OBRACUNATA PRIBAVA
                    else {
                        alert('Niste obračunali pribavu za tekuću godinu.');
                    }
                }
            });


            //FUNKCIJA NA KLIK DUGMETA IZVEZI U EXCEL
            $('#export_excel').click(function() {

                console.log(obracun_pocetna);

                alert('Obracun prva ' + obracun_prva);
                alert('Obracun druga ' + obracun_druga);
                alert('Godina klizna ' + godina_klizna);
                /*
                if (godina_klizna === false) {

                    //INICIJALIZACIJA PRAZNOG NIZA
                    var troskovi_pocetna = [];

                    //DOHVATANJE TABELE SA TROSKOVIMA ZA POCETNU GODINU PO ID-JU
                    //var tabela = document.getElementById('tabela_prva_god');

                    //PROMENJIVA ZA DODELJIVANJE INDEKSA NIZU
                    var indeks_niza = 0;
                    
                    //PROLAZAK KROZ SVE REDOVE
                    for (var i = 0; i < tabela.rows.length;  i++) {

                        //PRESKAKANJE ZAGLAVLJA TABELE
                        if (i > 0) {

                            //DOHVATANJE CHECKBOXA PO ID-JU
                            var checkBox = document.getElementById(i);

                                //AKO JE CHECKBOX CEKIRAN
                                if (checkBox.checked == true) {

                                //KREIRANJE PRAZNIH NIZOVA SA TRENUTNIM INDEKSOM
                                troskovi_pocetna[indeks_niza] = [];
                                
                                //PROLAZAK KROZ SVE CELIJE U TRENUTNOM REDU TABELE,BEZ POSLEDNJE DVE
                                for (var j = 0; j < tabela.rows[i].cells.length-2; j++) {

                                    //DODAVANJE SADRZAJA TD POLJA IZ REDA TABELE U ODGOVARAJUCI INDEKS NIZA
                                    troskovi_pocetna[indeks_niza].push(tabela.rows[i].cells[j].innerHTML);
                                }

                                //UVECANJE INDEKSA NIZA ZA 1
                                indeks_niza++;
                            }
                        }
                    }

                    console.log(troskovi_pocetna);

                    //INICIJALIZACIJA PRAZNOG NIZA
                    var obracun_pocetni_niz = [];

                    //DOHVATANJE TABELE SA TROSKOVIMA ZA POCETNU GODINU PO ID-JU
                    //var obracun_pocetna = document.getElementById('obracun_pocetna');

                    //PROLAZAK KROZ SVE REDOVE
                    for (var i = 0; i < obracun_pocetna.rows.length;  i++) {

                        //KREIRANJE PRAZNIH NIZOVA SA TRENUTNIM INDEKSOM
                        obracun_pocetni_niz[i] = [];

                        //PROLAZAK KROZ SVE CELIJE U TRENUTNOM REDU TABELE
                        for (var j = 0; j < obracun_pocetna.rows[i].cells.length; j++) {

                            //DODAVANJE SADRZAJA TD POLJA IZ REDA TABELE U ODGOVARAJUCI INDEKS NIZA
                            obracun_pocetni_niz[i].push(obracun_pocetna.rows[i].cells[j].innerHTML);
                        }
                    }

                    //BRISANJE PRVOG CLANA,TJ.ZAGLAVLJA TABELA IZ NIZA
                    obracun_pocetni_niz.shift();
                    console.log(obracun_pocetni_niz);


                    //INICIJALIZACIJA PRAZNOG NIZA
                    var koeficijenti_pocetni_niz = [];

                    //DOHVATANJE TABELE SA KOEFICIJENTIMA ZA POCETNU GODINU PO ID-JU
                    //var koeficijenti_pocetna = document.getElementById('koeficijenti_pocetna');

                    //PROLAZAK KROZ SVE REDOVE
                    for (var i = 0; i < koeficijenti_pocetna.rows.length;  i++) {

                        //KREIRANJE PRAZNIH NIZOVA SA TRENUTNIM INDEKSOM
                        koeficijenti_pocetni_niz[i] = [];

                        //PROLAZAK KROZ SVE CELIJE U TRENUTNOM REDU TABELE
                        for (var j = 0; j < koeficijenti_pocetna.rows[i].cells.length; j++) {

                            //DODAVANJE SADRZAJA TD POLJA IZ REDA TABELE U ODGOVARAJUCI INDEKS NIZA
                            koeficijenti_pocetni_niz[i].push(koeficijenti_pocetna.rows[i].cells[j].innerHTML);
                        }
                    }

                    console.log(koeficijenti_pocetni_niz);

                    var datum_pocetna_godina = $('#prvi_tab').html();

                    //SERIJALIZACIJA NIZA ZA SLANJE
                    troskovi_pocetna = JSON.stringify(troskovi_pocetna);
                    obracun_pocetni_niz = JSON.stringify(obracun_pocetni_niz);
                    koeficijenti_pocetni_niz = JSON.stringify(koeficijenti_pocetni_niz);
                    /*
                    //SLANJE AJAX POZIVA U FAJL ZA GENERISANJE EXCEL FAJLA
                    $.ajax({

                        url: 'dodaci/funkcija_xls.php',
                        method: 'POST',
                        dataType: 'json',

                        data: {troskovi_pocetna:troskovi_pocetna, obracun_pocetni_niz:obracun_pocetni_niz, koeficijenti_pocetni_niz:koeficijenti_pocetni_niz,datum_pocetna_godina:datum_pocetna_godina},

                        success: function(data) {

                            //REDIREKCIJA NA PUTANJU EXCEL FAJLA
                            location.href = data;

                            console.log(data);
                        },

                        error: function(jqXHR, textStatus, errorThrown) {
                            console.log(textStatus, errorThrown);
                        }
                    });
                    
                }
                */
                
                //AKO JE GODINA KLIZNA
                if (godina_klizna === true) {
                    
                    //AKO SU OBRACUNATI TROSKOVI ZA OBE GODINE
                    if (obracun_prva && obracun_druga) {

                        console.log(tabela);
                        console.log(tabela2);

                        //INICIJALIZACIJA PRAZNOG NIZA
                        var troskovi_pocetna = [];

                        //DOHVATANJE TABELE SA TROSKOVIMA ZA POCETNU GODINU PO ID-JU
                        //var tabela = document.getElementById('tabela_prva_god');

                        //PROMENJIVA ZA DODELJIVANJE INDEKSA NIZU
                        var indeks_niza_pocetna = 0;

                        //PROLAZAK KROZ SVE REDOVE
                        for (var i = 0; i < tabela.rows.length;  i++) {
                            /*
                            //PRESKAKANJE ZAGLAVLJA TABELE
                            if (i > 0) {

                                //DOHVATANJE CHECKBOXA PO ID-JU
                                var checkBox = document.getElementById(i);
                                console.log(checkBox);

                                //AKO JE CHECKBOX CEKIRAN
                                if (checkBox.checked == true) {

                                    //KREIRANJE PRAZNIH NIZOVA SA TRENUTNIM INDEKSOM
                                    troskovi_pocetna[indeks_niza_pocetna] = [];
                                    
                                    //PROLAZAK KROZ SVE CELIJE U TRENUTNOM REDU TABELE,BEZ POSLEDNJE DVE
                                    for (var j = 0; j < tabela.rows[i].cells.length-2; j++) {

                                        //DODAVANJE SADRZAJA TD POLJA IZ REDA TABELE U ODGOVARAJUCI INDEKS NIZA
                                        troskovi_pocetna[indeks_niza_pocetna].push(tabela.rows[i].cells[j].innerHTML);
                                    }

                                    //UVECANJE INDEKSA NIZA ZA 1
                                    indeks_niza_pocetna++;
                                }
                            }
                            */
                        }
                        console.log('Broj redova prva ' + i);

                        console.log(troskovi_pocetna);
                    
                        //INICIJALIZACIJA PRAZNOG NIZA
                        var obracun_pocetni_niz = [];

                        //DOHVATANJE TABELE SA TROSKOVIMA ZA POCETNU GODINU PO ID-JU
                        //var obracun_pocetna = document.getElementById('obracun_pocetna');

                        //PROLAZAK KROZ SVE REDOVE
                        for (var i = 0; i < obracun_pocetna.rows.length;  i++) {

                            //KREIRANJE PRAZNIH NIZOVA SA TRENUTNIM INDEKSOM
                            obracun_pocetni_niz[i] = [];

                            //PROLAZAK KROZ SVE CELIJE U TRENUTNOM REDU TABELE
                            for (var j = 0; j < obracun_pocetna.rows[i].cells.length; j++) {

                                //DODAVANJE SADRZAJA TD POLJA IZ REDA TABELE U ODGOVARAJUCI INDEKS NIZA
                                obracun_pocetni_niz[i].push(obracun_pocetna.rows[i].cells[j].innerHTML);
                            }
                        }

                        //BRISANJE PRVOG CLANA,TJ.ZAGLAVLJA TABELA IZ NIZA
                        obracun_pocetni_niz.shift();
                        console.log(obracun_pocetni_niz);


                        //INICIJALIZACIJA PRAZNOG NIZA
                        var koeficijenti_pocetni_niz = [];

                        //DOHVATANJE TABELE SA KOEFICIJENTIMA ZA POCETNU GODINU PO ID-JU
                        //var koeficijenti_pocetna = document.getElementById('koeficijenti_pocetna');

                        //PROLAZAK KROZ SVE REDOVE
                        for (var i = 0; i < koeficijenti_pocetna.rows.length;  i++) {

                            //KREIRANJE PRAZNIH NIZOVA SA TRENUTNIM INDEKSOM
                            koeficijenti_pocetni_niz[i] = [];

                            //PROLAZAK KROZ SVE CELIJE U TRENUTNOM REDU TABELE
                            for (var j = 0; j < koeficijenti_pocetna.rows[i].cells.length; j++) {

                                //DODAVANJE SADRZAJA TD POLJA IZ REDA TABELE U ODGOVARAJUCI INDEKS NIZA
                                koeficijenti_pocetni_niz[i].push(koeficijenti_pocetna.rows[i].cells[j].innerHTML);
                            }
                        }

                        console.log(koeficijenti_pocetni_niz);

                        var datum_pocetna_godina = $('#prvi_tab').html();

                        //SERIJALIZACIJA NIZA ZA SLANJE
                        troskovi_pocetna = JSON.stringify(troskovi_pocetna);
                        obracun_pocetni_niz = JSON.stringify(obracun_pocetni_niz);
                        koeficijenti_pocetni_niz = JSON.stringify(koeficijenti_pocetni_niz);


                        

                        //INICIJALIZACIJA PRAZNOG NIZA
                        var troskovi_krajnja = [];

                        //DOHVATANJE TABELE SA TROSKOVIMA ZA KRAJNJU GODINU PO ID-JU
                        //var tabela2 = document.getElementById('tabela_krajnja_god');

                        //PROMENJIVA ZA DODELJIVANJE INDEKSA NIZU
                        var indeks_niza = 0;

                        //PROLAZAK KROZ SVE REDOVE
                        for (var i = 0; i < tabela2.rows.length;  i++) {

                            //PRESKAKANJE ZAGLAVLJA TABELE
                            if (i > 0) {

                                //DOHVATANJE CHECKBOXA PO ID-JU
                                var checkBox2 = document.getElementById(i);

                                //AKO JE CHECKBOX CEKIRAN
                                if (checkBox2.checked == true) {

                                    //KREIRANJE PRAZNIH NIZOVA SA TRENUTNIM INDEKSOM
                                    troskovi_krajnja[indeks_niza] = [];
                                    
                                    //PROLAZAK KROZ SVE CELIJE U TRENUTNOM REDU TABELE,BEZ POSLEDNJE DVE
                                    for (var j = 0; j < tabela2.rows[i].cells.length-2; j++) {

                                        //DODAVANJE SADRZAJA TD POLJA IZ REDA TABELE U ODGOVARAJUCI INDEKS NIZA
                                        troskovi_krajnja[indeks_niza].push(tabela2.rows[i].cells[j].innerHTML);
                                    }

                                    //UVECANJE INDEKSA NIZA ZA 1
                                    indeks_niza++;
                                }
                            }
                        }
                        console.log('Broj redova druga ' + i);

                        console.log(troskovi_krajnja);

                        //INICIJALIZACIJA PRAZNOG NIZA
                        var obracun_krajnji_niz = [];

                        //DOHVATANJE TABELE SA TROSKOVIMA ZA KRAJNJU GODINU PO ID-JU
                        //var obracun_krajnja = document.getElementById('obracun_krajnja');

                        //PROLAZAK KROZ SVE REDOVE
                        for (var i = 0; i < obracun_krajnja.rows.length;  i++) {

                            //KREIRANJE PRAZNIH NIZOVA SA TRENUTNIM INDEKSOM
                            obracun_krajnji_niz[i] = [];

                            //PROLAZAK KROZ SVE CELIJE U TRENUTNOM REDU TABELE
                            for (var j = 0; j < obracun_krajnja.rows[i].cells.length; j++) {

                                //DODAVANJE SADRZAJA TD POLJA IZ REDA TABELE U ODGOVARAJUCI INDEKS NIZA
                                obracun_krajnji_niz[i].push(obracun_krajnja.rows[i].cells[j].innerHTML);
                            }
                        }

                        //BRISANJE PRVOG CLANA,TJ.ZAGLAVLJA TABELA IZ NIZA
                        obracun_krajnji_niz.shift();
                        console.log(obracun_krajnji_niz);


                        //INICIJALIZACIJA PRAZNOG NIZA
                        var koeficijenti_krajnji_niz = [];

                        //DOHVATANJE TABELE SA KOEFICIJENTIMA ZA KRAJNJU GODINU PO ID-JU
                        ///var koeficijenti_krajnja = document.getElementById('koeficijenti_krajnja');

                        //PROLAZAK KROZ SVE REDOVE
                        for (var i = 0; i < koeficijenti_krajnja.rows.length;  i++) {

                            //KREIRANJE PRAZNIH NIZOVA SA TRENUTNIM INDEKSOM
                            koeficijenti_krajnji_niz[i] = [];

                            //PROLAZAK KROZ SVE CELIJE U TRENUTNOM REDU TABELE
                            for (var j = 0; j < koeficijenti_krajnja.rows[i].cells.length; j++) {

                                //DODAVANJE SADRZAJA TD POLJA IZ REDA TABELE U ODGOVARAJUCI INDEKS NIZA
                                koeficijenti_krajnji_niz[i].push(koeficijenti_krajnja.rows[i].cells[j].innerHTML);
                            }
                        }

                        console.log(koeficijenti_krajnji_niz);

                        var datum_krajnja_godina = $('#cetvrti_tab').html();

                        //SERIJALIZACIJA NIZA ZA SLANJE
                        troskovi_krajnja = JSON.stringify(troskovi_krajnja);
                        obracun_krajnji_niz = JSON.stringify(obracun_krajnji_niz);
                        koeficijenti_krajnji_niz = JSON.stringify(koeficijenti_krajnji_niz);
                        
                    }
                    else {
                        alert('Uradite obračun troškova za obe godine.');
                    }
                }
                
             
                /*
                //SLANJE AJAX POZIVA U FAJL ZA GENERISANJE EXCEL FAJLA
                $.ajax({

                    url: 'dodaci/funkcija_xls.php',
                    method: 'POST',
                    dataType: 'json',

                    data: {troskovi_pocetna:troskovi_pocetna, obracun_pocetni_niz:obracun_pocetni_niz, koeficijenti_pocetni_niz:koeficijenti_pocetni_niz,datum_pocetna_godina:datum_pocetna_godina},

                    success: function(data) {

                        //REDIREKCIJA NA PUTANJU EXCEL FAJLA
                        location.href = data;

                        console.log(data);
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log(textStatus, errorThrown);
                    }
                });
                */
            });
     
    })

       

    </script>

    <script>
    //SACEKAJ DA SE DOKUMENT UCITA
    $(document).ready(function(){

        //RESETOVANJE VREDNOSTI POLJA ZA PRETRAGU
        $("#pretraga_tabele").val('');
        $("#pretraga_tabele2").val('');
        $("#pretraga_tabele6").val('');

        //FUNKCIJA NA PRETRAGU TABELE SA TROSKOVIMA ZA POCETNU I KRAJNJU GODINU
        $("#pretraga_tabele").on("keyup", function() {

            //UPIS POJMA IZ INPUT POLJA U PROMENJIVU
            var kriterijum = $(this).val().toLowerCase();

            //UPIS ID-JA BODY SEKCIJE TABELE,DA BI SE PROVERILO DA LI JE U PITANJU POCETNA ILI KRAJNJA GODINA
            var id_tabele_troskovi = $('table.tabela_konta > tbody').attr('id');
            
            //AKO JE U PITANJU TABELA ZA POCETNU GODINU
            if (id_tabele_troskovi == 'troskovi_pocetna_godina') {

                //FILTRIRANJE REDOVA U BODY SEKCIJI TABELE
                $("#troskovi_pocetna_godina tr").filter(function() {

                    $(this).toggle($(this).text().toLowerCase().indexOf(kriterijum) > -1)
                });
            }

            //AKO JE U PITANJU TABELA ZA KRAJNJU GODINU
            if (id_tabele_troskovi == 'troskovi_krajnja_godina') {

                //FILTRIRANJE REDOVA U BODY SEKCIJI TABELE
                $("#troskovi_krajnja_godina tr").filter(function() {

                    $(this).toggle($(this).text().toLowerCase().indexOf(kriterijum) > -1)
                });
            }
        });


        //FUNKCIJA NA PRETRAGU TABELE SA TROSKOVIMA PRIBAVE ZA POCETNU I KRAJNJU GODINU
        $("#pretraga_tabele2").on("keyup", function() {

            //UPIS POJMA IZ INPUT POLJA U PROMENJIVU
            var kriterijum_pretrage = $(this).val().toLowerCase();

            //UPIS ID-JA BODY SEKCIJE TABELE,DA BI SE PROVERILO DA LI JE U PITANJU POCETNA ILI KRAJNJA GODINA
            var id_tabele_pribava = $('table.tabela_pribava > tbody').attr('id');

            //AKO JE U PITANJU TABELA ZA POCETNU GODINU
            if (id_tabele_pribava == 'pribava_pocetna_godina') {

                //FILTRIRANJE REDOVA U BODY SEKCIJI TABELE
                $("#pribava_pocetna_godina tr").filter(function() {

                    $(this).toggle($(this).text().toLowerCase().indexOf(kriterijum_pretrage) > -1)
                });
            }

            //AKO JE U PITANJU TABELA ZA KRAJNJU GODINU
            if (id_tabele_pribava == 'pribava_krajnja_godina') {

                //FILTRIRANJE REDOVA U BODY SEKCIJI TABELE
                $("#pribava_krajnja_godina tr").filter(function() {

                    $(this).toggle($(this).text().toLowerCase().indexOf(kriterijum_pretrage) > -1)
                });
            }
        });


        //FUNKCIJA NA PRETRAGU TABELE SA TROSKOVIMA SA KLJUCA ZA POCETNU I KRAJNJU GODINU
        $("#pretraga_tabele6").on("keyup", function() {

            //UPIS POJMA IZ INPUT POLJA U PROMENJIVU
            var uneta_vrednost = $(this).val().toLowerCase();

            //UPIS ID-JA BODY SEKCIJE TABELE,DA BI SE PROVERILO DA LI JE U PITANJU POCETNA ILI KRAJNJA GODINA
            var id_tabele_kljuc = $('table.tabela_kljuc > tbody').attr('id');
            //alert(id_tabele_kljuc);

            //AKO JE U PITANJU TABELA ZA POCETNU GODINU
            if (id_tabele_kljuc == 'kljuc_pocetna_godina') {

                //FILTRIRANJE REDOVA U BODY SEKCIJI TABELE
                $("#kljuc_pocetna_godina tr").filter(function() {

                    $(this).toggle($(this).text().toLowerCase().indexOf(uneta_vrednost) > -1)
                });
            }

            //AKO JE U PITANJU TABELA ZA KRAJNJU GODINU
            if (id_tabele_kljuc == 'kljuc_krajnja_godina') {

                //FILTRIRANJE REDOVA U BODY SEKCIJI TABELE
                $("#kljuc_krajnja_godina tr").filter(function() {

                    $(this).toggle($(this).text().toLowerCase().indexOf(uneta_vrednost) > -1)
                });
            }
        });  
    })

    </script>
    
</body>
</html>
