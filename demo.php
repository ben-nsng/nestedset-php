<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="assets/css/jstree.css" />
<script type="text/javascript" src="assets/js/jquery.min.js"></script>
<script type="text/javascript" src="assets/js/jstree.js"></script>
<script>
$(function() {
	//create tree view
	var tree = $("#jstree").jstree({
		'contextmenu' : {
			items : function(node) {
				return {
					remove : {
						'label'	: 'Remove',
						'icon'	: false,
						'action': function() {
							$.ajax({
								type	: 'POST',
								url		: 'model.php',
								data	: { action: 'remove_node', id : $(node).attr('id') },
								success	: function(data) {
									$(node).remove();
								}
							});
						}
					}
				}
			}
		},
		'plugins': ['themes', 'html_data', 'ui', 'dnd', 'crrm', 'contextmenu']
	});

	//create node
	$('.create-node').click(function() {
		$.ajax({
			type	: 'POST',
			url		: 'model.php',
			data	: { action: 'create_node' },
			success	: function(id) {
				tree.jstree('create_node', -1, 'last', 'new_node', false, false);
			}
		});
	});

	//move node
	tree.bind('move_node.jstree', function(event, data) {
		var json = [
			'{"parent":"' + $(data.rslt.np).attr('id') + '"',
			'"prev":"' + $(data.rslt.o).prev().attr('id') + '"',
			'"new":"' + $(data.rslt.o).attr('id') + '"',
			'"next":"' + $(data.rslt.o).next().attr('id') + '"}'
		].join();

		var rollback = function() {
			$.jstree.rollback(data.rlbk);
		};

		$.ajax({
			type: "POST",
			url: "model.php",
			data: { action: 'move_node', node_data: json },
			success: function(data) {
			}
		});
	});

	//query all node
	$.ajax({
		type	: 'POST',
		url		: 'model.php',
		data	: { action: 'select_all' },
		success	: function(data) {
			var root_id = -1, node;
			for(var i = 0; i < data.length; i++) {
				if(data[i].parent_id === null) {
					root_id = data[i].id;
					continue;
				}

				//create tree node
				node = tree.jstree(
					'create_node',
					data[i].parent_id == root_id ? -1 : $('#' + data[i].parent_id),
					'last',
					'node_' + data[i].id,
					false,
					false
					);

				//assign id to tree node
				node.attr('id', data[i].id);
			}

			tree.jstree('open_all');
		},
		dataType: 'json'
	});

});
</script>
</head>
<body>
</body>
<button class='create-node'>Create Node</button><br /><br /><br />
<div id="jstree">
</div>

</html>
