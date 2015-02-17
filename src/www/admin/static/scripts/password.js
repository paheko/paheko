(function () {
	var strength_elm, match_elm, pw_elm, pw2_elm, suggest_elm;

	RegExp.quote = function(str) {
	    return (str+'').replace(/([.?*+^$[\]\\(){}|-])/g, "\\$1");
	};

	window.initPasswordField = function(suggest, password, password2)
	{
		suggest_elm = (typeof suggest == 'string') ? document.getElementById(suggest) : suggest;
		pw_elm = (typeof password == 'string') ? document.getElementById(password) : password;
		pw2_elm = (typeof password2 == 'string') ? document.getElementById(password2) : password2;

		suggest_elm.size = suggest_elm.value.length;

		suggest_elm.onclick = function () {
	        pw_elm.value = this.value;
	        pw2_elm.value = this.value;
	        this.select();
	        checkPasswordStrength();
	        checkPasswordMatch();
		};

		strength_elm = document.createElement('span');
		strength_elm.className = 'password_check';
		
		pw_elm.parentNode.appendChild(strength_elm);

		match_elm = document.createElement('span');
		match_elm.className = 'password_check';
		
		pw2_elm.parentNode.appendChild(match_elm);

		pw_elm.onkeyup = checkPasswordStrength;
		pw_elm.onchange = function () { checkPasswordStrength(); checkPasswordMatch(); };
		pw_elm.onblur = function () { checkPasswordStrength(); checkPasswordMatch(); };
		pw2_elm.onkeypress = checkPasswordMatch;
		pw2_elm.onblur = checkPasswordMatch;
		pw2_elm.onchange = checkPasswordMatch;

		pw_elm.form.addEventListener('submit', function (e) {
			if (pw_elm.value == '') return true;
			if (scorePassword(pw_elm.value) <= 30 && !window.confirm("Êtes-vous sûr de vouloir utiliser un mot de passe aussi mauvais que ça ?"))
			{
				e = e || window.event;
				if(e.preventDefault)
					e.preventDefault();
				if(e.stopPropagation)
					e.stopPropagation();
				e.returnValue = false;
				e.cancelBubble = true;
				return false;
			}
		}, true);
	};

    function scorePassword(pass) {
	    var score = 0;

	    if (!pass)
	        return score;

	    // Date
	    if (/19\d\d|200\d|201\d/.test(pass))
	    	score -= 5;

	    // Autres champs du formulaire
	    var inputs = document.getElementsByTagName('input');

	    for (var i = 0; i < inputs.length; i++)
	    {
	    	var input = inputs[i];

	    	if (input.type != 'text' && input.type != 'url' && input.type != 'email')
	    		continue;

	    	if (input == suggest_elm)
	    		continue;

	    	if (input.value.replace(/\s/, '') == '')
	    		continue;

	    	var v = input.value.split(/[\W]/);
	    	for (var j = 0; j < v.length; j++)
	    	{
		    	if (v[j].length < 4)
		    		continue;

		    	var r = new RegExp(RegExp.quote(v[j]), 'ig');
		    	score -= pass.match(r) ? pass.match(r).length * 5 : 0;
		    }
	    }
	    
	    // award every unique letter until 5 repetitions
	    var letters = new Object();
	    for (var i=0; i<pass.length; i++) {
	        letters[pass[i]] = (letters[pass[i]] || 0) + 1;
	        score += 5.0 / letters[pass[i]];
	    }

	    // bonus points for mixing it up
	    var variations = {
	        digits: /\d/.test(pass),
	        lower: /[a-z]/.test(pass),
	        upper: /[A-Z]/.test(pass),
	        nonWords: /\W/.test(pass),
	    }

	    variationCount = 0;
	    for (var check in variations) {
	        variationCount += (variations[check] == true) ? 1 : 0;
	    }
	    score += (variationCount - 1) * 10;

	    return parseInt(score);
	}

	function checkPasswordStrength() {
	    if (pw_elm.value == '')
	    {
	    	strength_elm.className = strength_elm.className.split(' ')[0];
	        strength_elm.innerHTML = '';
	        return true;
	    }

	    if (!pw_elm.value.match(new RegExp(pw_elm.getAttribute('pattern'))))
	    {
	    	strength_elm.className = strength_elm.className.split(' ')[0] + ' fail';
	        strength_elm.innerHTML = 'Trop court&nbsp;!';
	        return true;
	    }

	    var score = scorePassword(pw_elm.value);

	    if (score > 80)
	    {
	    	strength_elm.className = strength_elm.className.split(' ')[0] + ' ok';
	        strength_elm.innerHTML = 'Sécurité : <b>forte</b>';
	    }
	    else if (score > 60)
	    {
	    	strength_elm.className = strength_elm.className.split(' ')[0] + ' medium';
	        strength_elm.innerHTML = 'Sécurité : <b>moyenne</b>';
	    }
	    else if (score >= 30)
	    {
	    	strength_elm.className = strength_elm.className.split(' ')[0] + ' weak';
	        strength_elm.innerHTML = 'Sécurité : <b>mauvaise</b>';
	    }
	    else
	    {
	    	strength_elm.className = strength_elm.className.split(' ')[0] + ' fail';
	        strength_elm.innerHTML = 'Sécurité : <b>aucune</b>';	    	
	    }

	    return true;
	}

	function checkPasswordMatch()
	{
		if (pw2_elm.value == '' && pw_elm.value == '')
		{
			match_elm.className = strength_elm.className.split(' ')[0];
			match_elm.innerHTML = '';
		}
		else if (pw_elm.value !== pw2_elm.value)
		{
			match_elm.className = strength_elm.className.split(' ')[0] + ' fail';
			match_elm.innerHTML = 'Ne correspond pas au mot de passe entré.';
		}
		else
		{
			match_elm.className = strength_elm.className.split(' ')[0] + ' ok';
			match_elm.innerHTML = '&#10003;';
		}
	}
}());