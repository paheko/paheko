/*jshint strict:false*/
/*global CasperError, casper, console, phantom, require*/

casper.test.begin('Installation', 3, function suite(test) {
    casper.start("http://localhost:8080/", function() {
        test.assertTitle("Garradin - Installation", "Page installation");
        test.assertExists('form', "Formulaire présent");
        this.fillSelectors('form', {
            '#f_nom_asso':          "Garradin",
            '#f_email_asso':        "asso@demo.garradin.eu",
            '#f_adresse_asso':      "15 rue du Logiciel Libre\n21000 DIJON",
            '#f_nom_membre':        "Ada Lovelace",
            '#f_cat_membre':        "Bureau",
            '#f_email_membre':      "ada@demo.garradin.eu",
            '#f_passe_membre':      "Garradin c'est chouette",
            '#f_repasse_membre':    "Garradin c'est chouette"
        }, false);
        this.click('form input[type=submit]');
    });

    casper.then(function() {
        if (this.getCurrentUrl().match(/login\.php/))
        {
            test.pass("Installation réussie.");
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