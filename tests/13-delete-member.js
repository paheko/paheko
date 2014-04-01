/*jshint strict:false*/
/*global CasperError, casper, console, phantom, require*/

casper.test.begin('Login', 3, function suite(test) {
    casper.start("http://localhost:8080/admin/membres/supprimer.php?id=2", function() {
        test.assertTitle("Supprimer un membre", "Page OK");
        test.assertExists('form', "Formulaire présent");
        this.click('form input[type=submit]');
    });

    casper.then(function() {
        if (this.getCurrentUrl().match(/\/membres\/$/))
        {
            test.pass("Suppression OK");
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