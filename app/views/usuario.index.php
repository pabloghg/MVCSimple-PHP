<?php
$extend = 'layout';
set('title','Usuarios');

function contenido(){
	$model = model();
	$html = helper('html');
?>
<table border='0' width='100%'>
	<tr>
		<th>Id</th>
		<th>E-mail</th>
		<th>Nombre</th>
		<th>Es admin</th>
		<th>&nbsp;</th>
	<?php
	foreach ($model->records as $record) {
	?>
	<tr>
		<td><?php echo $record['id'] ?></td>
		<td><?php echo $record['email'] ?></td>
		<td><?php echo $record['nombre'] ?></td>
		<td><?php echo $record['admin']?'Sí':'No' ?></td>
		<td><?php $html->link('Editar','edit',$record['id']) ?> |
			<?php $html->link('Eliminar','delete',$record['id'],'*') ?></td>
	</tr>
	<?php
	}
	?>
	</tr>
</table>
<br/><?php $html->link('Adicionar','add') ?>
<?php
}