<style>
#item_list .reference {
	min-width: 0;
	max-width: 7em;
}

#invoice_module_search p {
	display: inline;
}

#invoice_module_search input[type=search] {
	min-width: 5em;
	width: 14em;
}

.irreversible, .irreversible:before, .irreversible > span {
	color: red;
	font-weight: bold;
}

.safe, .safe span {
	font-weight: bold;
}

.organization_name, .business_name, .signing_place {
	text-transform: uppercase;
}

nav.tabs aside.menu ul, ul.list_action_buttons {
	border: 0;
	display: flex;
}

nav.tabs aside.menu li, .list_action_buttons li {
	margin: 0;
}

nav.tabs aside.menu li a, .list_action_buttons li a {
	background: none;
	border-radius: 0;
	padding: 0.2em 0.4em;
}

nav.tabs aside.menu li a:hover, .list_action_buttons li a:hover {
	border: 1px solid rgba(var(--gMainColor), 0.5);
}

nav.tabs aside.menu li a.icn-btn, .list_action_buttons li a.icn-btn {
	margin: 0.2em 0.2em;
}
</style>