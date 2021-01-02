

var brojac = 0;
var brojac2 = 0;
var brojac3 = 0;
var brojac4 = 0;
var brojac5 = 0;

//var tabela_troskovi = $('.konta').html();

//alert(tabela_troskovi_pocetna);

var broj_redova = $('.grupa_pocetna').length;

$('td.grupa_konta').each(function() {

    troskovi_pocetna[brojac] = [$(this).html()];
    brojac++;

});

$('td.sektor_konta').each(function() {

    troskovi_pocetna[brojac2].push($(this).html());
    brojac2++;
});
                    
$('td.broj_konta').each(function() {

    troskovi_pocetna[brojac3].push($(this).html());
    brojac3++;
});

$('td.opis_konta').each(function() {

    troskovi_pocetna[brojac4].push($(this).html());
    brojac4++;
});

$('td.iznos_konta').each(function() {

    troskovi_pocetna[brojac5].push($(this).html());
    brojac5++;
});

//console.log(troskovi_pocetna);

