// From https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps/Add_to_home_screen
// Register service worker for add to home screen feature
if ('serviceWorker' in navigator) {
	// The service worker must be exactly at the start_url specified in the manifest
	navigator.serviceWorker.register(g.admin_url + '_serviceworker.js').then(() => {
	});
}

let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
	// Prevent Chrome 67 and earlier from automatically showing the prompt
	e.preventDefault();
	// Stash the event so it can be triggered later.
	deferredPrompt = e;
	// Update UI to notify the user they can add to home screen
	g.toggle('#homescreen-btn', true);

	$('#homescreen-btn').addEventListener('click', (e) => {
		// hide our user interface that shows our A2HS button
		g.toggle('#homescreen-btn', false);
		// Show the prompt
		deferredPrompt.prompt();
		// Wait for the user to respond to the prompt
		deferredPrompt.userChoice.then((choiceResult) => {
			if (choiceResult.outcome === 'accepted') {
				console.log('User accepted the A2HS prompt');
			} else {
				console.log('User dismissed the A2HS prompt');
			}
			deferredPrompt = null;
		});
	});
});