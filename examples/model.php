<?php

ini_set('display_errors', '1');
include('../pdo.nestedset.php');

$treeModel = new PDONestedSet(
				new PDO('mysql:host=localhost;dbname=tree', 'tree', 'tree')
				);

if(isset($_POST['action'])) {
	switch($_POST['action']) {
		case 'create_node':
			echo $treeModel->addNode('new_node');
		break;
		case 'select_all':
			echo json_encode($treeModel->selectAll()->result());
		break;
		case 'move_node':
			$node_data = json_decode($_POST['node_data']);
			//move the new node after the prev one
			if($node_data->prev != 'undefined') {
				$treeModel->addAfter($node_data->new, $node_data->prev);
			}
			//move the new node before the next one
			else if($node_data->next != 'undefined') {
				$treeModel->addBefore($node_data->new, $node_data->next);
			}
			else if($node_data->parent != 'undefined') {
				$treeModel->addChild($node_data->new, $node_data->parent);
			}
		break;
		case 'remove_node':
			if(isset($_POST['id']))
				$treeModel->deleteNode($_POST['id']);
		break;
	}
}
