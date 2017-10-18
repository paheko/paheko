/*jshint strict:false*/
/*global CasperError, casper, console, phantom, require*/

casper.test.begin('Ajout cotise', 3, function suite(test) {
	casper.start("http://localhost:8080/admin/membres/cotisations/", function() {
		test.assertTitle("Cotisations", "Page OK");
		test.assertExists('form', "Formulaire présent");
		this.click('#f_periodicite_jours');
		this.click('#f_categorie');
		this.fillSelectors('form', {
			'#f_intitule':
				'Cotisation normale',
			'#f_montant':
				'15.00',
			'#f_id_categorie_compta':
				'18',
			'#f_duree':
				'300',
		}, false);
		this.click('form input[type=submit]');
	});

	casper.thenOpen('http://localhost:8080/admin/membres/cotisations/voir.php?id=1', function() {
		test.assertTitle("Membres ayant cotisé", "Cotisation OK");
	});

	casper.run(function () {
		test.done();
	});
});