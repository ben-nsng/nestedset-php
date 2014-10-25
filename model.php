<?php

class database {

	private $dh;

	public function __construct($host, $db, $user, $pass) {
		$this->dh = new PDO('mysql:host=' . $host . ';dbname=' . $db, $user, $pass);
	}

	public function quote($str) {
		return $this->dh->quote($str);
	}

	public function last_insert_id() {
		return $this->dh->lastInsertId();
	}

	public function trans_start() {
		$this->dh->beginTransaction();
	}

	public function trans_end() {
		$this->dh->commit();
	}

	public function execute($sql, $placeholders = array()) {
		$stmt = new statement($this->dh);
		return $stmt->query($sql, $placeholders);
	}

}

class statement {
	private $dh;
	private $stmt;
	private $result;
	private $result_array;

	public function __construct($dh) {
		$this->dh = $dh;
	}

	public function query($sql, $placeholders = array()) {
		if(count($placeholders) == 0)
			$this->stmt = $this->dh->query($sql);
		else {
			if(!is_array($placeholders)) $placeholders = array($placeholders);
			$this->stmt = $this->dh->prepare($sql);
			$this->stmt->execute($placeholders);
		}

		return $this;
	}

	public function result() {
		if($this->result != null) return $this->result;
		if($this->stmt != null) {
			$this->result = $this->stmt->fetchAll(PDO::FETCH_OBJ);
			return $this->result;
		}
		return array();
	}

	public function result_array() {
		if($this->result_array != null) return $this->result_array;
		if($this->stmt != null) {
			$this->result_array = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
			return $this->result_array;
		}
		return array();
	}

	public function num_rows() {
		if($this->stmt != null) return $this->stmt->rowCount();
		return 0;
	}
}

class tree {

	private $database;

	public function __construct($database) {
		$this->database = $database;
	}

	// add root
	public function add_root() {
		$sql = 'SELECT COUNT(1) AS row_count FROM tree WHERE lvl=0;';

		$query = $this->database->execute($sql);

		$result = $query->result();
		if($result[0]->row_count != '0') {
			return false;	// root exists, exit
		}

		$sql = 'INSERT INTO tree(label, lft, rht, lvl) VALUES(?, ?, ?, ?)';

		$query = $this->database->execute($sql, array('root', '1', '2', '0'));
	}

	// add node
	public function add_node($label, $node_parent_id = '') {

		//if no parent define, add to root node
		if($node_parent_id == '') {
			$sql = 'SELECT id FROM tree WHERE lvl=0';
			$query = $this->database->execute($sql);
			$result = $query->result();
			$node_parent_id = $result[0]->id;
		}

		//check if node_parent_id exists
		$sql = 'SELECT id, lft, rht, lvl FROM tree WHERE id = ?';
		$query = $this->database->execute($sql, array($node_parent_id));
		if($query->num_rows() == 0) {
			return false;	// no parent ?
		}

		$result = $query->result();
		$parent_lft = $result[0]->lft;
		$parent_rht = $result[0]->rht;
		$parent_lvl = $result[0]->lvl;

		$this->database->trans_start();

		//shift the node to give some room for new node
		$sql = 'UPDATE tree
		SET 
			lft = CASE
				WHEN lft > ? THEN lft + 2
				ELSE lft
			END,
			rht = CASE
				WHEN rht >= ? THEN rht + 2
				ELSE rht
			END
		WHERE
			rht >= ?';
		$this->database->execute($sql, array($parent_rht, $parent_rht, $parent_rht));

		$sql = 'INSERT INTO tree(label, lft, rht, lvl, parent_id) VALUES(?, ?, ?, ?, ?)';
		$this->database->execute($sql, array($label, $parent_rht, $parent_rht + 1, $parent_lvl + 1, $node_parent_id));
	
		$this->database->trans_end();

	}

	// select node
	public function select_all() {
		$sql = 'SELECT id, label, lvl, parent_id,
		FORMAT((((rht - lft) -1) / 2),0) AS cnt_children, 
		CASE WHEN rht - lft > 1 THEN 1 ELSE 0 END AS is_branch
		FROM tree';
		return $this->database->execute($sql);
	}

