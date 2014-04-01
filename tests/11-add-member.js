/*jshint strict:false*/
/*global CasperError, casper, console, phantom, require*/

casper.test.begin('Login', 3, function suite(test) {
    casper.start("http://localhost:8080/admin/membres/ajouter.php", function() {
        test.assertTitle("Ajouter un membre", "Page OK");
        test.assertExists('form', "Formulaire présent");
        this.fillSelectors('form', {
            '#f_nom':               "Gaston Lagaffe",
            '#f_email':             "gaston@demo.garradin.eu",
            '#f_adresse':           "42 rue Spirou",
            '#f_ville':             "Bruxelles",
            '#f_code_postal':       "3001",
            '#f_pays':              "BE",
            '#f_telephone':         "01 02 03 04 05",
        }, false);
        this.click('#password_suggest');
        this.click('form input[type=submit]');
    });

    casper.then(function() {
        if (this.getCurrentUrl().match(/fiche\.php\?id=/))
        {
            test.pass("Ajout réussi.");
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