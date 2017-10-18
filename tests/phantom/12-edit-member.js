/*jshint strict:false*/
/*global CasperError, casper, console, phantom, require*/

casper.test.begin('Modif Membre', 3, function suite(test) {
    casper.start("http://localhost:8080/admin/membres/modifier.php?id=2", function() {
        test.assertTitle("Modifier un membre", "Page OK");
        test.assertExists('form', "Formulaire présent");
        this.fillSelectors('form', {
            '#f_nom':               "Pacôme De Champignac",
            '#f_email':             "comte@demo.garradin.eu",
            '#f_adresse':           "Château",
            '#f_ville':             "Champignac",
            '#f_code_postal':       "12070",
            '#f_pays':              "FR",
            '#f_telephone':         "+32777229929",
        }, false);
        this.click('#password_suggest');
        this.click('form input[type=submit]');
    });

    casper.then(function() {
        if (this.getCurrentUrl().match(/fiche\.php\?id=/))
        {
            test.pass("Modif réussie.");
        }
        else
        {
            test.error(this.fetchText('p.error'));
        }
    });

    casper.run(function () {
        test.done();
    });
});