	// add child
	// ad node 1 into node 2
	public function add_child($label, $node_id_1, $node_id_2) {
		if($node_id_1 == $node_id_2) {
			return false;	//same node
		}

		// check if node id 1, 2 exist
		$sql = 'SELECT id, lft, rht, lvl FROM tree WHERE id=? OR id=?';
		$query = $this->database->execute($sql, array($node_id_1, $node_id_2));

		if($query->num_rows() != 2) {
			return false;	//no node
		}

		// save the result
		$result = $query->result();
		if($result[0]->id == $node_id_1) {
			$node1 = $result[0];
			$node2 = $result[1];
		}
		else {
			$node1 = $result[1];
			$node2 = $result[0];
		}

		$node1_size = $node1->rht - $node1->lft + 1;

		$this->database->trans_start();

		// temporary "remove" moving node
		$sql = 'UPDATE tree
			   SET lft = 0 - lft
				  ,rht = 0 - rht
				  ,lvl = lvl + (?)
			 WHERE lft >= ? AND rht <= ?';
		$this->database->execute($sql, array($node2->lvl - $node1->lvl + 1, $node1->lft, $node1->rht));

		// decrease left / right position for current node
		$sql = 'UPDATE tree
			   SET lft = lft - (?)
			 WHERE lft >= ?';
		$this->database->execute($sql, array($node1_size, $node1->lft));

		$sql = 'UPDATE tree
			   SET rht = rht - (?)
			 WHERE rht >= ?';
		$this->database->execute($sql, array($node1_size, $node1->rht));

		// increase left / right position for future node
		$sql = 'UPDATE tree
			   SET lft = lft + (?)
			 WHERE lft >= ?';
		$this->database->execute($sql, array($node1_size, $node2->rht > $node1->rht ? $node2->rht - $node1_size : $node2->rht));

		$sql = 'UPDATE tree
			   SET rht = rht + (?)
			 WHERE rht >= ?';
		$this->database->execute($sql, array($node1_size, $node2->rht > $node1->rht ? $node2->rht - $node1_size : $node2->rht));

		// move the node to new position
		$sql = 'UPDATE tree
				SET
					lft = 0 - lft + (?),
					rht = 0 - rht + (?)
				WHERE lft <= ? AND rht >= ?';
		$this->database->execute($sql, array(
			$node2->rht > $node1->rht ? $node2->rht - $node1->rht - 1 : $node2->rht - $node1->rht - 1 + $node1_size,
			$node2->rht > $node1->rht ? $node2->rht - $node1->rht - 1 : $node2->rht - $node1->rht - 1 + $node1_size,
			0 - $node1->lft, 0 - $node1->rht));

		// update parent
		$sql = 'UPDATE tree
				SET
					label = ?,
					parent_id = ?
				WHERE
					id = ?';
		$this->database->execute($sql, array($label, $node2->id, $node1->id));

		$this->database->trans_end();
	}

	// add before
	// add node 1 before node 2
	public function add_before($label, $node_id_1, $node_id_2) {
		if($node_id_1 == $node_id_2) {
			return false;	//same node
		}

		// check if node id 1, 2 exist
		$sql = 'SELECT id, lft, rht, lvl, parent_id FROM tree WHERE id=? OR id=?';
		$query = $this->database->execute($sql, array($node_id_1, $node_id_2));

		if($query->num_rows() != 2) {
			return false;	//no node
		}

		// save the result
		$result = $query->result();
		if($result[0]->id == $node_id_1) {
			$node1 = $result[0];
			$node2 = $result[1];
		}
		else {
			$node1 = $result[1];
			$node2 = $result[0];
		}

		$this->database->trans_start();

		// if not in same level, put it in same level
		if($node1->lvl != $node2->lvl || $node1->parent_id != $node2->parent_id) {
			$this->add_child($label, $node_id_1, $node2->parent_id);
			return $this->add_before($label, $node_id_1, $node_id_2);
		}

		// same level, put node 1 before node 2
		$node1_size = $node1->rht - $node1->lft + 1;
		$node2_size = $node2->rht - $node1->lft + 1;

		// temporary "remove" moving node
		$sql = 'UPDATE tree
		   SET lft = 0 - lft
			  ,rht = 0 - rht
		 WHERE lft >= ? AND rht <= ?';

		$this->database->execute($sql, array($node1->lft, $node1->rht));
		
		if($node1->lft > $node2->lft) {	//move left

			//shift the node to right to give some room
			$sql = 'UPDATE tree 				
			   SET lft = lft + ?
				  ,rht = rht + ?
			 WHERE lft >= ? AND rht <= ?';
			 $this->database->execute($sql, array($node1_size, $node1_size, $node2->lft, $node1->lft));

			//move back the node1
			$sql = 'UPDATE tree 				
			   SET lft = 0 - lft - ?
				  ,rht = 0 - rht - ?
			 WHERE lft <= ? AND rht >= ?';
			 $this->database->execute($sql, array($node1->lft - $node2->lft, $node1->lft - $node2->lft, 0 - $node1->lft, 0 - $node1->rht));
		}

		else {

			//shift the node to left to give some room
			$sql = 'UPDATE tree 				
			   SET lft = lft - ?
				  ,rht = rht - ?
			 WHERE lft >= ? AND rht < ?';
			 $this->database->execute($sql, array($node1_size, $node1_size, $node1->rht, $node2->lft));

			//move back the node1
			$sql = 'UPDATE tree 				
			   SET lft = 0 - lft + ?
				  ,rht = 0 - rht + ?
			 WHERE lft <= ? AND rht >= ?';
			 $this->database->execute($sql, array($node2->lft - $node1->rht - 1, $node2->lft - $node1->rht - 1, 0 - $node1->lft, 0 - $node1->rht));

		}

		$this->database->trans_end();
	}

