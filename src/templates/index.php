<?php
script('passwords', 'compatibility');
script('passwords', 'utility');
script('passwords', 'Libraries/vue.dev.2.4.2');
script('passwords', 'Libraries/vue.router.2.7.0');
script('passwords', 'SimpleMDE/simplemde.min');
script('passwords', 'Classes/Encryption');
script('passwords', 'Classes/SimpleApi');
script('passwords', 'Classes/EnhancedApi');
script('passwords', 'Classes/UserInterface');
script('passwords', 'main');
script('passwords', 'Components/Line/Password');
script('passwords', 'Components/Breadcrumbs');
script('passwords', 'Components/DialogPasswordCreate');
script('passwords', 'Components/Foldout');
script('passwords', 'Components/Section/All');
script('passwords', 'Components/Section/Favourites');
script('passwords', 'Components/Section/Folders');
script('passwords', 'Components/Section/Recent');
script('passwords', 'Components/Section/Security');
script('passwords', 'Components/Section/Shared');
script('passwords', 'Components/Section/Tags');
script('passwords', 'Components/Section/Trash');
script('passwords', 'Components/Tags');
style('passwords', 'SimpleMDE/simplemde.min');
style('passwords', 'style');
?>

<?php print_unescaped($this->inc('partials/vue.components')); ?>
<?php print_unescaped($this->inc('partials/vue.partials')); ?>
<?php print_unescaped($this->inc('partials/vue.sections')); ?>

<div id="app" class="passwords">
    <?php print_unescaped($this->inc('partials/constants')); ?>
	<div id="app-navigation">
		<?php print_unescaped($this->inc('navigation/index')); ?>
		<?php print_unescaped($this->inc('settings/index')); ?>
	</div>

	<div id="app-content">
		<div id="app-content-wrapper">
			<?php print_unescaped($this->inc('content/index')); ?>
		</div>
	</div>
    <div id="app-popup"><div></div></div>
</div>

