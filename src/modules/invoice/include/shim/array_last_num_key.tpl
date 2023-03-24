{{* Hack to get the unique element since "$my_array|keys" and "$my_array|first" do not exist *}}
{{#foreach from=$array key='id' item='item'}}
	{{if $id != $id|intval|strval}} {{* Hack to check the data is a number *}}
		{{:assign var='check_errors.' value=$error_message}}
	{{/if}}
	{{:assign var=$name value=$id|intval}}
{{/foreach}}