	// add after
	// add node 1 after node 2
	public function add_after($label, $node_id_1, $node_id_2) {

		if($node_id_1 == $node_id_2) {
			return false;	//same node
		}

		// check if node id 1, 2 exist
		$sql = 'SELECT id, lft, rht, lvl, parent_id FROM tree WHERE id=? OR id=?';
		$query = $this->database->execute($sql, array($node_id_1, $node_id_2));

		if($query->num_rows() != 2) {
			return false;	//no node
		}

		// save the result
		$result = $query->result();
		if($result[0]->id == $node_id_1) {
			$node1 = $result[0];
			$node2 = $result[1];
		}
		else {
			$node1 = $result[1];
			$node2 = $result[0];
		}

		$this->database->trans_start();

		// if not in same level, put it in same level
		if($node1->lvl != $node2->lvl || $node1->parent_id != $node2->parent_id) {
			$this->add_child($label, $node_id_1, $node2->parent_id);
			return $this->add_after($label, $node_id_1, $node_id_2);
		}

		// same level, put node 1 before node 2
		$node1_size = $node1->rht - $node1->lft + 1;
		$node2_size = $node2->rht - $node1->lft + 1;

		// temporary "remove" moving node
		$sql = 'UPDATE tree 			
		   SET lft = 0 - lft
			  ,rht = 0 - rht
		 WHERE lft >= ? AND rht <= ?';

		$this->database->execute($sql, array($node1->lft, $node1->rht));
		
		if($node1->lft > $node2->lft) {	//move left

			//shift the node to right to give some room
			$sql = 'UPDATE tree 				
			   SET lft = lft + ?
				  ,rht = rht + ?
			 WHERE lft > ? AND rht <= ?';
			 $this->database->execute($sql, array($node1_size, $node1_size, $node2->rht, $node1->lft));

			//move back the node1
			$sql = 'UPDATE tree 				
			   SET lft = 0 - lft - ?
				  ,rht = 0 - rht - ?
			 WHERE lft <= ? AND rht >= ?';
			 $this->database->execute($sql, array($node1->lft - $node2->rht - 1, $node1->lft - $node2->rht - 1, 0 - $node1->lft, 0 - $node1->rht));
		}

		else {

			//shift the node to left to give some room
			$sql = 'UPDATE tree 				
			   SET lft = lft - ?
				  ,rht = rht - ?
			 WHERE lft >= ? AND rht <= ?';
			 $this->database->execute($sql, array($node1_size, $node1_size, $node1->rht, $node2->rht));

			//move back the node1
			$sql = 'UPDATE tree 				
			   SET lft = 0 - lft + ?
				  ,rht = 0 - rht + ?
			 WHERE lft <= ? AND rht >= ?';
			 $this->database->execute($sql, array($node2->rht - $node1->rht, $node2->rht - $node1->rht, 0 - $node1->lft, 0 - $node1->rht));

		}

		$this->database->trans_end();
	}

	// delete node
	public function delete_node($node_id) {

		$sql = 'SELECT id, lft, rht, lvl FROM tree WHERE id=?';
		$query = $this->database->execute($sql, $node_id);

		if($query->num_rows() == 0) {
			return false;	//no node
		}

		$result = $query->result();
		$lft = $result[0]->lft;
		$rht = $result[0]->rht;
		$lvl = $result[0]->lvl;
		
		$this->database->trans_start();

		// remove parent first
		$sql = 'UPDATE tree 				
				SET parent_id = NULL
				WHERE lft >= ? AND rht <= ?';
		$this->database->execute($sql, array($lft, $rht));


		// delete nodes
		$sql = 'DELETE *
			  FROM tree 			  
			 WHERE lft >= ?
			   AND rht <= ?';
		$this->database->execute($sql, array($lft, $rht));

		$node_tmp = $rht - $lft + 1;

		// shift other node to correct position
		$sql = 'UPDATE tree 				
			   SET lft = CASE WHEN lft > ? THEN lft - ? ELSE lft END,
				  rht = CASE WHEN rht >= ? THEN rht - ? ELSE rht END
			 WHERE rht >= ?';
		$this->database->execute($sql, array($lft, $node_tmp, $rht, $node_tmp, $rht));

		$this->database->trans_end();
	}

}

$db = new database('localhost', 'tree', 'tree', 'tree');
$tree = new tree($db);
