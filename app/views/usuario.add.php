<?php
$extend = 'layout';
set('title','Adicionar Usuario');

function contenido(){
	$html = helper('html');
	$form = $html->form();
	$form->model = model();
	$form->start();
	?>
	<table border='0' width='400'>
		<tr>
			<td>E-mail</td>
			<td><?php $form->input('email') ?></td>
		</tr>
		<tr>
			<td>Clave</td>
			<td><?php $form->input('clave','password') ?></td>
		</tr>
		<tr>
			<td>Nombre</td>
			<td><?php $form->input('nombre') ?></td>
		</tr>
		<tr>
			<td>Es admin</td>
			<td><?php $form->yesno('admin','Sí','No',FALSE) ?></td>
		</tr>
		<tr>
			<td colspan='2' align='center'>
			<?php $form->submit() ?>
			<?php $form->reset() ?>
			</td>
		</tr>
	</table>
	<?php
	$form->end();
	?>
	<br/><?php $html->link('Volver','index') ?>
	<?php
}