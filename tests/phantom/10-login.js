/*jshint strict:false*/
/*global CasperError, casper, console, phantom, require*/

casper.test.begin('Login', 3, function suite(test) {
    casper.start("http://localhost:8080/admin/login.php", function() {
        test.assertTitle("Connexion", "Page de connexion");
        test.assertExists('form', "Formulaire présent");
        this.fillSelectors('form', {
            '#f_id':                "ada@demo.garradin.eu",
            '#f_passe':             "Garradin c'est chouette",
        }, false);
        this.click('form input[type=submit]');
    });

    casper.then(function() {
        if (this.getCurrentUrl().match(/\/$/))
        {
            test.pass("Connexion réussie.");
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